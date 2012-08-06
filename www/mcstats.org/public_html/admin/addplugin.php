<?php

define('ROOT', '../');
session_start();

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

ensure_loggedin();

send_header();

if (isset($_POST['submit']))
{
    $pluginName = $_POST['pluginName'];
    $dbo = $_POST['dbo'];
    $email = $_POST['email'];

    $plugin = loadPlugin($pluginName);

    if ($plugin === NULL)
    {
        err('Invalid plugin.');
        send_add_plugin(htmlentities($pluginName), htmlentities($email));
    } else
    {
        // check if they already have access to it
        $accessible = get_accessible_plugins();
        $hasPlugin = FALSE;

        foreach ($accessible as $accessiblePlugin)
        {
            if ($plugin->getID() == $accessiblePlugin->getID())
            {
                $hasPlugin = TRUE;
                break;
            }
        }

        if ($hasPlugin)
        {
            err(sprintf('You already own the plugin <b>%s</b>!', htmlentities($plugin->getName())));
            send_add_plugin(htmlentities($plugin->getName()), htmlentities($email));
        } else
        {
            $uid = $_SESSION['uid'];
            $statement = get_slave_db_handle()->prepare('SELECT Created FROM PluginRequest WHERE Author = ? AND Plugin = ?');
            $statement->execute(array($uid, $plugin->getID()));

            if ($row = $statement->fetch())
            {
                $created = $row['Created'];
                err(sprintf('Your ownership request for <b>%s</b> is still pending approval, which was submitted at <b>%s</b>', htmlentities($plugin->getName()), date('H:i T D, F d', $created)));
                send_add_plugin(htmlentities($plugin->getName()), htmlentities($email), htmlentities($dbo));
            } else
            {
                $statement = $master_db_handle->prepare('INSERT INTO PluginRequest (Author, Plugin, Email, DBO, Created) VALUES (?, ?, ?, ?, UNIX_TIMESTAMP())');
                $statement->execute(array($uid, $plugin->getID(), $email, $dbo));

                success(sprintf('Successfully requested ownership of the plugin <b>%s</b>!', htmlentities($plugin->getName())));
            }
        }
    }
}
else
{
    send_add_plugin();
}

send_footer();

function send_add_plugin($plugin = '', $email = '', $dbo = '')
{
    echo '
            <script type="text/javascript">
                $(document).ready(function() {
                    var LOCK = false;
                    var LOOKUP_CACHE = new Object();

                    function checkPlugin(plugin, callback) {
                        if (plugin == "") {
                            return;
                        }

                        LOCK = true;

                        // is it not cached already ?
                        var value = LOOKUP_CACHE[plugin];
                        if (value != null) {
                            console.log("Cache hit for " + plugin + " (" + value + ")");
                            LOCK = false;
                            return value;
                        }

                        console.log("Checking plugin " + plugin);

                        $.get("/test/plugin.php?plugin=" + plugin, function(data) {
                            var pluginExists = parseInt(data) == 1;
                            LOOKUP_CACHE[plugin] = pluginExists;
                            console.log("Server returned " + pluginExists + " for plugin " + plugin);

                            // check if the plugin name changed in the interim
                            var currentPlugin = $("#pluginName").val();
                            if (currentPlugin != plugin) {
                                checkPlugin(currentPlugin, callback);
                            }

                            LOCK = false;
                            callback(pluginExists);
                        });
                    }

                    $("#pluginName").keyup(function() {
                        if (LOCK) {
                            return;
                        }

                        var pluginName = $("#pluginName").val();
                        checkPlugin(pluginName, function(exists) {
                            if (exists) {
                                $("#submit").removeAttr("disabled");
                                $("#pluginName-icon").removeClass("fam-cancel");
                                $("#pluginName-icon").addClass("fam-accept");
                            } else {
                                $("#submit").attr("disabled", "disabled");
                                $("#pluginName-icon").removeClass("fam-accept");
                                $("#pluginName-icon").addClass("fam-cancel");
                            }
                        });
                    });

                    // check the plugin currently in the textbox
                    checkPlugin($("#pluginName").val(), function(exists) {
                            if (exists) {
                                $("#submit").removeAttr("disabled");
                                $("#pluginName-icon").removeClass("fam-cancel");
                                $("#pluginName-icon").addClass("fam-accept");
                            } else {
                                $("#submit").attr("disabled", "disabled");
                                $("#pluginName-icon").removeClass("fam-accept");
                                $("#pluginName-icon").addClass("fam-cancel");
                            }
                        });
                });
            </script>

            <div class="row-fluid" style="margin-left: 25%;">

                <div style="width: 50%;">
                    <p style="font-size:18px; font-weight:200; line-height:27px; text-align: center;">
                        <b>NOTE:</b> All admin plugin additions are manually processed. <br/> If you enter an email address you will be notified via email once it has been processed.
                    </p>

                    <div class="well">
                        <div style="margin-left: 25%;">
                            <form action="" method="post" class="form-horizontal">
                                <div class="control-group">
                                    <label class="control-label" for="pluginName">Plugin Name</label>
                                    <div class="controls">
                                        <div class="input-prepend">
                                            <span class="add-on"><i class="fam-cancel" id="pluginName-icon"></i></span><input type="text" name="pluginName" id="pluginName" value="' . $plugin . '" />
                                        </div>
                                    </div>
                                </div>

                                <div class="control-group">
                                    <label class="control-label" for="dbo">dev.bukkit.org entry or forum post (optional)</label>
                                    <div class="controls">
                                        <input type="text" name="dbo" value="' . $dbo . '" />
                                    </div>
                                </div>

                                <div class="control-group">
                                    <label class="control-label" for="email">Email address (optional)</label>
                                    <div class="controls">
                                        <input type="text" name="email" value="' . $email . '" />
                                    </div>
                                </div>

                                <div class="control-group">
                                    <div class="controls">
                                        <input type="submit" name="submit" value="Submit" id="submit" class="btn btn-success btn-large" style="width: 100px;" disabled />
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
';
}