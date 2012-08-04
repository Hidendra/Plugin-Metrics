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
    echo sprintf('[%d%%] Estimating created date for %s ..%s', floor(($converted / $total) * 100), $plugin->getName(), PHP_EOL);

    $statement = get_slave_db_handle()->prepare('SELECT Min(Epoch) FROM CustomDataTimeline where Plugin = ?');
    $statement->execute(array($plugin->getID()));

    if ($row = $statement->fetch())
    {
        $plugin->setCreated($row[0]);
    } else
    {
        echo sprintf('%s has no available data to estimate from, using current time%s', $plugin->getName(), PHP_EOL);
        $plugin->setCreated(time());
    }

    // save it
    $plugin->save();
    $converted ++;
}