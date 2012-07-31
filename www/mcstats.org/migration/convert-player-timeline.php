<?php

define('ROOT', '../public_html/');
define('MAX_CHILDREN', 30);

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

// the current number of running forks
$running_processes = 0;

$baseEpoch = normalizeTime();
$minimum = strtotime('-30 minutes', $baseEpoch);

// number of plugins converted
$converted = 0;

$plugins = loadPlugins(PLUGIN_ORDER_ALPHABETICAL);
$total = count($plugins);

// iterate through all of the plugins
foreach ($plugins as $plugin)
{
    echo sprintf('[%d%%] Converting %s from PlayerTimeline to the unified graphing format ..%s', floor(($converted / $total) * 100), $plugin->getName(), PHP_EOL);

    // get or create the graph
    $globalstats = $plugin->getOrCreateGraph('Global Statistics', false, 1, GraphType::Area, TRUE);
    // get the column id
    $columnID = $globalstats->getColumnID('Players');

    // convert all of it
    $statement = $master_db_handle->prepare('INSERT INTO CustomDataTimeline (Plugin, ColumnID, Sum, Count, Avg, Max, Min, Variance, StdDev, Epoch)
                                            SELECT Plugin, :ColumnID, Players, 0, 0, 0, 0, 0, 0, Epoch FROM PlayerTimeline where Plugin = :Plugin');
    $statement->execute(array(':Plugin' => $plugin->getID(), ':ColumnID' => $columnID));
    $converted ++;
}

echo sprintf('Converted %d plugins%s', $converted, PHP_EOL);