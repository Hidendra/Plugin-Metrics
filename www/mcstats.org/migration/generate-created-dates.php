<?php

define('ROOT', '../public_html/');
define('MAX_CHILDREN', 5);

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

// the current number of running forks
$running_processes = 0;

// number of plugins converted
$index = 0;

$plugins = loadPlugins(PLUGIN_ORDER_ALPHABETICAL);
$total = count($plugins);

// iterate through all of the plugins
foreach ($plugins as $plugin) {
    // are we at the process limit ?
    if ($running_processes >= MAX_CHILDREN) {
        // wait for some children to be allocated
        pcntl_wait($status);
        $running_processes--;
    }

    $running_processes++;
    $pid = pcntl_fork();
    $index++;

    if ($pid == 0) {
        $master_db_handle = try_connect_database();
        echo sprintf('[%d%%] Estimating created date for %s ..%s', floor(($index / $total) * 100), $plugin->getName(), PHP_EOL);

        $statement = get_slave_db_handle()->prepare('SELECT Min(Epoch) FROM CustomDataTimeline where Plugin = ?');
        $statement->execute(array($plugin->getID()));

        if ($row = $statement->fetch()) {
            $plugin->setCreated($row[0]);
        } else {
            echo sprintf('%s has no available data to estimate from, using current time%s', $plugin->getName(), PHP_EOL);
            $plugin->setCreated(time());
        }

        // save it
        $plugin->save();
        exit(0);
    }
}

// wait for all of the processes to finish
while ($running_processes > 0)
{
    pcntl_wait($status);
    $running_processes --;
}

echo sprintf('Converted %d plugins%s', count($plugins), PHP_EOL);