<?php

define('ROOT', '../');
session_start();

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

if (!isset($_POST['submit']) || !isset($_GET['plugin']))
{
    header('Location: /admin/');
    exit;
}

/// Load the plugin
$plugin = loadPlugin($_GET['plugin']);

// Can we even admin it ?
if (!can_admin_plugin($plugin))
{
    header('Location: /admin/');
    exit;
}

//// We are keeping this shit simple
//// I am not making this uber complex with templates, we are just redirecting them off back to the plugin page
//// Screw them


/// Author
if (isset($_POST['authors']))
{
    // Strip out invalid characters
    $authorText = preg_replace('/[^a-zA-Z0-9_,\- ]+/', '', $_POST['authors']);
    $plugin->setAuthors($authorText);
}


/// Save the plugin
$plugin->save();

/// Redirect them back to the view
header('Location: /admin/plugin/' . $plugin->getName() . '/view');