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

// countries
$countries = loadCountries();

$statement = $master_db_handle->prepare('INSERT INTO CustomDataTimeline (Plugin, ColumnID, Sum, Count, Avg, Max, Min, Variance, StdDev, Epoch)
                                            SELECT Plugin, :ColumnID, Servers, 0, 0, 0, 0, 0, 0, Epoch FROM CountryTimeline where Plugin = :Plugin AND Country = :ShortCode AND Servers > 0');

// iterate through all of the plugins
foreach ($plugins as $plugin)
{
    echo sprintf('[%d%%] Converting %s from CountryTimeline to the unified graphing format ..%s', floor(($converted / $total) * 100), $plugin->getName(), PHP_EOL);

    // get or create the graph
    $serverlocations = $plugin->getOrCreateGraph('Server Locations', false, 1, GraphType::Pie, TRUE);

    foreach ($countries as $shortCode => $countryName)
    {
        // get the column id
        $columnID = $serverlocations->getColumnID($countryName);

        // convert all of it
        $statement->execute(array(':Plugin' => $plugin->getID(), ':ColumnID' => $columnID, ':ShortCode' => $shortCode));
    }

    $converted ++;
}

echo sprintf('Converted %d plugins%s', $converted, PHP_EOL);