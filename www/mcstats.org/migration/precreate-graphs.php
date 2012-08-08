<?php

define('ROOT', '../public_html/');

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

// number of plugins converted
$converted = 0;

$plugins = loadPlugins(PLUGIN_ORDER_ALPHABETICAL);
$total = count($plugins);

// switch to memory
$statement = $master_db_handle->prepare('ALTER TABLE Graph Engine = MEMORY');
$statement->execute();

// iterate through all of the plugins
foreach ($plugins as $plugin)
{
    echo sprintf('[%d%%] Precreating graphs for %s ..%s', floor(($converted / $total) * 100), $plugin->getName(), PHP_EOL);

    // get or create the graph
    $plugin->getOrCreateGraph('Global Statistics', false, 1, GraphType::Area, TRUE, 1);
    $plugin->getOrCreateGraph('Server Locations', false, 1, GraphType::Pie, TRUE, 9002);
    $plugin->getOrCreateGraph('Version Trends', false, 1, GraphType::Area, TRUE, 9003);
    $plugin->getOrCreateGraph('Minecraft Version', false, 1, GraphType::Pie, TRUE, 9000);
    $plugin->getOrCreateGraph('Server Software', false, 1, GraphType::Pie, TRUE, 9001);

    $converted ++;
}

// switch back
echo sprintf('Converting Graph table back to InnoDB ..%s', PHP_EOL);
$statement = $master_db_handle->prepare('ALTER TABLE Graph Engine = InnoDB');
$statement->execute();

echo sprintf('Converted %d plugins%s', $converted, PHP_EOL);