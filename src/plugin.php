<?php

/// Todo fully remove other deprecated methods
/// Todo for the old way of generating graphs :D

define('ROOT', './');
session_start();

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

// Get the plugin name
$pluginName = $plugin->getName();
$encodedName = urlencode($pluginName); // encoded name, for use in signature url

/// Template hook
$page_title = $pluginName . ' Statistics';

/// Templating
send_header();

echo '
            <script>
                // Plugin-specific bindings
                var pluginName = "' . $pluginName . '";
            </script>

            <!-- Important scripts we want just for this page -->
            <script src="http://static.griefcraft.com/javascript/highcharts/highcharts.js" type="text/javascript"></script>
            <script src="http://static.griefcraft.com/javascript/highcharts/highstock.js" type="text/javascript"></script>
            <script src="http://static.griefcraft.com/javascript/highcharts/themes/grid.js" type="text/javascript"></script>

            <div id="row-fluid" style="width: 100%">

                <div class="span4" style="width: 20%">
                    <h3>Plugin information</h3>
';

// get last updated
$timelast = getTimeLast();

// display time last
if($timelast > 0){
echo '
                    <p> Minutes since last update: '.floor($timelast/60).' </p>
';
}

echo '
                    <table class="table table-striped">
                        <tbody>
                            <tr> <td> Name </td> <td> ' . $pluginName . ' </td> </tr>
                            <tr> <td> Author </td> <td> ' . $plugin->getAuthors() . ' </td> </tr>
                            <tr> <td> Global starts </td> <td> ' . number_format($plugin->getGlobalHits()) . ' </td> </tr>
                            <tr> <td> Signature </td> <td> <a href="/signature/' . strtolower($encodedName) . '.png" target="_blank">/signature/' . strtolower($encodedName) . '.png</a> </td> </tr>
                        </tbody>
                    </table>

                    <h3>Servers using ' . $pluginName . '</h3>
                    <table class="table table-striped">
                        <tbody>
                            <tr> <td> All-time </td> <td> ' . number_format($plugin->countServers()) . ' </td> </tr>
                            <tr> <td> Last hour </td> <td> ' . number_format($plugin->countServersLastUpdated(time() - SECONDS_IN_HOUR)) . ' </td> </tr>
                            <tr> <td> Last 12 hrs </td> <td> ' . number_format($plugin->countServersLastUpdated(time() - SECONDS_IN_HALFDAY)) . ' </td> </tr>
                            <tr> <td> Last 24 hrs </td> <td> ' . number_format($plugin->countServersLastUpdated(time() - SECONDS_IN_DAY)) . ' </td> </tr>
                            <tr> <td> Last 7 days </td> <td> ' . number_format($plugin->countServersLastUpdated(time() - SECONDS_IN_WEEK)) . ' </td> </tr>
                            <tr> <td> This month </td> <td> ' . number_format($plugin->countServersLastUpdated(strtotime(date('m').'/01/' . date('Y') . ' 00:00:00'))) . ' </td> </tr>
                        </tbody>
                    </table>

                    <h3>Servers\' last known version</h3>
                    <p> Versions with less than 5 servers are omitted. <br/> Servers not using ' . $plugin->getName() . ' in the last 7 days are also omitted. </p>
                    <table class="table table-striped">
                        <tbody>';

foreach ($plugin->getVersions() as $version)
{
    $count = $plugin->countServersUsingVersion($version);

    if ($count < 5)
    {
        continue;
    }

    echo '
                              <tr> <td>' . $version . '</td> <td>' . number_format($count) . '</td> </tr>';
}

echo '
                        </tbody>
                    </table>
                </div>

                <div class="span8" style="width: 75%">
                    <div id="PlayerServerChart" style="height:500"></div>
                    <div id="PlayerServerChart2" style="height:500"></div>
';

/// Load all of the custom graphs for the plugin
$activeGraphs = $plugin->getActiveGraphs();

/// Output a div for each one
$index = 1;
foreach ($activeGraphs as $activeGraph)
{
    echo '                    <br/> <div id="CustomChart' . $index++ . '" style="height:500"></div>
';
}

echo '                    <br/> <div id="CountryPieChart" style="height:500"></div>
                </div>

            </div>';

/// Flush before sending / generating graph data
flush();

/// Get some graphs up in hurr
echo '

            <script>';

/// Players/Servers chart
$playersAndServersChart = new Graph(-1, $plugin, GraphType::Area);
$playersAndServersChart->setName('Global Statistics');

// Add the serieses
$playersSeries = new HighRollerSeriesData();
$serversSeries = new HighRollerSeriesData();
$playersAndServersChart->addSeries($playersSeries->addName('Players')->addData(DataGenerator::generatePlayerChartData($plugin)));
$playersAndServersChart->addSeries($serversSeries->addName('Servers')->addData(DataGenerator::generateServerChartData($plugin)));

/// Countries chart
$countryChart = new Graph(-1, $plugin, GraphType::Pie);
$countryChart->setName('Server Locations');

// Add the series
$countrySeries = new HighRollerSeriesData();
$countryChart->addSeries($countrySeries->addName('Country')->addData(DataGenerator::generateCountryChartData($plugin)));

/// MULTIPLE CUSTOM GRAPHS YEAH TO THE POWER OF FUCK YEAH
// ITERATE THROUGH THE ACTIVE GRAPHS
$index = 1; // WE GIVE A UNIQUE NUMBER TO EACH CHART
foreach ($activeGraphs as $activeGraph)
{
    // ADD ALL OF THE SERIES PLOTS TO THE CHART
    if ($activeGraph->getType() != GraphType::Pie)
    {
        foreach ($activeGraph->getColumns() as $id => $columnName)
        {
            // GENERATE SOME DATA DIRECTLY TO THE CHART!
            $series = new HighRollerSeriesData();
            $activeGraph->addSeries($series->addName($columnName)->addData(DataGenerator::generateCustomChartData($activeGraph, $id)));
        }
    } else // Pie chart
    {
        $series = new HighRollerSeriesData();
        $seriesData = array();

        // Time !
        $baseEpoch = normalizeTime();
        $minimum = strtotime('-12 hours', $baseEpoch);

        // the amounts for each column
        $columnAmounts = array();

        foreach ($activeGraph->getColumns() as $id => $columnName)
        {
            // Get all of the data points
            $dataPoints = $activeGraph->getPlugin()->getTimelineCustom($id, $minimum);

            foreach ($dataPoints as $epoch => $dataPoint)
            {
                $generatedData[] = array($epoch, $dataPoint);
                $columnAmounts[$columnName] = $dataPoint;

                // We only want 1 :)
                break;
            }
        }

        // Now begin our magic
        arsort(&$columnAmounts);

        // Sum all of the points
        $data_sum = array_sum($columnAmounts);

        // Now convert it to %
        foreach ($columnAmounts as $columnName => $dataPoint)
        {
            $seriesData[] = array($columnName, round(($dataPoint / $data_sum) * 100, 2));
        }

        // Finalize
        $activeGraph->addSeries($series->addName('')->addData($seriesData));
    }

    // GENERATE THE GRAPH, OH HELL YEAH!
    echo $activeGraph->generateGraph('CustomChart' . $index++);
}

// Render the graphs
echo $playersAndServersChart->generateGraph('PlayerServerChart');
flush();
echo $countryChart->generateGraph('CountryPieChart');
flush();



echo '
            </script>';

/// Templating
send_footer();