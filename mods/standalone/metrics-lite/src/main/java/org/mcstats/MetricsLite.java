/*
 * Copyright 2011-2013 Tyler Blair. All rights reserved.
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
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.io.UnsupportedEncodingException;
import java.lang.reflect.Method;
import java.net.Proxy;
import java.net.URL;
import java.net.URLConnection;
import java.net.URLEncoder;
import java.util.Collections;
import java.util.HashSet;
import java.util.Iterator;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Properties;
import java.util.Set;
import java.util.UUID;

/**
 * <p> The metrics class obtains data about a plugin and submits statistics about it to the metrics backend. </p> <p>
 * Public methods provided by this class: </p>
 * <code>
 * Graph createGraph(String name); <br/>
 * void addCustomData(Plotter plotter); <br/>
 * void start(); <br/>
 * </code>
 */
public abstract class MetricsLite {

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
     * Interval of time to ping (in minutes)
     */
    private static final int PING_INTERVAL = 10;
    /**
     * Debug mode
     */
    private final boolean debug;
    /**
     * The plugin configuration file
     */
    private final Properties properties = new Properties();

    /**
     * The plugin's name
     */
    private final String pluginName;

    /**
     * The plugin's version
     */
    private final String pluginVersion;

    /**
     * The plugin configuration file
     */
    private final File configurationFile;
    /**
     * Unique server id
     */
    private final String guid;
    /**
     * Lock for synchronization
     */
    private final Object optOutLock = new Object();

    /**
     * The thread submission is running on
     */
    private Thread thread = null;

    public MetricsLite(String pluginName, String pluginVersion) throws IOException {
        if (pluginName == null || pluginVersion == null) {
            throw new IllegalArgumentException("Plugin cannot be null");
        }

        this.pluginName = pluginName;
        this.pluginVersion = pluginVersion;

        configurationFile = getConfigFile();

        if (!configurationFile.exists()) {
            if (configurationFile.getPath().contains("/") || configurationFile.getPath().contains("\\")) {
                File parent = new File(configurationFile.getParent());
                if (!parent.exists()) {
                    parent.mkdir();
                }
            }

            configurationFile.createNewFile(); // config file
            properties.put("opt-out", "false");
            properties.put("guid", UUID.randomUUID().toString());
            properties.put("debug", "false");
            properties.store(new FileOutputStream(configurationFile), "http://mcstats.org");
        } else {
            properties.load(new FileInputStream(configurationFile));
        }

        guid = properties.getProperty("guid");
        debug = Boolean.parseBoolean(properties.getProperty("debug"));
    }

    /**
     * Get the full server version
     *
     * @return
     */
    public abstract String getFullServerVersion();

    /**
     * Get the amount of players online
     *
     * @return
     */
    public abstract int getPlayersOnline();

    /**
     * Gets the File object of the config file that should be used to store data such as the GUID and opt-out status
     *
     * @return the File object for the config file
     */
    public abstract File getConfigFile();

    /**
     * Start measuring statistics. This will immediately create an async repeating task as the plugin and send the
     * initial data to the metrics backend, and then after that it will post in increments of PING_INTERVAL * 1200
     * ticks.
     *
     * @return True if statistics measuring is running, otherwise false.
     */
    public boolean start() {
        synchronized (optOutLock) {
            // Did we opt out?
            if (isOptOut()) {
                return false;
            }

            // Is metrics already running?
            if (thread != null) {
                return true;
            }

            thread = new Thread(new Runnable() {

                private boolean firstPost = true;
                private long nextPost = 0L;

                public void run() {
                    while (thread != null) {
                        if (nextPost == 0L || System.currentTimeMillis() > nextPost) {
                            try {
                                // This has to be synchronized or it can collide with the disable method.
                                synchronized (optOutLock) {
                                    // Disable Task, if it is running and the server owner decided to opt-out
                                    if (isOptOut() && thread != null) {
                                        Thread temp = thread;
                                        thread = null;
                                        temp.interrupt(); // interrupting ourselves
                                        return;
                                    }
                                }

                                // We use the inverse of firstPost because if it is the first time we are posting,
                                // it is not a interval ping, so it evaluates to FALSE
                                // Each time thereafter it will evaluate to TRUE, i.e PING!
                                postPlugin(!firstPost);

                                // After the first post we set firstPost to false
                                // Each post thereafter will be a ping
                                firstPost = false;
                                nextPost = System.currentTimeMillis() + (PING_INTERVAL * 60 * 1000);
                            } catch (IOException e) {
                                if (debug) {
                                    System.out.println("[Metrics] " + e.getMessage());
                                }
                            }
                        }

                        try {
                            Thread.sleep(100L);
                        } catch (InterruptedException e) { }
                    }
                }
            }, "MCStats / Plugin Metrics");
            thread.start();

            return true;
        }
    }

