<?php

define('ROOT', '../');
session_start();

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

ensure_loggedin();

$container_class = 'container';
send_header();
?>

<div class="hero-unit">
    <h1 style="margin-bottom:10px; font-size:57px;">Welcome!</h1>
    <p>
        To the left you will find the plugins you have access to. From there you will be able to
        change settings and graphing options for your plugin. If you should require access to another
        plugin, please contact Hidendra directly, preferably through irc: irc.esper.net #metrics
    </p>
    <p>
        <a class="btn btn-primary btn-large" href="/plugin-list/" target="_blank">Add A Plugin &raquo;</a>
    </p>
</div>

<?php

send_footer();