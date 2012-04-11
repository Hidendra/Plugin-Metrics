<?php

// 0 * * * * php cron/players.php
//
// takes player count data for the last hour for each plugin and stores it in the database so it can be easily graphed

define('ROOT', '../');

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

$baseEpoch = normalizeTime();

// we want the data for the last hour
$minimum = strtotime('-30 minutes', $baseEpoch);

// iterate through all of the plugins
foreach (loadPlugins() as $plugin)
{
    $players = 0;

    // load the players online in the last hour
    if ($plugin->getID() != GLOBAL_PLUGIN_ID)
    {
        $players = $plugin->sumPlayersOfServersLastUpdated($minimum);
    } else
    {
        $statement = $pdo->prepare('SELECT SUM(dev.Players) AS Count FROM (SELECT DISTINCT Server, Server.Players from ServerPlugin LEFT OUTER JOIN Server ON Server.ID = ServerPlugin.Server WHERE ServerPlugin.Updated >= ?) dev;');
        $statement->execute(array($minimum));

        if ($row = $statement->fetch())
        {
            $players = $row['Count'];
        }
    }

    // Insert it into the database
    $statement = $pdo->prepare('INSERT INTO PlayerTimeline (Plugin, Players, Epoch) VALUES (:Plugin, :Players, :Epoch)');
    $statement->execute(array(
        ':Plugin' => $plugin->getID(),
        ':Players' => $players,
        ':Epoch' => $baseEpoch
    ));
}