<?php
if (!defined('ROOT')) exit('For science.');

/**
 * Global PDO object that is accessible after the database is connected to
 * @var PDO
 */
$pdo = NULL;

/**
 * Attempt to connect to the database
 *
 * @return TRUE if connected, otherwise an error message
 */
function try_connect_database()
{
    global $pdo , $config;

    $db = $config['database'];

    try
    {
        $pdo = new PDO("{$db['driver']}:host={$db['host']};dbname={$db['dbname']}", $db['username'], $db['password'], array(
            PDO::ATTR_PERSISTENT => true
        ));

        return TRUE;
    } catch (PDOException $e)
    {
        return $e->getMessage();
    }
}

// Connect to the database
if (($e = try_connect_database()) !== TRUE)
{
    exit('Error while connecting to the database: <br/><b>' . $e . '</b>');
}