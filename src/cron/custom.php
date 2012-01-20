<?php

// 0 * * * * php cron/servers.php
//
// stores the amount of servers that pinged us in the last hour so it can be easily graphed

define('ROOT', '../');
define('MAX_COLUMNS', 30); // soft limit of max amount of columns to loop through per plugin

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

// iterate through all of the plugins
foreach (loadPlugins() as $plugin)
{
    // Calculate the closest hour
    $denom = 60 * 60; // 60 minutes * 60 seconds = 3600 seconds in an hour
    $baseEpoch = round(time() / $denom) * $denom;

    // we want the data for the last hour
    $minimum = strtotime('-1 hour', $baseEpoch);

    // Loop through all of the possible columns
    foreach ($plugin->getCustomColumns() as $id => $name)
    {
        $count = 0;

        if ($count > MAX_COLUMNS) {
            break;
        }

        $sum = $plugin->sumCustomData($id, $minimum);

        $statement = $pdo->prepare('INSERT INTO CustomDataTimeline (Plugin, ColumnID, DataPoint, Epoch) VALUES (:Plugin, :ColumnID, :DataPoint, :Epoch)');
        $statement->execute(array(
            ':Plugin' => $plugin->getID(),
            ':ColumnID' => $id,
            ':DataPoint' => $sum,
            ':Epoch' => $baseEpoch
        ));

        $count ++;
    }
}