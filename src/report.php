<?php
define('ROOT', './');

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

if (!isset($_GET['plugin']))
{
    exit('ERR No plugin provided.');
}

// Check that we have a valid plugin name
if (!preg_match('/[a-zA-Z ]/', $_GET['plugin'])) {
    exit('ERR Invalid plugin name.');
}

// Load the plugin
$plugin = loadPlugin($_GET['plugin']);

// Begin extracting arguments
$guid = getPostArgument('guid');
$serverVersion = getPostArgument('server');
$version = getPostArgument('version');
$ping = isset($_POST['ping']); // if they're pinging us, we don't update the hitcount

// simple user agent check to block the lazy
if (!preg_match('/Java/', $_SERVER['HTTP_USER_AGENT'])) {
    exit('ERR');
}

// If it does not exist we will create a new plugin for them :-)
if ($plugin === NULL)
{
    $plugin = new Plugin();
    $plugin->setName($_GET['plugin']);
    $plugin->setAuthor('');
    $plugin->setHidden(0);
    $plugin->setGlobalHits(0);
    $plugin->create();
    $plugin = loadPlugin($_GET['plugin']);
}

// Some arguments added later in that to remain backwards compatibility
$players = isset($_POST['players']) ? intval($_POST['players']) : 0;

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

// Check the player count
if ($players >= 0)
{
    $server->setPlayers($players);
}

// increment the hits if it's a fresh server start
if (!$ping)
{
    $plugin->incrementGlobalHits();
    $server->incrementHits();
}

// Check for Geo IP
if (isset($_SERVER['GEOIP_COUNTRY_CODE']))
{
    $shortCode = $_SERVER['GEOIP_COUNTRY_CODE'];
    $fullName = $_SERVER['GEOIP_COUNTRY_NAME'];

    // Do we need to update their country?
    if ($server->getCountry() != $shortCode)
    {
        $server->setCountry($shortCode);

        // Insert it into the Country table
        $statement = $pdo->prepare('INSERT INTO Country (ShortCode, FullName) VALUES (:ShortCode, :FullName)');
        $statement->execute(array(':ShortCode' => $shortCode, ':FullName' => $fullName));
    }
}

// Check for custom data
if (count(($data = extractCustomData())) > 0) {
    foreach ($data as $k => $v)
    {
        $server->addCustomData($k, $v);
    }
}

// Get the timestamp for the last hour
$lastHour = getLastHour();

// Is this the first time they updated this hour?
if ($lastHour > $server->getUpdated())
{
    echo 'OK This is your first update this hour.';
} else
{
    echo 'OK';
}

// save. if no changes, this at least updates the 'updated' time
$server->save();