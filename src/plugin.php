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
        <style>
            table
            {
                font-family: "Lucida Sans Unicode", "Lucida Grande", Sans-Serif;
                font-size: 12px;
                background: #fff;
                border-collapse: collapse;
                text-align: left;
            }
            table th
            {
                font-size: 14px;
                font-weight: normal;
                color: #039;
                padding: 10px 8px;
                border-bottom: 2px solid #6678b1;
            }
            table td
            {
                color: #669;
                padding: 9px 8px 0px 8px;
            }
            table tbody tr:hover td
            {
                color: #009;
            }
        </style>
    </head>

    <body>
        <h3>Plugin information</h3>
        <table>
            <tr> <td> Name </td> <td> ' . $name . ' </td> </tr>
            <tr> <td> Global starts </td> <td> ' . number_format($plugin->getGlobalHits()) . ' </td> </tr>
        </table>

        <h3>Servers using ' . $name . '</h3>
        <table>
            <tr> <td> Total </td> <td> ' . number_format($plugin->countServers()) . ' </td> </tr>
            <tr> <td> Last 24 hrs </td> <td> ' . number_format($plugin->countServersLastUpdatedAfter(time() - MILLISECONDS_IN_DAY)) . ' </td> </tr>
            <tr> <td> Last 7 days </td> <td> ' . number_format($plugin->countServersLastUpdatedAfter(time() - MILLISECONDS_IN_WEEK)) . ' </td> </tr>
            <tr> <td> This month </td> <td> ' . number_format($plugin->countServersLastUpdatedAfter(strtotime(date('m').'/01/'.date('Y').' 00:00:00'))) . ' </td> </tr>
        </table>

        <h3>Servers\' last known version</h3>
        <table>
';

foreach ($plugin->getVersions() as $version)
{
    echo '            <tr> <td>' . $version . '</td> <td>' . $plugin->countServersUsingVersion($version) . '</td> </tr>
';
}
?>
        </table>
    </body>
</html>