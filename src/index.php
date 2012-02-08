<?php
define('ROOT', './');

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

    <head>
        <title>Metrics</title>
        <link href="/static/css/main.css" rel="stylesheet" type="text/css" />
    </head>

    <body>

        <div align="center">
            <h2> Plugin Metrics </h2>
            <p> Plugins with zero active servers (last 24 hrs) are omitted from this list. </p>

            <table>

                <tr> <th> Plugin </th> <th> Servers (last 24 hrs) </th> </tr>

<?php
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

        echo '<tr> <td> <a href="/plugin/' . $plugin->getName() . '">' . $plugin->getName() . '</a> </td> <td> ' . number_format($servers) . ' </td> </tr>';
    }
?>
            </table>
        </div>

        <!-- Footer -->
        <div align="center" style="font-size: 11px; margin-top: 30px;">
            <p> Created by Hidendra. Plugins are owned by their respective authors, I simply provide statistical data. <br/>
            <a href="http://forums.bukkit.org/threads/53449/">Bukkit Thread</a>. Can't access the thread? <a href="mailto:hidendra@griefcraft.com">Concerns/feedback/etc</a> </p>
        </div>
    </body>
</html>