<?php

// Caching
$config['cache']['enabled'] = true;

// The amount of minutes between graphing intervals
$config['graph']['interval'] = 30;

// The separator to used in post requests for custom data
$config['graph']['separator'] = '~~';

// main database info
$config['database']['driver'] = 'mysql';
$config['database']['host'] = 'localhost';
$config['database']['dbname'] = 'metrics';

// auth credentials for the database if it requires it
$config['database']['username'] = 'root';
$config['database']['password'] = '';
