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
            <h2>Plugin Metrics</h2>

            <table>

                <tr> <th> Plugin </th> <th> Servers (last 24 hrs) </th> </tr>

<?php
    foreach (loadPlugins() as $plugin)
    {
        if ($plugin->isHidden())
        {
            continue;
        }

        echo '<tr> <td> <a href="/plugin/' . $plugin->getName() . '">' . $plugin->getName() . '</a> </td> <td> ' . number_format($plugin->countServersLastUpdated(time() - SECONDS_IN_DAY)) . ' </td> </tr>';
    }
?>
            </table>
        </div>

    </body>
</html>