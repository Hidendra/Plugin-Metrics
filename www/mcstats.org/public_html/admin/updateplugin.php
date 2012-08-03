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

// Graph data
if (isset($_POST['graph']))
{

    // Iterate through each graph
    // Each graph adds its ID to the graph array so we can easily iterate through each
    foreach ($_POST['graph'] as $graphID => $trash)
    {
        // Load the graph
        $graph = $plugin->getGraph($graphID);

        // No graph found, carry on
        if ($graph === NULL)
        {
            continue;
        }

        // don't allow editing for readonly graphs
        if ($graph->isReadOnly())
        {
            continue;
        }

        // Pull out tasty data
        $displayName = $_POST['displayName'][$graphID];
        $type = $_POST['type'][$graphID];
        $active = isset($_POST['active'][$graphID]) ? $_POST['active'][$graphID] : 0;
        $scale = $_POST['scale'][$graphID];

        // Validate active
        if ($active != 0 && $active != 1)
        {
            // Default to active
            $active = 1;
        }

        // Validate scale
        if ($scale != GraphScale::Linear && $scale != GraphScale::Logarithmic)
        {
            // default to linear
            $scale = GraphScale::Linear;
        }

        // Set them onto the graph
        $graph->setDisplayName($displayName);
        $graph->setType($type);
        $graph->setActive($active);
        $graph->setScale($scale);

        // Save the graph
        $graph->save();
    }
}


/// Save the plugin
$plugin->save();

/// Redirect them back to the view
header('Location: /admin/plugin/' . $plugin->getName() . '/view');