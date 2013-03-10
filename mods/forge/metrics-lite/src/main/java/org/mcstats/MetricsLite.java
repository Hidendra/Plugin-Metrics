/*
 * Copyright 2011-2013 Tyler Blair. All rights reserved.
 * Ported to Minecraft Forge by Mike Primm
 *
 * Redistribution and use in source and binary forms, with or without modification, are
 * permitted provided that the following conditions are met:
 *
 *    1. Redistributions of source code must retain the above copyright notice, this list of
 *       conditions and the following disclaimer.
 *
 *    2. Redistributions in binary form must reproduce the above copyright notice, this list
 *       of conditions and the following disclaimer in the documentation and/or other materials
 *       provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ''AS IS'' AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
 * FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE AUTHOR OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
 * ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * The views and conclusions contained in the software and documentation are those of the
 * authors and contributors and should not be interpreted as representing official policies,
 * either expressed or implied, of anybody else.
 */

package org.mcstats;

import java.io.BufferedReader;
import java.io.File;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.io.UnsupportedEncodingException;
import java.net.Proxy;
import java.net.URL;
import java.net.URLConnection;
import java.net.URLEncoder;
import java.util.Collections;
import java.util.EnumSet;
import java.util.HashSet;
import java.util.Iterator;
import java.util.LinkedHashSet;
import java.util.Set;
import java.util.UUID;

import net.minecraft.server.MinecraftServer;
import net.minecraftforge.common.Configuration;
import net.minecraftforge.common.Property;

import cpw.mods.fml.common.FMLLog;
import cpw.mods.fml.common.IScheduledTickHandler;
import cpw.mods.fml.common.Loader;
import cpw.mods.fml.common.TickType;
import cpw.mods.fml.common.registry.TickRegistry;
import cpw.mods.fml.relauncher.Side;

/**
 * <p>
 * The metrics class obtains data about a plugin and submits statistics about it
 * to the metrics backend.
 * </p>
 */
public class MetricsLite {

    /**
     * The current revision number
     */
    private final static int REVISION = 6;

    /**
     * The base url of the metrics domain
     */
    private static final String BASE_URL = "http://mcstats.org";

    /**
     * The url used to report a server's status
     */
    private static final String REPORT_URL = "/report/%s";

    /**
     * The separator to use for custom data. This MUST NOT change unless you are
     * hosting your own version of metrics and want to change it.
     */
    private static final String CUSTOM_DATA_SEPARATOR = "~~";

    /**
     * Interval of time to ping (in minutes)
     */
    private static final int PING_INTERVAL = 10;

    /**
     * The mod this metrics submits for
     */
    private final String modname;
    private final String modversion;

    /**
     * The metrics configuration file
     */
    private final Configuration configuration;

    /**
     * The metrics configuration file
     */
    private final File configurationFile;

    /**
     * Unique server id
     */
    private final String guid;

    /**
     * Debug mode
     */
    private final boolean debug;

    /**
     * The scheduled task
     */
    private volatile IScheduledTickHandler task = null;

    /**
     * Flag for tracking if metrics have been stopped/paused
     */
    private boolean stopped = false;

    public MetricsLite(final String modname, final String modversion)
            throws IOException {
        if ((modname == null) || (modversion == null)) {
            throw new IllegalArgumentException(
                    "modname and modversion cannot be null");
        }

        this.modname = modname;
        this.modversion = modversion;

        // load the config
        configurationFile = getConfigFile();
        configuration = new Configuration(configurationFile);

        // Get values, and add some defaults, if needed
        configuration.get(Configuration.CATEGORY_GENERAL, "opt-out", false,
                "Set to true to disable all reporting");
        guid = configuration.get(Configuration.CATEGORY_GENERAL, "guid", UUID
                .randomUUID().toString(), "Server unique ID").getString();
        debug = configuration.get(Configuration.CATEGORY_GENERAL, "debug",
                false, "Set to true for verbose debug").getBoolean(false);
        configuration.save();
    }

