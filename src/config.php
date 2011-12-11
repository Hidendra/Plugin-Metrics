<?php

// TODO fix me
ini_set('display_errors', 1);
error_reporting(E_ALL);

// main database info
$config['database']['driver'] = 'mysql';
$config['database']['host'] = 'localhost';
$config['database']['dbname'] = 'lwc';

// auth credentials for the database if it requires it
$config['database']['username'] = 'root';
$config['database']['password'] = '';