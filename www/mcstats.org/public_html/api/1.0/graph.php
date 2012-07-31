<?php
define('ROOT', '../../');

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

// Fine-tune this or allow customizations?
// This is 1 week
$hours = 168;

// Our json encoded response
$response = array();

if (!isset($_GET['plugin']))
{
    $response['msg'] = 'No plugin provided';
    $response['status'] = 'err';
    exit(json_encode($response));
}

if (!isset($_GET['graph']))
{
    $response['msg'] = 'No graph name provided';
    $response['status'] = 'err';
    exit(json_encode($response));
}

$plugin = loadPlugin($_GET['plugin']);

if ($plugin === NULL)
{
    $response['msg'] = 'Invalid plugin';
    $response['status'] = 'err';
    exit(json_encode($response));
}

// Decide which graph they want
switch (strtolower($_GET['graph']))
{
        case 'global':
        // load the plugin's stats graph
        $globalstatistics = $plugin->getOrCreateGraph('Global Statistics');
        // the player plot's column id
        $playersColumnID = $globalstatistics->getColumnID('Players');
        // server plot's column id
        $serversColumnID = $globalstatistics->getColumnID('Servers');

        $response['status'] = 'ok';
        $response['data']['players'] = DataGenerator::generateCustomChartData($globalstatistics, $playersColumnID, $hours);
        $response['data']['servers'] = DataGenerator::generateCustomChartData($globalstatistics, $serversColumnID, $hours);
        break;

    case 'country':
        // $response['status'] = 'ok';
        // $response['data'] = DataGenerator::generateCountryChartData($plugin);
        $response['status'] = 'err';
        $response['data'] = 'Support for this has been removed for now';
        break;

    case 'players':
        // load the plugin's stats graph
        $globalstatistics = $plugin->getOrCreateGraph('Global Statistics');
        // the player plot's column id
        $playersColumnID = $globalstatistics->getColumnID('Players');

        $response['status'] = 'ok';
        $response['data'] = DataGenerator::generateCustomChartData($globalstatistics, $playersColumnID, $hours);
        break;

    case 'servers':
        // load the plugin's stats graph
        $globalstatistics = $plugin->getOrCreateGraph('Global Statistics');
        // server plot's column id
        $serversColumnID = $globalstatistics->getColumnID('Servers');

        $response['status'] = 'ok';
        $response['data'] = DataGenerator::generateCustomChartData($globalstatistics, $serversColumnID, $hours);
        break;

    default:
        $response['msg'] = 'Invalid graph type';
        $response['status'] = 'err';
        break;

}

echo json_encode($response, JSON_NUMERIC_CHECK);