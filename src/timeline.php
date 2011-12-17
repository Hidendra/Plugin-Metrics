<?php
// Emits JSON

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

if (!isset($_GET['days']))
{
    exit('ERR No amount of days provided.');
}

// Amount of days to go back in past
$days = intval($_GET['days']);

if ($days <= 0 || $days > 60)
{
    exit('ERR Not supported.');
}


$json = array();

// Calculate midnight
$midnight = mktime(0, 0, 0);

for ($day = $days - 1; $day >= 0; $day --)
{
    // calculate the range
    $minimum = strtotime('-' . $day . ' days', $midnight);
    $maximum = strtotime('+1 day', $minimum);

    // get the amount of servers that changed
    $changes = $plugin->countVersionChanges($minimum, $maximum);

    // store it
    $json[] = array('epoch' => $minimum, 'changes' => $changes);
}

// output the json
echo json_encode($json);