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
$pluginName = $_GET['plugin'];
$plugin = loadPlugin($pluginName);

// We want to allow square brackets so we decode $_post ourselves
$raw_post = urldecode(file_get_contents('php://input'));

// Empty out $_POST
$_POST = array();

// Create the new $_POST
foreach (explode('&', $raw_post) as $v)
{
    $a = explode('=', $v);
    $_POST[$a[0]] = $a[1];
}

// Begin extracting arguments
$guid = getPostArgument('guid');
$serverVersion = getPostArgument('server');
$version = getPostArgument('version');
$ping = isset($_POST['ping']); // if they're pinging us, we don't update the hitcount

// Revision, added in R4, so default to R4
$revision = isset($_POST['revision']) ? $_POST['revision'] : 4;

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
                if ($plugin->getName() == 'CraftBukkitPlusPlus')
                {
                    if (!is_numeric($columnName))
                    {
                        continue;
                    }
                }

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