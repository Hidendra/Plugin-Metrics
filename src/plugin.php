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
        <link href="/static/css/main.css" rel="stylesheet" type="text/css" />

        <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
        <script src="/static/javascript/highcharts/highcharts.js" type="text/javascript"></script>
        <script src="/static/javascript/highcharts/themes/gray.js" type="text/javascript"></script>
        <script src="/static/javascript/charts.js" type="text/javascript"></script>
        <script type="text/javascript">
            function generateCustomData()
            {
                $.getJSON('/timeline-custom/<?php echo $name; ?>/144', function(json) {
                    var columnNames = {};
                    var columnData = {}; // columnData[id] = [date, xx, yy...]

                    // Add the columns
                    $.each(json.columns, function(i, v) {
                        columnNames[i] = v;
                        columnData[i] = [];
                    });

                    // iterate through the JSON data
                    $.each(json.data, function(i, v) {
                        // The graph row
                        var date = Date.parse(epochToDate(parseInt(i)));
                        var row = [date];

                        // Generate the data into the map
                        $.each(v, function(i, v) {
                            columnData[i].push([date, parseInt(v)]);
                        });
                    });

                    // Add the data to the graph
                    $.each(columnData, function(id, data) {
                        customGraphOptions.series.push(
                            {
                                name: columnNames[id],
                                data: data
                            }
                        );
                    });

                    customGraphOptions.title.text = 'Custom data for <?php echo $plugin->getName(); ?>';
                    customGraph = new Highcharts.Chart(customGraphOptions);
                });
            }

            /**
             * Generate the timeline coverage for player/server counts
             */
            function generateCoverage()
            {
                $.getJSON('/coverage/<?php echo $name; ?>/144', function(json) {
                    // Store all of the extracted data in an arrow
                    var allServers = [];
                    var allPlayers = [];

                    // iterate through the JSON data
                    $.each(json, function(i, v) {
                        // extract data
                        var date = Date.parse(epochToDate(parseInt(v.epoch)));
                        var servers = parseInt(v.servers);
                        var players = parseInt(v.players);

                        // add it to the graph
                        allServers.push([date, servers]);
                        allPlayers.push([date, players]);
                    });
                    console.log(allServers);
                    console.log(allPlayers);

                    globalStatisticsOptions.series.push({
                        name: 'Active Servers',
                        marker: {
                            radius: 3
                        },
                        data: allServers
                    });

                    globalStatisticsOptions.series.push({
                        name: 'Active Players',
                        marker: {
                            radius: 3
                        },
                        data: allPlayers
                    });
                    globalStatisticsOptions.title.text = 'Global Statistics for <?php echo $plugin->getName(); ?>';
                    globalStatistics = new Highcharts.Chart(globalStatisticsOptions);
                });
            }
        </script>
    </head>

<?php
echo '    <body>
        <h3>Plugin information</h3>
        <table>
            <tr> <td> Name </td> <td> ' . $name . ' </td> </tr>
            <tr> <td> Author </td> <td> ' . $plugin->getAuthor() . ' </td> </tr>
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
        </table> <br/>

        <div id="coverage_timeline" style="height:500"></div>
';

if (count($plugin->getCustomColumns()) > 0)
{
    echo '        <br/> <div id="custom_timeline" style="height:500"></div> <script> generateCustomData(); </script>
';
}

echo '        <h3>Servers\' last known version</h3>
        <p> Versions with less than 5 servers are omitted. <br/> Servers not using ' . $plugin->getName() . ' in the last 7 days are also omitted. </p>
        <table>
';

foreach ($plugin->getVersions() as $version)
{
    $count = $plugin->countServersUsingVersion($version);

    if ($count < 5)
    {
        continue;
    }

    echo '            <tr> <td>' . $version . '</td> <td>' . number_format($count) . '</td> </tr>
';
}
?>
        </table>
        <br/>
    </body>
</html>