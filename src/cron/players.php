<?php

// 0 * * * * php cron/players.php
//
// takes player count data for the last hour for each plugin and stores it in the database so it can be easily graphed

define('ROOT', '../');

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

    // load the players online in the last hour
    $players = $plugin->sumPlayersOfServersLastUpdated($minimum);

    // Insert it into the database
    $statement = $pdo->prepare('INSERT INTO PlayerTimeline (Plugin, Players, Epoch) VALUES (:Plugin, :Players, :Epoch)');
    $statement->execute(array(
        ':Plugin' => $plugin->getID(),
        ':Players' => $players,
        ':Epoch' => $baseEpoch
    ));
}