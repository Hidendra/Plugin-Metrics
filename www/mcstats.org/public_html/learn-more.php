<?php
define('ROOT', './');
session_start();

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';
require_once ROOT . 'includes/markdown.php';

/// Templating
$page_title = 'Plugin Metrics :: Why MCStats?';
// $container_class = 'container';
send_header();

echo <<<END

<div class="row-fluid" style="margin-left: 25%; text-align: center;">
    <div class="span6" style="width: 50%;">
        <h1 style="margin-bottom:30px; font-size:40px;">
            MCStats is a unique service that is entirely open.
        </h1>
    </div>
</div>

<div class="row-fluid" style="margin-left: 25%;">
    <div class="span6 well" style="width: 50%;">
        <p style="font-size: 16px;">
            MCStats is <b>free</b>, <b>open source</b> and <b>anonymous</b>. All data is public and freely available for every plugin.
        </p>
        <p>
            The project started as a means to create an open source stats system for <i>LWC</i>. I wanted to share this with
            any other author, too, and so I slowly built the system up. It has became very powerful today and for that I am
            very proud of what has been done already.
        </p>
        <p>
            Some plugins out there use a significantly less powerful system for tracking plugin usage but <b><i>they do not
            tell you about it</i></b> nor can you see the code that is being used, so you can never be sure they're not doing
            something bad.
        </p>
        <p>
            While MCStats forces plugins to show their data to everyone, it also means they are proud to show <i>you</i>
            data they're collecting with it. This is a step in the right direction and something I believe all authors
            should strive for: <i>transparency</i>.
        </p>
        <p style="text-align: center;">
            <img src="/plugin-preview/all+servers.png" />
        </p>
        <p style="font-size: 16px;">
            <b>IRC:</b> <code>irc.esper.net #metrics</code>
        </p>
    </div>
</div>

<div class="row-fluid" style="margin-left: 16%;">
    <div class="span4" style="width: 33%;">
        <h1 style="margin-bottom:10px; font-size:28px; text-align: center;">
            Plugin Authors
        </h1>
    </div>

    <div class="span4" style="width: 33%;">
        <h1 style="margin-bottom:10px; font-size:28px; text-align: center;">
            Server Owners
        </h1>
    </div>
</div>

<div class="row-fluid" style="margin-left: 16%;">

    <div class="span4 well" style="width: 33%;">
        <p>
            It is very easy to add MCStats / Plugin Metrics to your plugin. You can be up and running in less than 5 minutes and
            you will have immediate access to everything that is available.
        </p>
        <p style="text-align: center;">
            <a href="/admin/" class="btn btn-success" target="_blank"><i class="icon-white icon-heart"></i> Register / Login</a>
        </p>
        <p>
            If you run into any troubles or have a question please do visit us in IRC or email me directly: <code>hidendra [at] mcstats.org</code>
        </p>
    </div>

    <div class="span4 well" style="width: 33%;">
        <p>
            For this service to track a plugin, an author must explicitly add it to their plugin. They will most likely have made
            note of this addition in any changelogs. Your server <i>cannot</i> be identified nor controlled in any way by MCStats.
            As well, <b>you have access to the same data that the plugin author can see</b>.
        </p>

        <p>
            You are free to opt-out of submitting data whenever you wish. This will immediately stop sending data for any plugins that supports MCStats / Plugin Metrics.
            Simply edit <code>plugins/PluginMetrics/config.yml</code> and change <code>opt-out: false</code> to <code>true</code>
        </p>

        <p>
            If you have any questions at all about how this service operates or anything else, please do visit us in IRC or email me directly: <code>hidendra [at] mcstats.org</code>
        </p>
    </div>

</div>

END;

send_footer();

?>