    /**
     * Has the server owner denied plugin metrics?
     *
     * @return true if metrics should be opted out of it
     */
    public boolean isOptOut() {
        synchronized (optOutLock) {
            try {
                // Reload the metrics file
                properties.load(new FileInputStream(configurationFile));
            } catch (IOException ex) {
                if (debug) {
                    System.out.println("[Metrics] " + ex.getMessage());
                }
                return true;
            }

            return Boolean.parseBoolean(properties.getProperty("opt-out"));
        }
    }

    /**
     * Enables metrics for the server by setting "opt-out" to false in the config file and starting the metrics task.
     *
     * @throws java.io.IOException
     */
    public void enable() throws IOException {
        // This has to be synchronized or it can collide with the check in the task.
        synchronized (optOutLock) {
            // Check if the server owner has already set opt-out, if not, set it.
            if (isOptOut()) {
                properties.setProperty("opt-out", "false");
                properties.store(new FileOutputStream(configurationFile), "http://mcstats.org");
            }

            // Enable Task, if it is not running
            if (thread == null) {
                start();
            }
        }
    }

    /**
     * Disables metrics for the server by setting "opt-out" to true in the config file and canceling the metrics task.
     *
     * @throws java.io.IOException
     */
    public void disable() throws IOException {
        // This has to be synchronized or it can collide with the check in the task.
        synchronized (optOutLock) {
            // Check if the server owner has already set opt-out, if not, set it.
            if (!isOptOut()) {
                properties.setProperty("opt-out", "true");
                properties.store(new FileOutputStream(configurationFile), "http://mcstats.org");
            }

            // Disable Task, if it is running
            if (thread != null) {
                thread.interrupt();
                thread = null;
            }
        }
    }

    /**
     * Generic method that posts a plugin to the metrics website
     */
    private void postPlugin(final boolean isPing) throws IOException {
        String serverVersion = getFullServerVersion();
        int playersOnline = getPlayersOnline();

        // END server software specific section -- all code below does not use any code outside of this class / Java

        // Construct the post data	
        final StringBuilder data = new StringBuilder();
        // The plugin's description file containg all of the plugin data such as name, version, author, etc
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
        encodeDataPair(data, "java_version", java_version);

        // If we're pinging, append it
        if (isPing) {
            encodeDataPair(data, "ping", "true");
        }

        // Create the url
        URL url = new URL(BASE_URL + String.format(REPORT_URL, encode(pluginName)));

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
        final OutputStreamWriter writer = new OutputStreamWriter(connection.getOutputStream());
        writer.write(data.toString());
        writer.flush();

        // Now read the response
        final BufferedReader reader = new BufferedReader(new InputStreamReader(connection.getInputStream()));
        final String response = reader.readLine();

        // close resources
        writer.close();
        reader.close();

        if (response == null || response.startsWith("ERR")) {
            throw new IOException(response); //Throw the exception
        }
    }

    /**
     * Check if mineshafter is present. If it is, we need to bypass it to send POST requests
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
     * <p>Encode a key/value data pair to be used in a HTTP post request. This INCLUDES a & so the first key/value pair
     * MUST be included manually, e.g:</p>
     * <code>
     * StringBuffer data = new StringBuffer();
     * data.append(encode("guid")).append('=').append(encode(guid));
     * encodeDataPair(data, "version", description.getVersion());
     * </code>
     *
     * @param buffer the stringbuilder to append the data pair onto
     * @param key    the key value
     * @param value  the value
     */
    private static void encodeDataPair(final StringBuilder buffer, final String key, final String value) throws UnsupportedEncodingException {
        buffer.append('&').append(encode(key)).append('=').append(encode(value));
    }

    /**
     * Encode text as UTF-8
     *
     * @param text the text to encode
     * @return the encoded text, as UTF-8
     */
    private static String encode(final String text) throws UnsupportedEncodingException {
        return URLEncoder.encode(text, "UTF-8");
    }

}