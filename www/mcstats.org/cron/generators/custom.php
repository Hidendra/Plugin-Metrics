<?php

define('ROOT', '../public_html/');
define('MAX_COLUMNS', 50); // soft limit of max amount of columns to loop through per plugin
define('MAX_CHILDREN', 30); // the maximum amount of children that can be started

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

// the current number of running forks
$running_processes = 0;

$baseEpoch = normalizeTime();
$minimum = strtotime('-30 minutes', $baseEpoch);

// iterate through all of the plugins
foreach (loadPlugins(PLUGIN_ORDER_POPULARITY) as $plugin)
{
    if ($plugin->getID() == GLOBAL_PLUGIN_ID) continue;

    // we want the data for the last hour
    $count = 0;

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

        // Loop through all of the possible columns
        foreach ($plugin->getCustomColumns() as $columnID => $name)
        {
            // Extract the data for the current graphing period
            $statement = get_slave_db_handle()->prepare('
                    SELECT
                        SUM(DataPoint) AS Sum,
                        COUNT(DataPoint) AS Count,
                        AVG(DataPoint) AS Avg,
                        MAX(DataPoint) AS Max,
                        MIN(DataPoint) AS Min,
                        VAR_SAMP(DataPoint) AS Variance,
                        STDDEV_SAMP(DataPoint) AS StdDev
                    FROM CustomData WHERE ColumnID = ? AND Plugin = ? AND Updated >= ?');
            $statement->execute(array($columnID, $plugin->getID(), $minimum));

            // grab the data
            $data = $statement->fetch();

            // assign it all
            $sum = $data['Sum'];
            $count = $data['Count'];
            $avg = $data['Avg'];
            $max = $data['Max'];
            $min = $data['Min'];
            $variance = $data['Variance'];
            $stddev = $data['StdDev'];

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

            $count ++;
        }

        // exit the child
        exit(0);
    }
}

// wait for all of the processes to finish
while ($running_processes > 0)
{
    pcntl_wait($status);
    $running_processes --;
}