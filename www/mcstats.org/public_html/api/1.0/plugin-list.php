<?php
define('ROOT', '../../');

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

// Our json encoded response
$response = array();

if (!isset($_GET['page']))
{
    $page = 1;
} else
{
    $page = intval($_GET['page']);
}

// get the total number of plugins
$totalPlugins = count(loadPlugins(PLUGIN_ORDER_POPULARITY));
$response['maxPages'] = ceil($totalPlugins / PLUGIN_LIST_RESULTS_PER_PAGE);

// offset is how many plugins to start after
$offset = ($page - 1) * PLUGIN_LIST_RESULTS_PER_PAGE;

$step = 1;
foreach (loadPlugins(PLUGIN_ORDER_POPULARITY, PLUGIN_LIST_RESULTS_PER_PAGE, $offset) as $plugin)
{
    if ($plugin->isHidden()) {
        continue;
    }

    // calculate this plugin's rank
    $rank = $offset + $step;

    // count the number of servers in the last 24 hours
    $servers24 = $plugin->countServersLastUpdated(normalizeTime() - SECONDS_IN_DAY);

    // add the plugin
    $response['plugins'][] = array(
        'rank' => $rank,
        'name' => htmlentities($plugin->getName()),
        'authors' => htmlentities($plugin->getAuthors()),
        'servers24' => number_format($servers24)
    );

    $step ++;
}

$response['status'] = 'ok';
echo json_encode($response);