<?php

error_reporting(0);
@ini_set('display_errors', 0);

// The amount of minutes between graphing intervals
$config['graph']['interval'] = 30;

// main database info
$config['database']['driver'] = 'mysql';
$config['database']['host'] = 'localhost';
$config['database']['dbname'] = 'lwc';

// auth credentials for the database if it requires it
$config['database']['username'] = 'root';
$config['database']['password'] = '';