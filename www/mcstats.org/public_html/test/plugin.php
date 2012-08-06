<?php
define('ROOT', '../');
session_start();

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

$pluginName = isset($_GET['plugin']) ? $_GET['plugin'] : NULL;

if ($pluginName == null)
{
    exit ('0');
}

$plugin = loadPlugin($pluginName);
echo $plugin === NULL ? 0 : 1;