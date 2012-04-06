<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

    <head>
        <meta charset="utf-8" />
        <title><?php global $page_title; echo (isset($page_title) ? $page_title : 'Metrics - Admin'); ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="description" content="" />
        <meta name="author" content="Tyler Blair <hidendra@griefcraft.com>" />

        <link href="http://static.griefcraft.com/css/bootstrap.css" rel="stylesheet" />
        <link href="http://static.griefcraft.com/css/bootstrap-responsive.css" rel="stylesheet" />
        <link href="http://static.griefcraft.com/css/ui-lightness/jquery-ui.css" rel="stylesheet" />

        <script src="http://static.griefcraft.com/javascript/jquery.js" type="text/javascript"></script>
        <script src="http://static.griefcraft.com/javascript/jquery.pjax.js" type="text/javascript"></script>
        <script src="http://static.griefcraft.com/javascript/jquery-ui.js" type="text/javascript"></script>
    </head> <?php flush(); ?>

    <body>
<?php

// Find out our current working directory
$cwd = getcwd();

// Are we on the admin ui?
// if we are on the admin ui we want to always send the navbar
$is_in_admin_ui = str_endswith('admin', $cwd);

if ($is_in_admin_ui || is_loggedin())
{

    echo '
        <div class="navbar navbar-fixed-top">

            <div class="navbar-inner">
                <div class="container-fluid" style="width: auto;">

                    <a class="brand" href="/">Plugin Metrics</a>
                    
                    <ul class="nav">
                        <li' . ($is_in_admin_ui ? '' : ' class="active"') . '>
                            <a href="/">Home</a>
                        </li>
                        <li' . ($is_in_admin_ui ? ' class="active"' : '') . '>
                            <a href="/admin/">Admin</a>
                        </li>
                    </ul> ';

if (is_loggedin())
    echo '
                    <ul class="nav pull-right">
                        <li><a href="/admin/logout.php" >Logout</a></li>
                    </ul> ';
echo '
                </div>
            </div>

        </div>';


} else
{
    echo '
        <br />';
}

?>

        <div class="<?php global $container_class; echo (isset($container_class) ? $container_class : 'container-fluid'); ?>">
