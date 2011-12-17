<?php
define('ROOT', './');

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

if (!isset($_GET['plugin']))
{
    exit('ERR No plugin provided.');
}

// Load the plugin
$plugin = loadPlugin($_GET['plugin']);

// Doesn't exist
if ($plugin === NULL)
{
    exit('ERR Invalid plugin.');
}

// Begin extracting arguments
$guid = getPostArgument('guid');
$serverVersion = getPostArgument('server');
$version = getPostArgument('version');
$ping = isset($_POST['ping']); // if they're pinging us, we don't update the hitcount

// Now load the server
$server = $plugin->getOrCreateServer($guid);

// Are they using a different version?
if ($server->getCurrentVersion() != $version)
{
    // Log it and update the current version
    $server->addVersionHistory($version);
    $server->setCurrentVersion($version);
}

// Different server version?
if ($server->getServerVersion() != $serverVersion)
{
    $server->setServerVersion($serverVersion);
}


// increment the hits if it's a fresh server start
if (!ping)
{
    $plugin->incrementGlobalHits();
    $server->incrementHits();
}

// save. if no changes, this at least updates the 'updated' time
$server->save();

// All good; no errors!
echo 'OK Thank you for your contribution.';