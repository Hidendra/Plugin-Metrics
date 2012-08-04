<?php

define('ROOT', '../public_html/');

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

// number of plugins converted
$converted = 0;

$plugins = loadPlugins(PLUGIN_ORDER_ALPHABETICAL);
$total = count($plugins);

foreach ($plugins as $plugin)
{
    echo sprintf('[%d%%] Ordering graphs for %s ..%s', floor(($converted / $total) * 100), $plugin->getName(), PHP_EOL);
    $plugin->orderGraphs();
    $converted ++;
}