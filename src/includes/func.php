<?php

// Include classes
require 'Server.class.php';
require 'Plugin.class.php';

// include the geo ip database
require ROOT . 'geoip/countries.php';

// Some constants
define('MILLISECONDS_IN_DAY', 60 * 60 * 24 * 1000);
define('MILLISECONDS_IN_WEEK', 60 * 60 * 24 * 7 * 1000);

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
    if (!isset($_REQUEST[$key]))
    {
        exit('ERR Missing arguments.');
    }

    return $_REQUEST[$key];
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

/**
 * Convert an IP to a 2-char country code (ISO3166-1 alpha-2)
 * Obtained from: http://www.phptutorial.info/iptocountry/the_script.html
 *
 * @param $ip
 * @return string the country code, otherwise XX
 */
function convertIPToCountryCode($ip)
{
    // split the octets
    $numbers = preg_split( "/\./", $ip);

    // load the database for the single octet
    include ROOT . "geoip/" . $numbers[0] . ".php";

    // calculate the hashcode for the ip
    $code=($numbers[0] * 16777216) + ($numbers[1] * 65536) + ($numbers[2] * 256) + ($numbers[3]);

    // Search for a country
    foreach($ranges as $key => $value){
        if($key<=$code){
            if($ranges[$key][0]>=$code){$country=$ranges[$key][1];break;}
        }
    }

    return empty($country) ? "ZZ" : $country;
}