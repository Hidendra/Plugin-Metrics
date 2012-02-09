<?php

// 0 * * * * php cron/servers.php
//
// stores the amount of servers that pinged us in the last hour so it can be easily graphed

define('ROOT', '../');

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

// Load all of the countries we can use
$countries = loadCountries();

// iterate through all of the plugins
foreach (loadPlugins() as $plugin)
{
    $baseEpoch = normalizeTime();

    // we want the data for the last hour
    $minimum = strtotime('-30 minutes', $baseEpoch);

    foreach ($countries as $shortCode => $fullName)
    {
        // load the players online in the last hour
        $servers = $plugin->countServersLastUpdatedFromCountry($shortCode, $minimum);

        if ($servers == 0)
        {
            continue;
        }

        // Insert it into the database
        $statement = $pdo->prepare('INSERT INTO CountryTimeline (Plugin, Country, Servers, Epoch) VALUES (:Plugin, :Country, :Servers, :Epoch)');
        $statement->execute(array(
            ':Plugin' => $plugin->getID(),
            ':Country' => $shortCode,
            ':Servers' => $servers,
            ':Epoch' => $baseEpoch
        ));
    }
}