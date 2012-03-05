<?php
define('ROOT', './');

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

/// Templating
$page_title = 'Plugin Metrics';
$container_class = 'container';
send_header();

echo '
            <div class="row">

                <div class="span6 offset3" style="text-align: center">
                    <h2> Plugin Metrics </h2>
                    <p> Plugins with zero active servers (last 24 hrs) are omitted from this list. </p>

                    <table class="table table-striped table-bordered table-condensed">
                        <thead>
                            <tr> <th> Plugin </th> <th> Servers (last 24 hrs) </th> </tr>
                        </thead>

                        <tbody>
';

    foreach (loadPlugins() as $plugin)
    {
        if ($plugin->isHidden())
        {
            continue;
        }

        // Count the amount of servers in the last 24 hours
        $servers = $plugin->countServersLastUpdated(time() - SECONDS_IN_DAY);

        // Omit Servers with 0
        if ($servers == 0)
        {
            continue;
        }

        echo '                          <tr> <td> <a href="/plugin/' . $plugin->getName() . '">' . $plugin->getName() . '</a> </td> <td> ' . number_format($servers) . ' </td> </tr>
';
    }
echo '
                        </tbody>
                    </table>
                </div>
            </div>';

/// Templating
send_footer();