<?php

define('ROOT', '../public_html/');
define('MAX_COLUMNS', 50); // soft limit of max amount of columns to loop through per plugin
define('MAX_CHILDREN', 30); // the maximum amount of children that can be started

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

// copy timeline data
$statement = get_slave_db_handle()->prepare('INSERT INTO CustomDataTimeline (Plugin, ColumnID, Sum, Count, Max, Min, Variance, StdDev, Avg, Epoch)
                                                                SELECT Plugin, ColumnID, Sum, Count, Max, Min, Variance, StdDev, Avg, Epoch FROM CustomDataTimelineScratch');
$statement->execute();

// empty the scratch table incase it failed to empty
$statement = get_slave_db_handle()->prepare('TRUNCATE CustomDataTimelineScratch');
$statement->execute();