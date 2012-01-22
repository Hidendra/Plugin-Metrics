<?php
if (!defined('ROOT')) exit('For science.');

// Include classes
require 'Server.class.php';
require 'Plugin.class.php';

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
 * Extract custom data from the post request
 * @return array
 */
function extractCustomData()
{
    $custom = array();

    foreach ($_POST as $key => $value)
    {
        // verify we have a number as the key
        if (!is_numeric($value)) {
            continue;
        }

        // check if the string starts with custom
        // note !== note == (false == 0, false !== 0)
        if (stripos($key, 'custom') !== 0) {
            continue;
        }

        $columnName = str_replace('_', ' ', substr($key, 6));
        $columnName = mb_convert_encoding($columnName, 'ISO-8859-1', 'UTF-8');

        if (strstr($columnName, 'Protections') !== FALSE)
        {
            $columnName = str_replace('?', 'i', $columnName);
        }

        if (!in_array($columnName, $custom))
        {
            $custom[$columnName] = $value;
        }
    }

    return $custom;
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

    $statement = $pdo->prepare('SELECT ID, Name, Author, Hidden, GlobalHits FROM Plugin');
    $statement->execute();

    while ($row = $statement->fetch())
    {
        $plugin = new Plugin();
        $plugin->setID($row['ID']);
        $plugin->setName($row['Name']);
        $plugin->setAuthor($row['Author']);
        $plugin->setHidden($row['Hidden']);
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

    $statement = $pdo->prepare('SELECT ID, Name, Author, Hidden, GlobalHits FROM Plugin WHERE Name = :Name');
    $statement->execute(array(':Name' => $plugin));

    if ($row = $statement->fetch())
    {
        $plugin = new Plugin();
        $plugin->setID($row['ID']);
        $plugin->setName($row['Name']);
        $plugin->setAuthor($row['Author']);
        $plugin->setHidden($row['Hidden']);
        $plugin->setGlobalHits($row['GlobalHits']);
        return $plugin;
    }

    return NULL;
}