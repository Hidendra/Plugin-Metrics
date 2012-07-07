<?php

// 0 * * * * php cron/servers.php
//
// stores the amount of servers that pinged us in the last hour so it can be easily graphed

define('ROOT', '../');
define('MAX_CHILDREN', 30);

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

// Load all of the countries we can use
$countries = loadCountries();
$baseEpoch = normalizeTime();

// we want the data for the last hour
$minimum = strtotime('-30 minutes', $baseEpoch);

// the current number of running forks
$running_processes = 0;

// iterate through all of the plugins
foreach (loadPlugins(true) as $plugin)
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

        foreach ($countries as $shortCode => $fullName)
        {
            $servers = 0;

            // load the players online in the last hour
            if ($plugin->getID() != GLOBAL_PLUGIN_ID)
            {
                $servers = $plugin->countServersLastUpdatedFromCountry($shortCode, $minimum);
            } else
            {
                $statement = $master_db_handle->prepare('SELECT COUNT(distinct Server) AS Count FROM ServerPlugin
                                        LEFT OUTER JOIN Server ON (ServerPlugin.Server = Server.ID)
                                        WHERE Country = ? AND ServerPlugin.Updated >= ?');
                $statement->execute(array($shortCode, $minimum));

                if ($row = $statement->fetch())
                {
                    $servers = $row['Count'];
                }
            }

            if ($servers == 0)
            {
                continue;
            }

            // Insert it into the database
            $statement = $master_db_handle->prepare('INSERT INTO CountryTimeline (Plugin, Country, Servers, Epoch) VALUES (:Plugin, :Country, :Servers, :Epoch)');
            $statement->execute(array(
                ':Plugin' => $plugin->getID(),
                ':Country' => $shortCode,
                ':Servers' => $servers,
                ':Epoch' => $baseEpoch
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