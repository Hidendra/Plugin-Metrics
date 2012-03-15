<?php
if (!defined('ROOT')) exit('For science.');

// Include classes
require 'Server.class.php';
require 'Plugin.class.php';
require 'DataGenerator.class.php';
require 'Cache.class.php';

// graphing libs
require 'Graph.class.php';
require 'highroller/HighRoller.php';
require 'highroller/HighRollerSeriesData.php';
require 'highroller/HighRollerSplineChart.php';
require 'highroller/HighRollerAreaChart.php';
require 'highroller/HighRollerColumnChart.php';
require 'highroller/HighRollerPieChart.php';

// Some constants
define('SECONDS_IN_HOUR', 60 * 60);
define('SECONDS_IN_HALFDAY', 60 * 60 * 12);
define('SECONDS_IN_DAY', 60 * 60 * 24);
define('SECONDS_IN_WEEK', 60 * 60 * 24 * 7);

// Connect to the caching daemon
$cache = new Cache();

/**
 * Log an error and force end the process
 * @param $message
 */
function error_fquit($message)
{
    error_log($message);
    exit;
}

/**
 * Checks a PDO statement for errors and if any exist, the script will exist and log to the error log
 *
 * @param $statement PDOStatement
 */
function check_statement($statement)
{
    $errorInfo = $statement->errorInfo();

    // If the first element is 0, it's good
    if ($errorInfo[0] == 0)
    {
        return;
    }

    // Some error has occurred, log it and quit
    error_fquit('FQUIT Statement \"' . $statement->queryString . '" errorInfo() => ' . print_r($errorInfo, true));
}

/**
 * Get the epoch of the closest hour (downwards, never up)
 * @return float
 */
function getLastHour()
{
    return strtotime(date('F d Y H:00'));
}

/**
 * Calculate the time until the next graph will be calculated
 * @return int the unix timestamp of the next graph
 */
function timeUntilNextGraph()
{
    global $config;

    $interval = $config['graph']['interval'];
    return normalizeTime() + ($interval * 60);
}

/**
 * Normalize a time to the nearest graphing period
 *
 * @param $time if < 0, the time() will be used
 */
function normalizeTime($time = -1)
{
    global $config;

    if ($time < 0)
    {
        $time = time();
    }

    // The amount of minutes between graphing periods
    $interval = $config['graph']['interval'];

    // Calculate the denominator (interval * 60 secs)
    $denom = $interval * 60;

    // Round to the closest one
    return round(($time - ($denom / 2)) / $denom) * $denom;
}

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
        exit('ERR Missing arguments');
    }

    return $_POST[$key];
}

/**
 * Extract custom data from the post request. Used in R5 and above
 * Array format:
 * {
 *      "GraphName": {
 *          "ColumnName": Value
 *      },
 *      ...
 * }
 * @return array
 */
function extractCustomData()
{
    global $config;

    // What custom data is separated by
    $separator = $config['graph']['separator'];

    // Array of data to return
    $data = array();

    foreach ($_POST as $key => $value)
    {
        // verify we have a number as the key
        if (!is_numeric($value)) {
            continue;
        }

        // Find the first position of the separator
        $r_index = strrpos($key, $separator);

        // Did we not match one?
        if ($r_index === FALSE)
        {
            continue;
        }

        // Extract the data :-)
        $graphName = str_replace('_', ' ', substr($key, 3, $r_index - 3));
        $columnName = str_replace('_', ' ', substr($key, $r_index + 2));

        // Set it :-)
        $data[$graphName][$columnName] = $value;
    }

    return $data;
}

/**
 * Extract custom data from the post request. Used in R4 and lower.
 * Array format:
 * {
 *      "ColumnName": Value,
 *      ...
 * }
 *
 * @return array
 */
function extractCustomDataLegacy()
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
 * Get all of the possible country codes we have stored
 *
 * @return string[], e.g ["CA"] = "Canada"
 */
function loadCountries()
{
    global $pdo;
    $countries = array();

    $statement = $pdo->prepare('SELECT ShortCode, FullName FROM Country LIMIT 300'); // hard limit of 300
    $statement->execute();

    while ($row = $statement->fetch())
    {
        $shortCode = $row['ShortCode'];
        $fullName = $row['FullName'];

        $countries[$shortCode] = $fullName;
    }

    return $countries;
}

/**
 * Resolve a plugin object from a row
 *
 * @param $row
 * @return Plugin
 */
function resolvePlugin($row)
{
    $plugin = new Plugin();
    $plugin->setID($row['ID']);
    $plugin->setName($row['Name']);
    $plugin->setAuthors($row['Author']);
    $plugin->setHidden($row['Hidden']);
    $plugin->setGlobalHits($row['GlobalHits']);

    return $plugin;
}

/**
 * Loads all of the plugins from the database
 *
 * @return Plugin[]
 */
