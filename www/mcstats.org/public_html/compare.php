<?php

define('ROOT', './');

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

$plugin1_name = $_GET['plugin1'];
$plugin2_name = $_GET['plugin2'];

$plugin1 = loadPlugin($plugin1_name);
$plugin2 = loadPlugin($plugin2_name);

if ($plugin1 == null || $plugin2 == null)
{
    exit('Invalid plugins provided.');
}

// array of the plugins to compare
$plugins = array($plugin1, $plugin2);

// plugin-specific counts
printf("%s servers have used %s in the last hour. <br/>", number_format($plugin1->countServersLastUpdated(time() - SECONDS_IN_HOUR)), $plugin1->getName());
printf("%s servers have used %s in the last hour. <br/><br/>", number_format($plugin2->countServersLastUpdated(time() - SECONDS_IN_HOUR)), $plugin2->getName());

$min_epoch = time() - SECONDS_IN_HOUR;

// how many servers have Either of the plugins
$either_of = count_servers($plugins, $min_epoch, -1);
// how many servers have OneOf the plugins (but not both)
$one_of = count_servers($plugins, $min_epoch, 1);
// how many servers have Both of the plugins
$both_of = count_servers($plugins, $min_epoch, count($plugins));

printf("%s servers have EitherOf ( %s , %s ) in the last hour. <br/>", number_format($either_of), $plugin1->getName(), $plugin2->getName());
printf("%s servers have OneOf ( %s , %s ) in the last hour. <br/>", number_format($one_of), $plugin1->getName(), $plugin2->getName());
printf("%s servers have BothOf ( %s , %s ) in the last hour. <br/>", number_format($both_of), $plugin1->getName(), $plugin2->getName());

function count_servers($plugins, $min_epoch, $matches_required = -1 /* Match any of the plugins */)
{
    $plugin_ids = array();

    foreach ($plugins as $plugin)
    {
        $plugin_ids[] = $plugin->getID();
    }

    if ($matches_required == -1)
    {
        $statement = get_slave_db_handle()->prepare('
        select count(*) from (
          select 1 from ServerPlugin where Plugin IN ( ' . implode(',', $plugin_ids) . ' ) AND Updated >= ? group by Server
        ) V');
        $statement->execute(array($min_epoch));
    } else
    {
        $statement = get_slave_db_handle()->prepare('
        select count(*) from (
          select 1 from ServerPlugin where Plugin IN ( ' . implode(',', $plugin_ids) . ' ) AND Updated >= ? group by Server having count(Plugin) = ?
        ) V');
        $statement->execute(array($min_epoch, $matches_required));
    }

    $row = $statement->fetch();
    return $row != null ? $row[0] : 0;
}