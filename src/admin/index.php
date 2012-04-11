<?php

define('ROOT', '../');
session_start();

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

ensure_loggedin();

// TODO functions to include them?
send_header();
?>

            <div class="row-fluid">
<?php send_admin_sidebar(); ?>

                <div class="span8" id="plugin-content">

                        <div class="hero-unit">
                            <h1>Welcome!</h1>
                            <p>
                                To the left you will find the plugins you have access to. From there you will be able to
                                change settings and graphing options for your plugin. If you should require access to another
                                plugin, please contact Hidendra directly, preferably through irc: irc.esper.net #metrics
                            </p>
                        </div>

                </div>

            </div>
<?php


send_footer();