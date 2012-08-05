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

// iterate through all of the plugins
foreach (loadPlugins(PLUGIN_ORDER_ALPHABETICAL) as $plugin)
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

        // load the players online in the last hour
        if ($plugin->getID() != GLOBAL_PLUGIN_ID)
        {
            $statement = get_slave_db_handle()->prepare('
                    SELECT
                        SUM(Players) AS Sum,
                        COUNT(*) AS Count,
                        AVG(Players) AS Avg,
                        MAX(Players) AS Max,
                        MIN(Players) AS Min,
                        VAR_SAMP(Players) AS Variance,
                        STDDEV_SAMP(Players) AS StdDev
                    FROM ServerPlugin LEFT OUTER JOIN Server ON Server.ID = ServerPlugin.Server WHERE ServerPlugin.Plugin = ? AND ServerPlugin.Updated >= ?');
            $statement->execute(array($plugin->getID(), $minimum));
        } else
        {
            $statement = get_slave_db_handle()->prepare('
                    SELECT
                        SUM(dev.Players) AS Sum,
                        COUNT(*) AS Count,
                        AVG(dev.Players) AS Avg,
                        MAX(dev.Players) AS Max,
                        MIN(dev.Players) AS Min,
                        VAR_SAMP(dev.Players) AS Variance,
                        STDDEV_SAMP(dev.Players) AS StdDev
                    FROM (SELECT DISTINCT Server, Server.Players from ServerPlugin LEFT OUTER JOIN Server ON Server.ID = ServerPlugin.Server WHERE ServerPlugin.Updated >= ?) dev;');
            $statement->execute(array($minimum));
        }

        $data = $statement->fetch();
        $sum = $data['Sum'];
        $count = $data['Count'];
        $avg = $data['Avg'];
        $max = $data['Max'];
        $min = $data['Min'];
        $variance = $data['Variance'];
        $stddev = $data['StdDev'];

        $graph = $plugin->getOrCreateGraph('Global Statistics', false, 1, GraphType::Area, TRUE, 1);
        $columnID = $graph->getColumnID('Players');

        // these can be NULL IFF there is only one data point (e.g one server) in the sample
        // we're using sample functions NOT population so this should be fairly obvious why
        // this will return null
        if ($variance === null || $stddev === null)
        {
            $variance = 0;
            $stddev = 0;
        }

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

        exit(0);
    }
}

// wait for all of the processes to finish
while ($running_processes > 0)
{
    pcntl_wait($status);
    $running_processes --;
}