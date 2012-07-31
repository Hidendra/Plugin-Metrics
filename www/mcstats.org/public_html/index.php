<?php
define('ROOT', './');

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

/// Templating
$page_title = 'Plugin Metrics :: Homepage';
$container_class = 'container';
send_header();

// vars used later on
$pluginCount = 0;
$serverCount = number_format(sumServersSinceLastUpdated());
$playerCount = number_format(sumPlayersSinceLastUpdated());

foreach (loadPlugins(PLUGIN_ORDER_POPULARITY) as $plugin)
{
    $count = $plugin->countServersLastUpdated(normalizeTime() - SECONDS_IN_DAY);
    if ($count > 0)
    {
        $pluginCount ++;
    }
}

echo <<<END

<div class="hero-unit">
    <h1 style="margin-bottom:10px; font-size:57px;">Glorious plugin stats.</h1>
    <p>MCStats / Plugin Metrics is the de-facto statistical engine for Minecraft, actively used by over <b>$pluginCount</b> plugins.</p>
    <p>Across the world, over <b>$playerCount</b> players have been seen <b>in the last 30 minutes</b> across <b>$serverCount</b> servers.</p>
    <p><a class="btn btn-primary btn-large" href="/plugin-list/" target="_blank">Plugin List &raquo;</a></p>
</div>

<div class="row" style="text-align: center;">
    <h1 style="margin-bottom:30px; font-size:40px;">4 of the top 100 plugins. Do you use them?</h1>
</div>

<div class="row" style="text-align: center;">
END;

$first = true;
foreach (loadPlugins(PLUGIN_ORDER_RANDOM_TOP100, 4) as $plugin)
{
    $name = htmlentities($plugin->getName());
    $authors = htmlentities($plugin->getAuthors());

    // check for spaces or commas (and if they exist, throw is (s) after Author
    $author_prepend = '';
    if (strstr($authors, ' ') !== FALSE || strstr($authors, ',') !== FALSE)
    {
        $author_prepend = '(s)';
    }

    echo '
    <div class="span3">
        <h2 style="margin-bottom:7px;"><b>' . $name . '</b></h2>
        <p>
            ' . (empty ($authors) ? '' : ('Author' . $author_prepend . ': ' . $authors)) . ' <br/>
            Started ' . number_format($plugin->getGlobalHits()) . ' times <br/>
            Servers (last 24 hrs): ' . number_format($plugin->countServersLastUpdated(normalizeTime() - SECONDS_IN_DAY)) . '
        </p>
        <p>
            <img src="/plugin-preview/' . $name . '" />
        </p>
        <p><a class="btn" href="/plugin/' . htmlentities($plugin->getName()) . '" target="_blank">More info &raquo;</a></p>
    </div>
';
    $first = false;
}

echo '</div>';

send_footer();

?>