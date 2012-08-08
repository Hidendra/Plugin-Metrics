<?php

define('ROOT', '../public_html/');
define('MAX_CHILDREN', 30);

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

// the current number of running forks
$running_processes = 0;

// number of plugins converted
$converted = 0;

$plugins = loadPlugins(PLUGIN_ORDER_ALPHABETICAL);
$total = count($plugins);

// iterate through all of the plugins
foreach ($plugins as $plugin)
{
    // are we at the process limit ?
    if ($running_processes >= MAX_CHILDREN)
    {
        // wait for some children to be allocated
        pcntl_wait($status);
        $running_processes --;
    }

    $running_processes ++;
    $pid = pcntl_fork();

    if ($pid == 0)
    {
        $master_db_handle = try_connect_database();
        echo sprintf('[%d%%] Converting %s from VersionTimeline to the unified graphing format ..%s', floor(($converted / $total) * 100), $plugin->getName(), PHP_EOL);

        // get or create the graph
        $versiontrends = $plugin->getOrCreateGraph('Version Trends', false, 1, GraphType::Area, TRUE);

        foreach ($plugin->getVersions() as $versionID => $versionName)
        {
            // get the column id
            $columnID = $versiontrends->getColumnID($versionName);

            // convert all of it
            $statement = $master_db_handle->prepare('INSERT INTO CustomDataTimeline (Plugin, ColumnID, Sum, Count, Avg, Max, Min, Variance, StdDev, Epoch)
                                            SELECT Plugin, :ColumnID, Count, 0, 0, 0, 0, 0, 0, Epoch FROM VersionTimeline where Plugin = :Plugin AND Version = :VersionID');
            $statement->execute(array(':Plugin' => $plugin->getID(), ':ColumnID' => $columnID, ':VersionID' => $versionID));
        }

        $converted ++;
        exit(0);
    }

}

// wait for all of the processes to finish
while ($running_processes > 0)
{
    pcntl_wait($status);
    $running_processes --;
}

echo sprintf('Converted %d plugins%s', $converted, PHP_EOL);