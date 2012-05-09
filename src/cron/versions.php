<?php

define('ROOT', '../');

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

// The current graphing period
$baseEpoch = normalizeTime();

// we want the data for the last hour
$minimum = strtotime('-30 minutes', $baseEpoch);

// iterate through all of the plugins
foreach (loadPlugins(true) as $plugin)
{
    foreach($plugin->getVersions() as $versionID => $version)
    {
        // Count the amount of servers that upgraded to this version
        $count = $plugin->countVersionChanges($versionID, $minimum);

        // Insert it into the database
        $statement = $master_db_handle->prepare('INSERT INTO VersionTimeline (Plugin, Version, Count, Epoch) VALUES (:Plugin, :Version, :Count, :Epoch)');
        $statement->execute(array(
            ':Plugin' => $plugin->getID(),
            ':Version' => $versionID,
            ':Count' => $count,
            ':Epoch' => $baseEpoch
        ));
    }
}