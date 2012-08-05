<?php
define('ROOT', './');
session_start();

require_once ROOT . 'config.php';
require_once ROOT . 'includes/func.php';

$graphPercent = graph_generator_percentage();

echo $graphPercent == null ? 0 : $graphPercent;