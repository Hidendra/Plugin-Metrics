<?php

define ('HOURS', 168);

define ('ROOT', './');
require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

// set the search path for fonts
putenv('GDFONTPATH=' . realpath('../fonts/'));

// The image's height
define('IMAGE_HEIGHT', 124);

// The image's width
define('IMAGE_WIDTH', 478);

// We will be outputting a PNG image!
header ('Content-type: image/png');

if (!isset($_GET['plugin']))
{
    error_image('Error: No plugin provided');
}

// Required requirements
require 'pChart/pData.class.php';
require 'pChart/pChart.class.php';
require 'pChart/pCache.class.php';

// The plugin we are graphing
$pluginName = urldecode($_GET['plugin']);

// Load the json data from the api
// First, basic plugin data
$plugin = loadPlugin($pluginName);

// Is the plugin invalid?
if ($plugin == null)
{
    // no plugin found
    error_image('Invalid plugin');
}

// case-correct plugin name
$pluginName = $plugin->getName();

// Create a new data set
$dataSet = new pData();

// The servers plot
$serversX = array();

// The players plot
$playersX = array();
$graph_data = array(); // epoch => [ "servers" => v, "players" => v ]

foreach (DataGenerator::generatePlayerChartData($plugin, HOURS) as $data)
{
    $epoch = $data[0];
    $value = $data[1];

    $graph_data[$epoch]['players'] = $value;
}

foreach (DataGenerator::generateServerChartData($plugin, HOURS) as $data)
{
    $epoch = $data[0];
    $value = $data[1];

    $graph_data[$epoch]['servers'] = $value;
}

foreach ($graph_data as $epoch => $data)
{
    // Ignore missing data
    if (count($data) != 2)
    {
        continue;
    }

    // Add it
    $playersX[] = $data['players'];
    $serversX[] = $data['servers'];
}

// Free up some memory
unset($graph_data);

// Add the data to the graph
$dataSet->AddPoint($playersX, 'Serie1');
$dataSet->AddPoint($serversX, 'Serie2');

// Create the series
$dataSet->AddSerie('Serie1');
$dataSet->AddSerie('Serie2');
$dataSet->SetSerieName('Players', 'Serie1');
$dataSet->SetSerieName('Servers', 'Serie2');
$dataSet->SetYAxisName('');

// Add all of the series
$dataSet->AddAllSeries();

// Set us up the bomb
$graph = new pChart(IMAGE_WIDTH, IMAGE_HEIGHT);
$graph->setFontProperties('tahoma.ttf', 8);
$graph->setGraphArea(45, 10, IMAGE_WIDTH - 5, IMAGE_HEIGHT - 5);
// $graph->drawGraphArea(255, 255, 255);
$graph->drawScale($dataSet->GetData(), $dataSet->GetDataDescription(), SCALE_START0, 150, 150, 150, true, 0, 0);
// $graph->drawGrid(4, true, 230, 230, 230, 100);

// Draw the data
$graph->drawFilledLineGraph($dataSet->GetData(), $dataSet->GetDataDescription(), 75, true);

// Stroke the image
$graphImage = $graph->Render('__handle');

// generate the image
$image = imagecreatetruecolor(IMAGE_WIDTH, IMAGE_HEIGHT);

// Some colors
$white = imagecolorallocate($image, 255, 255, 255);
$black = imagecolorallocate($image, 0, 0, 0);

// Make white transparent
imagecolortransparent($image, $white);

// Fill the background with white
imagefilledrectangle($image, 0, 0, IMAGE_WIDTH, IMAGE_HEIGHT, $white);

// Copy our graph into the image
imagecopy($image, $graphImage, 0, 0, 0, 0, IMAGE_WIDTH, IMAGE_HEIGHT);

imagepng($image);

// Destroy it
imagedestroy($image);

/**
 * Create an error image, send it to the client, and then exit
 *
 * @param $text
 */
function error_image($text)
{
    // allocate image
    $image = imagecreatetruecolor(IMAGE_WIDTH, IMAGE_HEIGHT);

    // create some colours
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);

    // draw teh background
    imagefilledrectangle($image, 0, 0, IMAGE_WIDTH, IMAGE_HEIGHT, $white);

    // write the text
    imagettftext($image, 16, 0, 5, 25, $black, '../fonts/pf_arma_five.ttf', $text);

    // render and destroy the image
    imagepng($image);
    imagedestroy($image);
    exit;
}