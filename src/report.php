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
if (!preg_match('/[a-zA-Z0-9 ]/', $_GET['plugin']))
{
    exit('ERR Invalid plugin name.');
}

// Load the plugin
$pluginName = preg_replace('/[^a-zA-Z0-9_. ]+/', '', $_GET['plugin']);
$plugin = loadPlugin($pluginName);

// Begin extracting arguments
$guid = getPostArgument('guid');
$serverVersion = getPostArgument('server');
$version = getPostArgument('version');
$ping = isset($_POST['ping']); // if they're pinging us, we don't update the hitcount

// Revision, added in R4, so default to R4
$revision = isset($_POST['revision']) ? $_POST['revision'] : 4;

// Added in R5
$authors = isset($_POST['authors']) ? $_POST['authors'] : '';

// Cleanse the authors text
$authors = preg_replace('/[^a-zA-Z0-9_,\- ]+/', '', $authors);

// replace underscores with spaces
$authors = str_replace('_', ' ', $authors);

// simple user agent check to block the lazy
if (!preg_match('/Java/', $_SERVER['HTTP_USER_AGENT'])) {
    exit('ERR');
}

// If it does not exist we will create a new plugin for them :-)
if ($plugin === NULL)
{
    $plugin = new Plugin();
    $plugin->setName($pluginName);
    $plugin->setAuthors('');
    $plugin->setHidden(0);
    $plugin->setGlobalHits(0);

    // Create the plugin, at the moment we allow any new plugin to be automatically created
    // in the future this may require separate registration, but probably not
    $plugin->create();

    // Reload the plugin so we have the most up to date data from the database
    $plugin = loadPlugin($_GET['plugin']);
}

// Some arguments added later in that to remain backwards compatibility
$players = isset($_POST['players']) ? intval($_POST['players']) : 0;

// Now load the server
// This is guaranteed to not return null
$server = $plugin->getOrCreateServer($guid);

// Are they using a different version now?
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

// Update the authors for the plugin if the one set in the database is blank
if ($plugin->getAuthors() == '' && $authors != '')
{
    $plugin->setAuthors($authors);
    $plugin->save();
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
        // The Country table is used to keep track of the full name for countries if it does
        // not exist in the database yet, instead of storing the full name elsewhere
        $statement = $pdo->prepare('INSERT INTO Country (ShortCode, FullName) VALUES (:ShortCode, :FullName)');
        $statement->execute(array(':ShortCode' => $shortCode, ':FullName' => $fullName));
    }
}

// Check for custom data
// R5 and above, multigraph  compat
if ($revision >= 5)
{
    if (count(($data = extractCustomData())) > 0) {
        foreach ($data as $graphName => $plotters)
        {
            // Get or create the graph
            $graph = $plugin->getOrCreateGraph($graphName, false, 1); // Todo make it not active when authors can modify graphs

            foreach ($plotters as $columnName => $value)
            {
                // Ensure the column is set to this graph
                // and also ensure it's even in the graph
                $result = $graph->verifyColumn($columnName);

                // Now add the data to the given column
                $graph->addCustomData($server, $columnName, $value);
            }
        }
    }
}
// R4 and below
else
{
    if (count(($data = extractCustomDataLegacy())) > 0) {
        $graph = $plugin->getOrCreateGraph('Default', false, 1);

        foreach ($data as $columnName => $value)
        {
            $graph->verifyColumn($columnName, false, false);
            $graph->addCustomData($server, $columnName, $value);
        }
    }
}

// Get the timestamp for the last graphing period
$lastGraphUpdate = normalizeTime();

// Is this the first time they updated this hour?
if ($lastGraphUpdate > $server->getUpdated())
{
    echo 'OK This is your first update this hour.';
} else
{
    echo 'OK';
}

// save the server.. if no changes, this at least updates the 'updated' time
$server->save();