<?php

define('ROOT', '../public_html/');

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

// number of plugins converted
$index = 0;

$plugins = loadPlugins(PLUGIN_ORDER_ALPHABETICAL);
$total = count($plugins);

// countries
$countries = loadCountries();

// iterate through all of the plugins

$statement = $master_db_handle->prepare('INSERT INTO CustomDataTimeline (Plugin, ColumnID, Sum, Count, Avg, Max, Min, Variance, StdDev, Epoch)
                                            SELECT Plugin, :ColumnID, Servers, 0, 0, 0, 0, 0, 0, Epoch FROM CountryTimeline where Plugin = :Plugin AND Country = :ShortCode');
foreach ($plugins as $plugin)
{

    $index++;
    echo sprintf('[%d%%] Converting %s from CountryTimeline to the unified graphing format ..%s', floor(($index / $total) * 100), $plugin->getName(), PHP_EOL);

    // get or create the graph
    $serverlocations = $plugin->getOrCreateGraph('Server Locations', false, 1, GraphType::Pie, TRUE);

    $master_db_handle->beginTransaction();
    foreach ($countries as $shortCode => $countryName) {
        // get the column id
        $columnID = $serverlocations->getColumnID($countryName);

        // convert all of it
        $statement->execute(array(':Plugin' => $plugin->getID(), ':ColumnID' => $columnID, ':ShortCode' => $shortCode));
    }
    $master_db_handle->commit();

    echo sprintf('Converted %s%s', $plugin->getName(), PHP_EOL);
}

echo sprintf('Converted %d plugins%s', count($plugins), PHP_EOL);