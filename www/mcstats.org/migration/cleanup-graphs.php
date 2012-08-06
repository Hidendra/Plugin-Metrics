<?php

define('ROOT', '../public_html/');

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

// number of plugins converted
$converted = 0;

$plugins = loadPlugins(PLUGIN_ORDER_ALPHABETICAL);
$total = count($plugins);

// pre prepare some statements
$count_customdata = get_slave_db_handle()->prepare('SELECT COUNT(*) FROM CustomDataTimeline WHERE ColumnID = ? AND Epoch >= ?');
$delete_column = $master_db_handle->prepare('DELETE FROM CustomColumn where ID = ?');
$delete_graph = $master_db_handle->prepare('DELETE FROM Graph where ID = ?');

foreach ($plugins as $plugin)
{
    echo sprintf('[%d%%] Cleaning up Graph / CustomColumn objects for %s ..%s', floor(($converted / $total) * 100), $plugin->getName(), PHP_EOL);

    // walk through each graph
    foreach ($plugin->getAllGraphs() as $graph)
    {
        // first check each column
        foreach ($graph->getColumns() as $columnID => $columnName)
        {
            // get the amount of generated data in the last 7 days
            $count_customdata->execute(array($columnID, time() - SECONDS_IN_WEEK));

            $count = 0;

            if ($row = $count_customdata->fetch())
            {
                $count = $row[0];
            }

            // if the count is 0 simply remove the column
            if ($count == 0)
            {
                echo sprintf('  => Deleting Column [ID: %d Name: \"%s\"] from Plugin \"%s\"%s', $columnID, $columnName, $plugin->getName(), PHP_EOL);
                $delete_column->execute(array($columnID));
            }
        }

        // reload the columns
        $graph->loadColumns();

        // if the graph now has 0 (active) columns it is invalid
        if (count($graph->getColumns()) == 0)
        {
            echo sprintf('  => Deleting Graph [ID: %d Name: \"%s\"] from Plugin \"%s\"%s', $graph->getID(), $graph->getName(), $plugin->getName(), PHP_EOL);
            $delete_graph->execute(array($graph->getID()));
        }

    }

    $converted ++;
}