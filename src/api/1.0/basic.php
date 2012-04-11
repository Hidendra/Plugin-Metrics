<?php
define('ROOT', '../../');

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

// Our json encoded response
$response = array();

if (!isset($_GET['plugin']))
{
    $response['msg'] = 'No plugin provided';
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

// Add some basic data
$response['name'] = $plugin->getName(); // resend them the name so it is case-correct
$response['author'] = $plugin->getAuthors();
$response['starts'] = $plugin->getGlobalHits();

// Server data
$response['servers'][24] = $plugin->countServersLastUpdated(time() - SECONDS_IN_DAY);


$response['status'] = 'ok';
echo json_encode($response);