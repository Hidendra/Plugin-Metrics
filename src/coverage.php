<?php
// Emits JSON
define('ROOT', './');

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

if (!isset($_GET['hours']))
{
    exit('ERR No amount of days provided.');
}

// Amount of days to go back in past
$hours = intval($_GET['hours']);

if ($hours <= 0 || $hours > 744)
{
    exit('ERR Not supported.');
}


$json = array();

// Calculate the closest hour
$denom = 60 * 60; // 60 minutes * 60 seconds = 3600 seconds in an hour
$baseEpoch = round(time() / $denom) * $denom;

for ($hour = $hours - 1; $hour >= 0; $hour --)
{
    // calculate the range
    $minimum = strtotime('-' . $hour . ' hours', $baseEpoch);
    $maximum = strtotime('+1 hour', $minimum);

    // get the amount of servers that changed
    $servers = $plugin->getTimelineServers($minimum, $maximum);
    $players = $plugin->getTimelinePlayers($minimum, $maximum);

    // store it if they aren't -1
    if ($servers != -1 && $players != -1)
    {
        $json[] = array('epoch' => $minimum, 'servers' => $servers, 'players' => $players);
    }
}

// output the json
echo json_encode($json);