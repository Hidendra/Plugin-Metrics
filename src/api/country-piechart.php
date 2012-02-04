<?php
// Emits JSON
define('ROOT', '../');

define('MINIMUM_FOR_OTHERS', 15);

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

$json = array();
$countries = loadCountries();

// The epoch to lookup
$epoch = getLastHour();

// load the data from mysql
$servers = $plugin->getTimelineCountry($epoch);

// go through each and add to json
foreach ($servers as $epoch => $data)
{
    // Sort the server counts
    arsort(&$data);

    // Get the amount of servers we have
    $server_total = array_sum($data);

    // If it is bigger than MINIMUM_FOR_OTHERS, calculate what 'Others' would be
    $count = count($data);
    if ($count >= MINIMUM_FOR_OTHERS)
    {
        $others_total = 0;
        $index = 0;

        foreach ($data as $country => $amount)
        {
            $index ++;
            if ($index <= MINIMUM_FOR_OTHERS)
            {
                continue;
            }

            $others_total += $amount;
            unset($data[$country]);
        }

        // Set the 'Others' stat
        $data['Others'] = $others_total;

        // Sort again
        arsort(&$data);
    }

    // Begin emitting unadulterated JSON
    foreach ($data as $country => $amount)
    {
        $key = ($country == 'Others') ? $country : $countries[$country];
        $json[$key] = round(($amount / $server_total) * 100, 2);
    }

    // We only want 1
    break;
}

// output the json
echo json_encode($json);