    /**
     * Start measuring statistics. This will immediately create an async
     * repeating task as the plugin and send the initial data to the metrics
     * backend, and then after that it will post in increments of PING_INTERVAL
     * * 1200 ticks.
     * 
     * @return True if statistics measuring is running, otherwise false.
     */
    public boolean start() {
        // Did we opt out?
        if (isOptOut()) {
            return false;
        }
        stopped = false;

        // Is metrics already running?
        if (task != null) {
            return true;
        }

        // Begin hitting the server with glorious data
        task = new IScheduledTickHandler() {
            private boolean firstPost = true;
            private Thread thrd = null;

            @Override
            public void tickStart(EnumSet<TickType> type, Object... tickData) {
            }

            @Override
            public void tickEnd(EnumSet<TickType> type, Object... tickData) {
                if (stopped)
                    return;

                // Disable Task, if it is running and the server owner decided
                // to opt-out
                if (isOptOut()) {
                    stopped = true;
                    return;
                }
                if (thrd == null) {
                    thrd = new Thread(new Runnable() {
                        public void run() {
                            try {
                                // We use the inverse of firstPost because if it
                                // is the first time we are posting,
                                // it is not a interval ping, so it evaluates to
                                // FALSE
                                // Each time thereafter it will evaluate to
                                // TRUE, i.e PING!
                                postPlugin(!firstPost);
                                // After the first post we set firstPost to
                                // false
                                // Each post thereafter will be a ping
                                firstPost = false;
                            } catch (IOException e) {
                                if (debug) {
                                    FMLLog.info("[Metrics] Exception - %s",
                                            e.getMessage());
                                }
                            } finally {
                                thrd = null;
                            }
                        }
                    });
                    thrd.start();
                }
            }

            @Override
            public EnumSet<TickType> ticks() {
                return EnumSet.of(TickType.SERVER);
            }

            @Override
            public String getLabel() {
                return modname + " Metrics";
            }

            @Override
            public int nextTickSpacing() {
                if (firstPost)
                    return 100;
                else
                    return PING_INTERVAL * 1200;
            }
        };
        TickRegistry.registerScheduledTickHandler(task, Side.SERVER);

        return true;
    }

    /**
     * Stop processing
     */
    public void stop() {
        stopped = true;
    }

    /**
     * Has the server owner denied plugin metrics?
     * 
     * @return true if metrics should be opted out of it
     */
    public boolean isOptOut() {
        // Reload the metrics file
        configuration.load();
        return configuration.get(Configuration.CATEGORY_GENERAL, "opt-out",
                false).getBoolean(false);
    }

    /**
     * Enables metrics for the server by setting "opt-out" to false in the
     * config file and starting the metrics task.
     * 
     * @throws java.io.IOException
     */
    public void enable() throws IOException {
        // Check if the server owner has already set opt-out, if not, set it.
        if (isOptOut()) {
            configuration.getCategory(Configuration.CATEGORY_GENERAL).get("opt-out").set(false);
            configuration.save();
        }
        // Enable Task, if it is not running
        if (task == null) {
            start();
        }
    }

    /**
     * Disables metrics for the server by setting "opt-out" to true in the
     * config file and canceling the metrics task.
     * 
     * @throws java.io.IOException
     */
    public void disable() throws IOException {
        // Check if the server owner has already set opt-out, if not, set it.
        if (!isOptOut()) {
            configuration.getCategory(Configuration.CATEGORY_GENERAL).get("opt-out").set(true);
            configuration.save();
        }
    }

    /**
     * Gets the File object of the config file that should be used to store data
     * such as the GUID and opt-out status
     * 
     * @return the File object for the config file
     */
    public File getConfigFile() {
        return new File(Loader.instance().getConfigDir(), "PluginMetrics.cfg");
    }

