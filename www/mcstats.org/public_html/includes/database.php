<?php
if (!defined('ROOT')) exit('For science.');

// Profiling
require 'profiler/PDOStatementProfiler.class.php';
require 'profiler/PDOProfiler.class.php';

/**
 * Global PDO object that is accessible after the database is connected to
 * @var PDO
 */
$master_db_handle = NULL;

/**
 * The handle to the slave database
 */
$slave_db_handle = NULL;

/**
 * Get the slave database handle if it is connected, otherwise get the master handle.
 * This is mainly used for SELECT queries that can be offloaded to the slave server
 * if it is enabled.
 *
 * @return PDO
 */
function get_slave_db_handle()
{
    global $master_db_handle , $slave_db_handle;
    return $slave_db_handle !== NULL ? $slave_db_handle : $master_db_handle;
}

/**
 * Attempt to connect to the database
 *
 * @param string database type to connect to in the config, master or slave
 * @return PDO object if connected, otherwise the error message is sent to the error log and exited
 */
function try_connect_database($dbtype = 'master')
{
    global $config;
    $db = $config['database'][$dbtype];

    try
    {
        // Profiling:
        // return new PDOProfiler("mysql:host={$db['hostname']};dbname={$db['dbname']}", $db['username'], $db['password']);
        if (php_sapi_name() == 'cli')
        {
            return new PDO("mysql:host={$db['hostname']};dbname={$db['dbname']}", $db['username'], $db['password']);
        } else
        {
            return new PDO("mysql:host={$db['hostname']};dbname={$db['dbname']}", $db['username'], $db['password'], array(
                PDO::ATTR_PERSISTENT => true
            ));
        }
    } catch (PDOException $e)
    {
        error_log('Error while connecting to the database ' . $dbtype . ': <br/><b>' . $e->getMessage() . '</b>');
        exit('An error occurred while connecting to the database (' . $dbtype . '). This has been logged.');
    }
}

// Attempt to connect to the master database
$master_db_handle = try_connect_database();

// Only connect to the slave database if it is enabled
if ($config['database']['slave']['enabled'] == TRUE)
{
    $slave_db_handle = try_connect_database('slave');
}