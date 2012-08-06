<?php

// Caching
$config['cache']['enabled'] = true;

// The amount of minutes between graphing intervals
$config['graph']['interval'] = 30;

// The separator to used in post requests for custom data
$config['graph']['separator'] = '~~';

// email config settings for gmail
$config['email']['username'] = 'noreply@mcstats.org';
$config['email']['password'] = '';

// Master database configuration
$config['database']['master'] = array(
    'hostname'  => '127.0.0.1',
    'dbname'    => 'metrics',
    'username'  => 'metrics',
    'password'  => ''
);

// Slave database configuration
// Most select queries are offloaded to the slave instead
$config['database']['slave'] = array(
    'enabled'   => FALSE,
    'hostname'  => '10.10.1.8',
    'dbname'    => 'metrics',
    'username'  => 'metrics',
    'password'  => ''
);