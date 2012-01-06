<?php
if (!defined('ROOT')) exit('For science.');

// Include classes
require 'Server.class.php';
require 'Plugin.class.php';

// include the geo ip database
require ROOT . 'geoip/countries.php';

// Some constants
define('SECONDS_IN_HOUR', 60 * 60);
define('SECONDS_IN_HALFDAY', 60 * 60 * 12);
define('SECONDS_IN_DAY', 60 * 60 * 24);
define('SECONDS_IN_WEEK', 60 * 60 * 24 * 7);

/**
 * Load a key from POST. If it does not exist, die loudly
 *
 * @param $key string
 * @return string
 */
function getPostArgument($key)
{
    // FIXME change to $_POST
    // check
    if (!isset($_POST[$key]))
    {
        exit('ERR Missing arguments.');
    }

    return $_POST[$key];
}

/**
 * Loads all of the plugins from the database
 *
 * @return Plugin[]
 */
function loadPlugins()
{
    global $pdo;
    $plugins = array();

    $statement = $pdo->prepare('SELECT ID, Name, GlobalHits FROM Plugin');
    $statement->execute();

    if ($row = $statement->fetch())
    {
        $plugin = new Plugin();
        $plugin->setID($row['ID']);
        $plugin->setName($row['Name']);
        $plugin->setGlobalHits($row['GlobalHits']);
        $plugins[] = $plugin;
    }

    return $plugins;
}

/**
 * Load a plugin
 *
 * @param $plugin string The plugin's name
 * @return Plugin if it exists otherwise NULL
 */
function loadPlugin($plugin)
{
    global $pdo;

    $statement = $pdo->prepare('SELECT ID, Name, GlobalHits FROM Plugin WHERE Name = :Name');
    $statement->execute(array(':Name' => $plugin));

    if ($row = $statement->fetch())
    {
        $plugin = new Plugin();
        $plugin->setID($row['ID']);
        $plugin->setName($row['Name']);
        $plugin->setGlobalHits($row['GlobalHits']);
        return $plugin;
    }

    return NULL;
}