<?php

// 0 * * * * php cron/servers.php
//
// stores the amount of servers that pinged us in the last hour so it can be easily graphed

define('ROOT', '../');

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

// iterate through all of the plugins
foreach (loadPlugins() as $plugin)
{
    $baseEpoch = normalizeTime();

    // we want the data for the last hour
    $minimum = strtotime('-30 minutes', $baseEpoch);

    // load the players online in the last hour
    $servers = $plugin->countServersLastUpdated($minimum);

    // Insert it into the database
    $statement = $pdo->prepare('INSERT INTO ServerTimeline (Plugin, Servers, Epoch) VALUES (:Plugin, :Servers, :Epoch)');
    $statement->execute(array(
        ':Plugin' => $plugin->getID(),
        ':Servers' => $servers,
        ':Epoch' => $baseEpoch
    ));
}