<?php
define('ROOT', './');

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

if (!isset($_GET['plugin']))
{
    exit('ERR No plugin provided.');
}

// Load the plugin
$plugin = loadPlugin($_GET['plugin']);

// Doesn't exist
if ($plugin === NULL)
{
    exit('ERR Invalid plugin.');
}

$name = $plugin->getName(); ?>

<html>
    <head>
        <title><?php echo $name; ?> Statistics</title>
        <style>
            table
            {
                font-family: "Lucida Sans Unicode", "Lucida Grande", Sans-Serif;
                font-size: 12px;
                background: #fff;
                border-collapse: collapse;
                text-align: left;
            }
            table th
            {
                font-size: 14px;
                font-weight: normal;
                color: #039;
                padding: 10px 8px;
                border-bottom: 2px solid #6678b1;
            }
            table td
            {
                color: #669;
                padding: 9px 8px 0px 8px;
            }
            table tbody tr:hover td
            {
                color: #009;
            }
        </style>

        <script type="text/javascript" src="https://www.google.com/jsapi"></script>
        <script type="text/javascript">
            google.load("jquery", "1.7.1");
            google.load('visualization', '1.0', {'packages':['corechart']});
            google.setOnLoadCallback(drawCharts);

            /**
             * Convert epoch time to a Date object to be used for graphing
             * @param epoch
             * @return Date
             */
            function epochToDate(epoch)
            {
                var date = new Date(0);
                date.setUTCSeconds(epoch);
                return date;
            }

            /**
             * Draw the charts on the page
             */
            function drawCharts()
            {
                generateCoverage();

                // last 2 weeks
                generateTimeline(14, '14day_timeline');

                // last month
                generateTimeline(31, '31day_timeline');
            }

            /**
             * Generate the timeline coverage for player/server counts
             */
            function generateCoverage()
            {
                $.getJSON('/coverage/<?php echo $name; ?>/144', function(json) {
                    var graph = new google.visualization.DataTable();
                    graph.addColumn('datetime', 'Day');
                    graph.addColumn('number', 'Active Servers');
                    graph.addColumn('number', 'Active Players');

                    // iterate through the JSON data
                    $.each(json, function(i, v) {
                        // extract data
                        date = epochToDate(parseInt(v.epoch));
                        servers = parseInt(v.servers);
                        players = parseInt(v.players);

                        // add it to the graph
                        graph.addRow([date, servers, players]);
                    });

                    var options = {
                        width: 950, height: 500,
                        title: 'Global Statistics'
                    };

                    var chart = new google.visualization.LineChart(document.getElementById('coverage_timeline'));
                    chart.draw(graph, options);
                });
            }

            /**
             * Generate a timeline for X days
             * @param days
             * @param div
             */
            function generateTimeline(days, div)
            {
                $.getJSON('/timeline/<?php echo $name; ?>/' + days, function(json) {
                    var graph = new google.visualization.DataTable();
                    graph.addColumn('date', 'Day');
                    graph.addColumn('number', 'Changes');

                    // iterate through the JSON data
                    $.each(json, function(i, v) {
                        // extract data
                        date = epochToDate(parseInt(v.epoch));
                        changes = parseInt(v.changes);

                        // add it to the graph
                        graph.addRow([date, changes]);
                    });

                    var options = {
                        width: 950, height: 340,
                        title: days + '-day version timeline'
                    };

                    var chart = new google.visualization.LineChart(document.getElementById(div));
                    chart.draw(graph, options);
                });
            }
        </script>
    </head>

<?php
echo '    <body>
        <h3>Plugin information</h3>
        <table>
            <tr> <td> Name </td> <td> ' . $name . ' </td> </tr>
            <tr> <td> Global starts </td> <td> ' . number_format($plugin->getGlobalHits()) . ' </td> </tr>
        </table>

        <h3>Servers using ' . $name . '</h3>
        <table>
            <tr> <td> All-time </td> <td> ' . number_format($plugin->countServers()) . ' </td> </tr>
            <tr> <td> Last hour </td> <td> ' . number_format($plugin->countServersLastUpdated(time() - SECONDS_IN_HOUR)) . ' </td> </tr>
            <tr> <td> Last 12 hrs </td> <td> ' . number_format($plugin->countServersLastUpdated(time() - SECONDS_IN_HALFDAY)) . ' </td> </tr>
            <tr> <td> Last 24 hrs </td> <td> ' . number_format($plugin->countServersLastUpdated(time() - SECONDS_IN_DAY)) . ' </td> </tr>
            <tr> <td> Last 7 days </td> <td> ' . number_format($plugin->countServersLastUpdated(time() - SECONDS_IN_WEEK)) . ' </td> </tr>
            <tr> <td> This month </td> <td> ' . number_format($plugin->countServersLastUpdated(strtotime(date('m').'/01/' . date('Y') . ' 00:00:00'))) . ' </td> </tr>
        </table>

        <div id="coverage_timeline" style="width:950; height:500"></div>

        <h3>Servers\' last known version</h3>
        <p> Versions with less than 5 servers are omitted. <br/> Servers not using LWC in the last 7 days are also omitted. </p>
        <table>
';

foreach ($plugin->getVersions() as $version)
{
    $count = $plugin->countServersUsingVersion($version);

    if ($count < 5)
    {
        continue;
    }

    echo '            <tr> <td>' . $version . '</td> <td>' . $count . '</td> </tr>
';
}
?>
        </table>
        <br/>

        <div id="14day_timeline" style="width:950; height:400"></div>
        <div id="31day_timeline" style="width:950; height:400"></div>
    </body>
</html>