<?php
define('ROOT', './');
session_start();

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

if (!isset($_GET['plugin']))
{
    exit('ERR No plugin provided.');
}

// Load the plugin
$plugin = loadPlugin($_GET['plugin']);

// Doesn't exist
if ($plugin === NULL)
{
    exit('ERR Invalid plugin.');
}

$name = $plugin->getName();
echo '
<html>
    <head>
        <title>' . $name . ' statistics</title>
    </head>

    <body>
        <h3>Plugin information</h3>
        Name: ' . $name . ' <br/>
        Global starts: ' . number_format($plugin->getGlobalHits()) . ' <br/>

        <h3>Servers using ' . $name . '</h3>
        Total: ' . number_format($plugin->countServers()) . ' <br/>
        Last 24 hrs: ' . number_format($plugin->countServersLastUpdatedAfter(time() - MILLISECONDS_IN_DAY)) . ' <br/>
        Last 7 days: ' . number_format($plugin->countServersLastUpdatedAfter(time() - MILLISECONDS_IN_WEEK)) . ' <br/>
        This month: ' . number_format($plugin->countServersLastUpdatedAfter(strtotime(date('m').'/01/'.date('Y').' 00:00:00'))) . ' <br/>

        <h3>Servers\' last known version</h3>
';

foreach ($plugin->getVersions() as $version)
{
    echo '        <b>' . $version . '</b>: ' . $plugin->countServersUsingVersion($version) . ' <br/>
';
}
?>
    </body>
</html>