<?php
define('ROOT', './');

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

// Cache until the next interval
header('Cache-Control: public, s-maxage=' . (timeUntilNextGraph() - time()));

/// Templating
$page_title = 'Plugin Metrics';
$container_class = 'container-fluid';
send_header();

echo '

            <script src="http://static.mcstats.org/javascript/highcharts/highcharts.js" type="text/javascript"></script>
            <script src="http://static.mcstats.org/javascript/highcharts/highstock.js" type="text/javascript"></script>
            <script src="http://static.mcstats.org/javascript/highcharts/themes/grid.js" type="text/javascript"></script>

            <div class="row-fluid" style="text-align: center; margin-bottom: 15px;">
                    <h2> Plugin Metrics </h2>
                    <p> Plugins with zero active servers (last 24 hrs) are omitted from this list. </p>
';

// get last updated
$timelast = getTimeLast();

// display the time since the last graph update
if($timelast > 0) {
    $lastUpdate = floor($timelast / 60);
    $nextUpdate = $config['graph']['interval'] - $lastUpdate;

echo '
                    <p> Last update: ' . $lastUpdate . ' minutes ago <br/>
                        Next update in: ' . $nextUpdate . ' minutes</p>
';
}

echo '
            </div>

            <div class="row-fluid">

                <div class="span6" style="width: 20%; margin-left: 20px;">

                    <table class="table table-striped table-bordered table-condensed">
                        <thead>
                            <tr> <th> Plugin </th> <th> Servers (last 24 hrs) </th> </tr>
                        </thead>

                        <tbody>
';

// If the show more link should be shown
$showMoreServers = false;

foreach (loadPlugins() as $plugin)
{
    if ($plugin->isHidden()) {
        continue;
    }

    // Count the amount of servers in the last 24 hours
    $servers = $plugin->countServersLastUpdated(time() - SECONDS_IN_DAY);

    // Omit Servers with 0
    if ($servers == 0) {
        continue;
    }

    if ($servers < 10) {
        $showMoreServers = true;
    }

    $format = number_format($servers);

    if ($plugin->getName() == 'VanishNoPacket') {
        $format = '<b>' . $format . '</b>';
    }

    echo '                          <tr' . ($servers < 10 ? ' class="hide-server"' : '') . '> <td> <a href="/plugin/' . $plugin->getName() . '">' . $plugin->getName() . '</a> </td> <td> ' . $format . ' </td> </tr>
';
}
echo '
                        </tbody>';

if ($showMoreServers) {
    echo '                    <tr class="more-servers" onclick="showMoreServers();"> <td> <a href="javascript:showMoreServers();">More...</a> </td> <td> </td> </tr>';
}

echo '
                    </table>
                </div>
                
                <div class="span6" style="width: 75%;">
                <div id="GlobalServerChart" style="height:500"></div>
                <br />
                <div id="GlobalCountryPieChart" style="height:500"></div>

                    <script>
';

// Load the global plugin
$globalPlugin = loadPluginByID(GLOBAL_PLUGIN_ID);

/// Create the global server chart
$globalServersChart = new Graph(-1, $globalPlugin, GraphType::Area);
$globalServersChart->setName('All Servers reporting to Metrics');
$playersSeries = new HighRollerSeriesData();
$serversSeries = new HighRollerSeriesData();
$globalServersChart->addSeries($playersSeries->addName('Players')->addData(DataGenerator::generatePlayerChartData($globalPlugin)));
$globalServersChart->addSeries($serversSeries->addName('Servers')->addData(DataGenerator::generateServerChartData($globalPlugin)));

/// Create the countries chart
$globalCountryChart = new Graph(-1, $globalPlugin, GraphType::Pie);
$globalCountryChart->setName('Server Locations');
$countrySeries = new HighRollerSeriesData();
$globalCountryChart->addSeries($countrySeries->addName('Country')->addData(DataGenerator::generateCountryChartData($globalPlugin)));

// And render it
echo $globalServersChart->generateGraph('GlobalServerChart');
echo $globalCountryChart->generateGraph('GlobalCountryPieChart');


echo '              </script>
                </div>
            </div>';

/// Templating
send_footer();