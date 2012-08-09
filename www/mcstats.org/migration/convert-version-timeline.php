<?php

define('ROOT', '../public_html/');

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

// number of plugins converted
$index = 0;

$plugins = loadPlugins(PLUGIN_ORDER_ALPHABETICAL);
$total = count($plugins);

// iterate through all of the plugins
$statement = $master_db_handle->prepare('INSERT INTO CustomDataTimeline (Plugin, ColumnID, Sum, Count, Avg, Max, Min, Variance, StdDev, Epoch)
                                            SELECT Plugin, :ColumnID, Count, 0, 0, 0, 0, 0, 0, Epoch FROM VersionTimeline where Plugin = :Plugin AND Version = :VersionID');
foreach ($plugins as $plugin) {
    $index++;
    echo sprintf('[%d%%] Converting %s from VersionTimeline to the unified graphing format ..%s', floor(($index / $total) * 100), $plugin->getName(), PHP_EOL);

    // get or create the graph
    $versiontrends = $plugin->getOrCreateGraph('Version Trends', false, 1, GraphType::Area, TRUE);

    $master_db_handle->beginTransaction();
    foreach ($plugin->getVersions() as $versionID => $versionName) {
        // get the column id
        $columnID = $versiontrends->getColumnID($versionName);

        // convert all of it
        $statement->execute(array(':Plugin' => $plugin->getID(), ':ColumnID' => $columnID, ':VersionID' => $versionID));
    }
    $master_db_handle->commit();

    echo sprintf('Converted %s%s', $plugin->getName(), PHP_EOL);

}

echo sprintf('Converted %d plugins%s', count($plugins), PHP_EOL);