<?php

// 0 * * * * php cron/custom.php
//
// stores the custom data obtained in the last hour into a graphable format

define('ROOT', '../');
define('MAX_COLUMNS', 30); // soft limit of max amount of columns to loop through per plugin

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

// iterate through all of the plugins
foreach (loadPlugins() as $plugin)
{
    $baseEpoch = normalizeTime();

    // we want the data for the last hour
    $minimum = strtotime('-30 minutes', $baseEpoch);

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