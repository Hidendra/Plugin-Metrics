<?php
// Emits JSON
define('ROOT', '../');

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

// The key to use in the cache
$cache_key = 'plugin-' . $plugin->getID() . '-coverage';

// Is it cached?
if ($ret = $cache->get($cache_key))
{
    exit($ret);
}

if (!isset($_GET['hours']))
{
    exit('ERR No amount of hours provided.');
}

// Amount of days to go back in past
$hours = intval($_GET['hours']);

if ($hours <= 0 || $hours > 744)
{
    exit('ERR Not supported.');
}


$json = array();

// calculate the minimum
$baseEpoch = normalizeTime();
$minimum = strtotime('-' . $hours . ' hours', $baseEpoch);
$maximum = $baseEpoch;

// load the data from mysql
$servers = $plugin->getTimelineServers($minimum, $maximum);
$players = $plugin->getTimelinePlayers($minimum, $maximum);

// go through each and add to json
foreach ($servers as $epoch => $count)
{
    // if we're missing even one data point, continue on
    if (!isset($players[$epoch]))
    {
        continue;
    }

    $json[] = array($epoch, $count, $players[$epoch]);
}

// output the json
$output = json_encode($json, JSON_NUMERIC_CHECK);
echo $output;
$cache->set($cache_key, $output, timeUntilNextGraph() - time());