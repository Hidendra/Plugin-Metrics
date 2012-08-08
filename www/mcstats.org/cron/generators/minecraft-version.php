<?php

define('ROOT', '../public_html/');
define('MAX_CHILDREN', 30);

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

// the current number of running forks
$running_processes = 0;

// Load all of the countries we can use
$countries = loadCountries();
$baseEpoch = normalizeTime();
$minimum = strtotime('-30 minutes', $baseEpoch);

$minecraftVersions = array();

$statement = get_slave_db_handle()->prepare('SELECT distinct MinecraftVersion FROM Server');
$statement->execute();

while ($row = $statement->fetch())
{
    $minecraftVersions[] = $row[0];
}

// iterate through all of the plugins
$plugins = loadPlugins(PLUGIN_ORDER_POPULARITY);
$plugins[] = loadPluginByID(GLOBAL_PLUGIN_ID);
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

        foreach ($minecraftVersions as $minecraftVersion)
        {
            // load the players online in the last hour
            if ($plugin->getID() != GLOBAL_PLUGIN_ID)
            {
                $statement = get_slave_db_handle()->prepare('
                    SELECT
                        SUM(1) AS Sum,
                        COUNT(dev.Server) AS Count,
                        AVG(1) AS Avg,
                        MAX(1) AS Max,
                        MIN(1) AS Min,
                        VAR_SAMP(1) AS Variance,
                        STDDEV_SAMP(1) AS StdDev
                    FROM (SELECT DISTINCT Server, Server.Players from ServerPlugin LEFT OUTER JOIN Server ON Server.ID = ServerPlugin.Server WHERE MinecraftVersion = ? AND ServerPlugin.Plugin = ? AND ServerPlugin.Updated >= ?) dev');
                $statement->execute(array($minecraftVersion, $plugin->getID(), $minimum));
            } else
            {
                $statement = get_slave_db_handle()->prepare('
                    SELECT
                        SUM(1) AS Sum,
                        COUNT(dev.Server) AS Count,
                        AVG(1) AS Avg,
                        MAX(1) AS Max,
                        MIN(1) AS Min,
                        VAR_SAMP(1) AS Variance,
                        STDDEV_SAMP(1) AS StdDev
                    FROM (SELECT DISTINCT Server, Server.Players from ServerPlugin LEFT OUTER JOIN Server ON Server.ID = ServerPlugin.Server WHERE MinecraftVersion = ? AND ServerPlugin.Updated >= ?) dev');
                $statement->execute(array($minecraftVersion, $minimum));
            }

            $data = $statement->fetch();
            $sum = $data['Sum'];
            $count = $data['Count'];
            $avg = $data['Avg'];
            $max = $data['Max'];
            $min = $data['Min'];
            $variance = $data['Variance'];
            $stddev = $data['StdDev'];

            if ($count == 0)
            {
                continue;
            }

            // these can be NULL IFF there is only one data point (e.g one server) in the sample
            // we're using sample functions NOT population so this should be fairly obvious why
            // this will return null
            if ($variance === null || $stddev === null)
            {
                $variance = 0;
                $stddev = 0;
            }

            $graph = $plugin->getOrCreateGraph('Minecraft Version', false, 1, GraphType::Pie, TRUE, 9000);
            $columnID = $graph->getColumnID($minecraftVersion);

            // insert it into the database
            $statement = $master_db_handle->prepare('INSERT INTO CustomDataTimelineScratch (Plugin, ColumnID, Sum, Count, Avg, Max, Min, Variance, StdDev, Epoch)
                                                    VALUES (:Plugin, :ColumnID, :Sum, :Count, :Avg, :Max, :Min, :Variance, :StdDev, :Epoch)');
            $statement->execute(array(
                ':Plugin' => $plugin->getID(),
                ':ColumnID' => $columnID,
                ':Epoch' => $baseEpoch,
                ':Sum' => $sum,
                ':Count' => $count,
                ':Avg' => $avg,
                ':Max' => $max,
                ':Min' => $min,
                ':Variance' => $variance,
                ':StdDev' => $stddev
            ));
        }

        exit(0);
    }
}

// wait for all of the processes to finish
while ($running_processes > 0)
{
    pcntl_wait($status);
    $running_processes --;
}