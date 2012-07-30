<?php
define('ROOT', './');

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

// Cache until the next interval
header('Cache-Control: public, s-maxage=' . (timeUntilNextGraph() - time()));

// get the current page
$currentPage = 1;

if (isset($_GET['page']))
{
    $currentPage = intval($_GET['page']);
}

// If the show more link should be shown
$showMoreServers = false;

// number of pages
$totalPages = ceil(count(loadPlugins(PLUGIN_ORDER_POPULARITY)) / PLUGIN_LIST_RESULTS_PER_PAGE);

// offset is how many plugins to start after
$offset = ($currentPage - 1) * PLUGIN_LIST_RESULTS_PER_PAGE;

if ($currentPage > $totalPages)
{
    header('Location: /plugin-list/' . $totalPages . '/');
    exit;
}

/// Templating
$page_title = 'Plugin Metrics :: Plugin List';
$container_class = 'container-fluid';
send_header();

echo '

            <script src="http://test.static.mcstats.org/javascript/highcharts/highcharts.js" type="text/javascript"></script>
            <script src="http://test.static.mcstats.org/javascript/highcharts/highstock.js" type="text/javascript"></script>
            <script src="http://test.static.mcstats.org/javascript/highcharts/themes/simplex.js" type="text/javascript"></script>

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

                <div class="span4" style="width: 300px;">

                    <table class="table table-striped table-bordered table-condensed" id="plugin-list">
                        <thead>
                            <tr> <th style="text-align: center; width: 20px;">Rank <br/> &nbsp; </th> <th style="text-align: center; width: 160px;"> Plugin <br/> &nbsp; </th> <th style="text-align: center; width: 100px;"> Servers<br/> <span style="font-size: 10px;">(last 24 hrs)</span> </th> </tr>
                        </thead>

                        <tbody>
';

$step = 1;
foreach (loadPlugins(PLUGIN_ORDER_POPULARITY, PLUGIN_LIST_RESULTS_PER_PAGE, $offset) as $plugin)
{
    if ($plugin->isHidden()) {
        continue;
    }

    // calculate this plugin's rank
    $rank = $offset + $step;

    // Count the amount of servers in the last 24 hours
    $servers = $plugin->countServersLastUpdated(normalizeTime() - SECONDS_IN_DAY);

    // Omit Servers with 0
    if ($servers == 0) {
        continue;
    }

    if ($rank <= 10) {
        $rank = '<b>' . $rank . '</b>';
    }

    $format = number_format($servers);

    echo '                          <tr id="plugin-list-item"> <td style="text-align: center;">' . $rank . ' </td> <td> <a href="/plugin/' . $plugin->getName() . '" target="_blank">' . $plugin->getName() . '</a> </td> <td style="text-align: center;"> ' . $format . ' </td> </tr>
';
    $step ++;
}

echo '                          <tr>
                                    <td style="text-align: center;" id="plugin-list-page-number"> <span id="plugin-list-current-page">' . $currentPage . '</span>/<span id="plugin-list-max-pages">' . $totalPages . '</span> </td>
                                    <td style="text-align: center;"> <a href="#" class="btn btn-mini" id="plugin-list-back" onclick="movePluginListBack()" style="' . ($currentPage == 1 ? 'display: none; ' : '') . 'margin: 0;"><i class="icon-arrow-left"></i> Back</a> <a href="#" class="btn btn-mini" id="plugin-list-forward" onclick="movePluginListForward()" style="margin: 0;"><i class="icon-arrow-right"></i> Forward</a> </td>
                                    <td style="text-align: center;"> <input class="input-mini" type="text" value="' . $currentPage . '" id="plugin-list-goto-page" style="height: 12px; margin: 0; width: 20px; text-align: center;" /> <a href="#" class="btn btn-mini" id="plugin-list-go" onclick="loadPluginListPage($(\'#plugin-list-goto-page\').val());">Go <i class="icon-share-alt"></i></a> </td>
                                </tr>

                        </tbody>';

echo '
                    </table>
                </div>

                <div style="margin-left: 310px;">
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