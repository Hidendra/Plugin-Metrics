<?php

// while this can be ran from anywhere very easily and uses the public api because of that,
// we need func.php for caching to only cache until the next graphing interval
// when setup in other locations caching can be implemented and required instead of func.php
// just CACHE_UNTIL_NEXT_GRAPH would have to be replaced with your own expiry.
define ('ROOT', './');
require ROOT . 'config.php';
require ROOT . 'includes/func.php';

// The location of the metrics backend
define ('METRICS_BACKEND', 'http://metrics.griefcraft.com');

// The url of the API to use on the backend
define ('API_URL', '/api/1.0/');

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

// The plugin we are graphing
$pluginName = $_GET['plugin'];

// Check the cache for the signature
// If it already exists we can simply imagepng that shit
$cache_key = 'signature-' . strtolower($pluginName);
$cached_image = $cache->get($cache_key);

// Image found ?!?!?
if ($cached_image != NULL)
{
    exit ($cached_image);
}

// The servers plot
$serversX = array();

// The players plot
$playersX = array();

// Load the json data from the api
// First, basic plugin data
$pluginData = json_decode(file_get_contents(METRICS_BACKEND . API_URL . $pluginName), true);

// And secondly, player/server status
// returned in the format of $json['data']['players'] = [ [epoch, v], .. ] and also $json['data']['servers']
$globalData = json_decode(file_get_contents(METRICS_BACKEND . API_URL . $pluginName . '/graph/global'), true);

// Is the plugin invalid?
if (count($pluginData) == 0 || $pluginData['status'] == 'err')
{
    error_image('Error: ' . $pluginData['msg']);
}

// Create a new data set
$dataSet = new pData();

foreach ($globalData['data']['players'] as $data)
{
    $playersX[] = $data[1];
}

foreach ($globalData['data']['servers'] as $data)
{
    $serversX[] = $data[1];
}

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
// $graph->ReportWarnings('GD');
$graph->setFontProperties('tahoma.ttf', 8);
$graph->setGraphArea(60, 30, IMAGE_WIDTH - 20, IMAGE_HEIGHT - 30);
$graph->drawFilledRoundedRectangle(7, 7, IMAGE_WIDTH - 7, IMAGE_HEIGHT - 7, 5, 240, 240, 240);
$graph->drawRoundedRectangle(5, 5, IMAGE_WIDTH - 5, IMAGE_HEIGHT - 5, 5, 230, 230, 230);
$graph->drawGraphArea(250, 250, 250, true);
$graph->drawScale($dataSet->GetData(), $dataSet->GetDataDescription(), SCALE_NORMAL, 150, 150, 150, true, 0, 2);
// $graph->drawGrid(4, true, 230, 230, 230, 100);

// Draw the footer
$graph->setFontProperties('pf_arma_five.ttf', 6);
$title = sprintf('%s servers in the last 24 hours with %s all-time server starts  ', number_format($pluginData['servers'][24]), number_format($pluginData['starts']));
$graph->drawTextBox(60, IMAGE_HEIGHT - 25, IMAGE_WIDTH - 20, IMAGE_HEIGHT - 7, $title, 0, 255, 255, 255, ALIGN_RIGHT, true, 0, 0, 0, 30);

// Draw the data
$graph->drawFilledLineGraph($dataSet->GetData(), $dataSet->GetDataDescription(), 75, true);

// Draw legend
$graph->drawLegend(65, 35, $dataSet->GetDataDescription(), 255, 255, 255);

// Get the center of the image
$font = 'tahoma.ttf';
$bounding_box = imagettfbbox(11, 0, $font, $pluginData['name']);
$center_x = ceil((IMAGE_WIDTH - $bounding_box[2]) / 2);

// Draw the title there
$graph->setFontProperties($font, 11); // Switch to font size 10
$graph->drawTitle($center_x, 22, $pluginData['name'], 50, 50, 50);

// Stroke the image
$graphImage = $graph->Render('__handle');

// generate the image
$image = imagecreatetruecolor(IMAGE_WIDTH, IMAGE_HEIGHT);

// Some colors
$white = imagecolorallocate($image, 255, 255, 255);
$black = imagecolorallocate($image, 0, 0, 0);

// Fill the background with white
imagefilledrectangle($image, 0, 0, IMAGE_WIDTH, IMAGE_HEIGHT, $white);

// Copy our graph into the image
imagecopy($image, $graphImage, 0, 0, 0, 0, IMAGE_WIDTH, IMAGE_HEIGHT);

// Begin the output buffer to read in the image so we can cache it
ob_start();

// Render the image
imagepng($image);

// Get the buffer contents
$image_data = ob_get_contents();

// End and clean the buffer
ob_end_clean();

echo $image_data;

// cache the image until the next graphing period
$cache->set($cache_key, $image_data, CACHE_UNTIL_NEXT_GRAPH);

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
    imagettftext($image, 16, 0, 5, 25, $black, 'pf_arma_five.ttf', $text);

    // render and destroy the image
    imagepng($image);
    imagedestroy($image);
    exit;
}