function loadPlugins($alphabetical = false)
{
    global $pdo;
    $plugins = array();

    if ($alphabetical)
    {
        $statement = $pdo->prepare('SELECT ID, Name, Author, Hidden, GlobalHits FROM Plugin ORDER BY Name ASC');
    } else
    {
        $statement = $pdo->prepare('SELECT ID, Name, Author, Hidden, GlobalHits FROM Plugin ORDER BY (SELECT COUNT(*) FROM ServerPlugin WHERE Plugin = Plugin.ID AND Updated >= ?) DESC');
    }
    $statement->execute(array(time() - SECONDS_IN_DAY));

    while ($row = $statement->fetch())
    {

        $plugins[] = resolvePlugin($row);
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
        return resolvePlugin($row);
    }

    return NULL;
}

/////////////////////////////////
/// User interface functions  ///
/////////////////////////////////

/**
 * Checks if a string ends with the given string
 *
 * @param $needle
 * @param $haystack
 * @return bool TRUE if the haystack ends with the given needle
 */
function str_endswith($needle, $haystack)
{
    return strrpos($haystack, $needle) === strlen($haystack)-strlen($needle);
}

/**
 * Sender the header html file to the user
 */
function send_header()
{
    include ROOT . 'assets/template/header.php';
}

/**
 * Send the footer html file to the user
 */
function send_footer()
{
    include ROOT . 'assets/template/footer.php';
}


/////////////////////////////////
/// Admin interface functions ///
/////////////////////////////////

/**
 * Output a formatted error
 *
 * @param $msg the error to send
 */
function err($msg)
{
    echo '
    <div class="row-fluid">
        <span class="alert alert-error">
            ' . $msg . '
        </span>
    </div>';
}

function send_admin_sidebar()
{
    echo '
                <script type="text/javascript">

                    $(function() {
                        // pjaxify
                        $("a").pjax("#plugin-content");
                    });

                </script>

                <div class="span2">
                    <div class="well sidebar-nav">
                        <ul class="nav nav-list">
                            <li class="nav-header">Your plugins</li>';

    // Go through each of the plugins they can access
    foreach (get_accessible_plugins() as $plugin)
    {
        // The plugin's name
        $pluginName = $plugin->getName();

        echo '
                            <li><a href="/admin/plugin/' . $pluginName . '/view">' . $pluginName . '</a></li>';
    }

echo '
                        </ul>
                    </div>
                </div>
    ';
}

/**
 * Check if the given plugin can be accessed.
 *
 * @param $plugin Plugin or string
 * @return TRUE if the player can administrate the plugin
 */
function can_admin_plugin($plugin)
{
    if ($plugin instanceof Plugin)
    {
        $plugin_obj = $plugin;
    } else if ($plugin instanceof string)
    {
        $plugin_obj = loadPlugin($plugin);
    }

    // is it null??
    if ($plugin_obj == null)
    {
        return FALSE;
    }

    // iterate through our accessible plugins
    foreach (get_accessible_plugins() as $a_plugin)
    {
        if ($a_plugin->getName() == $plugin_obj->getName())
        {
            return TRUE;
        }
    }

    return FALSE;
}

/**
 * Get all of the plugins the currently logged in user can access
 *
 * @return array Plugin
 */
function get_accessible_plugins()
{
    global $_SESSION , $pdo;

    // The plugins we can access
    $plugins = array();

    // Make sure they are plugged in
    if (!is_loggedin())
    {
        return $plugins;
    }

    // Query for all of the plugins
    $statement = $pdo->prepare('SELECT Plugin, ID, Name, Plugin.Author, Hidden, GlobalHits FROM AuthorACL LEFT OUTER JOIN Plugin ON Plugin.ID = Plugin WHERE AuthorACL.Author = ? ORDER BY Name ASC');
    $statement->execute(array($_SESSION['uid']));

    while ($row = $statement->fetch())
    {
        $plugins[] = resolvePlugin($row);
    }

    return $plugins;
}

/**
 * Check a login if it is correct
 *
 * @param $username
 * @param $password
 * @return string their correct username if the login is correct, otherwise FALSE
 */
function check_login($username, $password)
{
    global $pdo , $_SESSION;

    // Create the query
    $statement = $pdo->prepare('SELECT ID, Name, Password FROM Author WHERE Name = ?');
    $statement->execute(array($username));

    if ($row = $statement->fetch())
    {
        $real_username = $row['Name'];
        $hashed_password = $row['Password'];

        // Verify the password
        if (sha1($password) != $hashed_password)
        {
            return FALSE;
        }

        // Set some stuff
        $_SESSION['uid'] = $row['ID'];

        // Authenticated
        return $real_username;
    }

    return FALSE;
}

/**
 * Check if the user is logged in
 * @return bool TRUE if the user is logged in
 */
function is_loggedin()
{
    global $_SESSION;
    return isset($_SESSION['loggedin']);
}

/**
 * Ensure the user is logged in
 */
function ensure_loggedin()
{
    global $_SESSION;

    if (!isset($_SESSION['loggedin']))
    {
        header('Location: /admin/login.php');
        exit;
    }
}