    /**
     * Generic method that posts a plugin to the metrics website
     */
    private void postPlugin(final boolean isPing) throws IOException {
        // Server software specific section
        String pluginName = modname;
        boolean onlineMode = MinecraftServer.getServer().isServerInOnlineMode(); // TRUE
                                                                                 // if
                                                                                 // online
                                                                                 // mode
                                                                                 // is
                                                                                 // enabled
        String pluginVersion = modversion;
        String serverVersion;
        if (MinecraftServer.getServer().isDedicatedServer()) {
            serverVersion = "MinecraftForge (MC: "
                    + MinecraftServer.getServer().getMinecraftVersion() + ")";
        } else {
            serverVersion = "MinecraftForgeSSP (MC: "
                    + MinecraftServer.getServer().getMinecraftVersion() + ")";
        }
        int playersOnline = MinecraftServer.getServer().getCurrentPlayerCount();

        // END server software specific section -- all code below does not use
        // any code outside of this class / Java

        // Construct the post data
        final StringBuilder data = new StringBuilder();

        // The plugin's description file containg all of the plugin data such as
        // name, version, author, etc
        data.append(encode("guid")).append('=').append(encode(guid));
        encodeDataPair(data, "version", pluginVersion);
        encodeDataPair(data, "server", serverVersion);
        encodeDataPair(data, "players", Integer.toString(playersOnline));
        encodeDataPair(data, "revision", String.valueOf(REVISION));

        // New data as of R6
        String osname = System.getProperty("os.name");
        String osarch = System.getProperty("os.arch");
        String osversion = System.getProperty("os.version");
        String java_version = System.getProperty("java.version");
        int coreCount = Runtime.getRuntime().availableProcessors();

        // normalize os arch .. amd64 -> x86_64
        if (osarch.equals("amd64")) {
            osarch = "x86_64";
        }

        encodeDataPair(data, "osname", osname);
        encodeDataPair(data, "osarch", osarch);
        encodeDataPair(data, "osversion", osversion);
        encodeDataPair(data, "cores", Integer.toString(coreCount));
        encodeDataPair(data, "online-mode", Boolean.toString(onlineMode));
        encodeDataPair(data, "java_version", java_version);

        // If we're pinging, append it
        if (isPing) {
            encodeDataPair(data, "ping", "true");
        }

        // Create the url
        URL url = new URL(BASE_URL
                + String.format(REPORT_URL, encode(pluginName)));

        // Connect to the website
        URLConnection connection;

        // Mineshafter creates a socks proxy, so we can safely bypass it
        // It does not reroute POST requests so we need to go around it
        if (isMineshafterPresent()) {
            connection = url.openConnection(Proxy.NO_PROXY);
        } else {
            connection = url.openConnection();
        }

        connection.setDoOutput(true);

        // Write the data
        final OutputStreamWriter writer = new OutputStreamWriter(
                connection.getOutputStream());
        writer.write(data.toString());
        writer.flush();

        // Now read the response
        final BufferedReader reader = new BufferedReader(new InputStreamReader(
                connection.getInputStream()));
        final String response = reader.readLine();

        // close resources
        writer.close();
        reader.close();

        if (response == null || response.startsWith("ERR")) {
            throw new IOException(response); // Throw the exception
        }
    }

    /**
     * Check if mineshafter is present. If it is, we need to bypass it to send
     * POST requests
     * 
     * @return true if mineshafter is installed on the server
     */
    private boolean isMineshafterPresent() {
        try {
            Class.forName("mineshafter.MineServer");
            return true;
        } catch (Exception e) {
            return false;
        }
    }

    /**
     * <p>
     * Encode a key/value data pair to be used in a HTTP post request. This
     * INCLUDES a & so the first key/value pair MUST be included manually, e.g:
     * </p>
     * <code>
     * StringBuffer data = new StringBuffer();
     * data.append(encode("guid")).append('=').append(encode(guid));
     * encodeDataPair(data, "version", description.getVersion());
     * </code>
     * 
     * @param buffer
     *            the stringbuilder to append the data pair onto
     * @param key
     *            the key value
     * @param value
     *            the value
     */
    private static void encodeDataPair(final StringBuilder buffer,
            final String key, final String value)
            throws UnsupportedEncodingException {
        buffer.append('&').append(encode(key)).append('=')
                .append(encode(value));
    }

    /**
     * Encode text as UTF-8
     * 
     * @param text
     *            the text to encode
     * @return the encoded text, as UTF-8
     */
    private static String encode(final String text)
            throws UnsupportedEncodingException {
        return URLEncoder.encode(text, "UTF-8");
    }
}