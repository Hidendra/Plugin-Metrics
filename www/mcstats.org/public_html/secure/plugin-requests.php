<?php
define('ROOT', '../');
session_start();

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

/// Templating
$page_title = 'Plugin Metrics :: Secure';
send_header();


send_footer();