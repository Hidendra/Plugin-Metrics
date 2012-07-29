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

        $servers = 0;

        // load the players online in the last hour
        if ($plugin->getID() != GLOBAL_PLUGIN_ID)
        {
            $servers = $plugin->countServersLastUpdated($minimum);
        } else
        {
            $statement = $master_db_handle->prepare('select COUNT(distinct Server) AS Count from ServerPlugin where Updated >= ?');
            $statement->execute(array($minimum));

            if ($row = $statement->fetch())
            {
                $servers = $row['Count'];
            }
        }

        // Insert it into the database
        $statement = $master_db_handle->prepare('INSERT INTO ServerTimeline (Plugin, Servers, Epoch) VALUES (:Plugin, :Servers, :Epoch)');
        $statement->execute(array(
            ':Plugin' => $plugin->getID(),
            ':Servers' => $servers,
            ':Epoch' => $baseEpoch
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