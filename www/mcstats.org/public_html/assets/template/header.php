<?php

// Find out our current working directory
$cwd = getcwd();

// Are we on the admin ui?
// if we are on the admin ui we want to always send the navbar
$is_in_admin_ui = str_endswith('admin', $cwd);

$show_navbar = $is_in_admin_ui || is_loggedin();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

    <head>
        <meta charset="utf-8" />
        <title><?php global $page_title; echo (isset($page_title) ? $page_title : 'Metrics - Admin'); ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="description" content="" />
        <meta name="author" content="Tyler Blair <hidendra@griefcraft.com>" />

        <link href="http://test.static.mcstats.org/css/bootstrap.min.css" rel="stylesheet" />
        <link href="http://test.static.mcstats.org/css/bootstrap-responsive.min.css" rel="stylesheet" />
        <link href="http://test.static.mcstats.org/css/fam-icons.css" rel="stylesheet" />
        <link href="http://test.static.mcstats.org/css/ui-lightness/jquery-ui.css" rel="stylesheet" />

        <script src="http://test.static.mcstats.org/javascript/jquery.js" type="text/javascript"></script>
        <script src="http://test.static.mcstats.org/javascript/jquery.pjax.js" type="text/javascript"></script>
        <script src="http://test.static.mcstats.org/javascript/jquery-ui.js" type="text/javascript"></script>
        <script src="http://test.static.mcstats.org/javascript/main.js" type="text/javascript"></script>

        <!-- charting scripts -->
        <script src="http://test.static.mcstats.org/javascript/highcharts/highcharts.js" type="text/javascript"></script>
        <script src="http://test.static.mcstats.org/javascript/highcharts/highstock.js" type="text/javascript"></script>
        <script src="http://test.static.mcstats.org/javascript/highcharts/themes/simplex.js" type="text/javascript"></script>

        <script type="text/javascript">
            // Google analytics
            var _gaq = _gaq || [];
            _gaq.push(['_setAccount', 'UA-31036792-1']);
            _gaq.push(['_setDomainName', 'mcstats.org']);
            _gaq.push(['_setAllowLinker', true]);
            _gaq.push(['_trackPageview']);

            (function() {
                var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
                ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
            })();
        </script>
    </head> <?php flush(); ?>

    <body<?php if ($show_navbar) echo ' style="padding-top: 50px;"';?>>
<?php

if ($show_navbar)
{
    if (is_loggedin())
    {
        $plugin_dropdown = '


                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">Plugins <b class="caret"></b></a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a href="/admin/add-plugin/">Add a Plugin</a>
                                </li>
                                <li class="divider"></li>';

        foreach (get_accessible_plugins() as $plugin)
        {
            $pluginName = htmlentities($plugin->getName());

            if ($plugin->getPendingAccess() !== TRUE)
            {
                $link = '<a href="/admin/plugin/' . $pluginName . '/view">' . $pluginName . '</a>';
            } else
            {
                $link = '<a href="#"><i>' . $pluginName . ' (Pending)</i></a>';
            }

            $plugin_dropdown .= '
                                <li>
                                    ' . $link . '
                                </li>';
        }

        $plugin_dropdown .= '
                            </ul>
                        </li>';
    } else
    {
        $plugin_dropdown = '';
    }

    echo '
        <div class="navbar navbar-fixed-top">

            <div class="navbar-inner">
                <div class="container-fluid" style="width: auto;">

                    <a class="brand" href="/">MCStats</a>
                    
                    <ul class="nav">
                        <li>
                            <a href="/">Home</a>
                        </li>
                        <li>
                            <a href="/plugin-list/">Plugin List</a>
                        </li>
                        <li' . ($is_in_admin_ui ? ' class="active"' : '') . '>
                            <a href="/admin/">Admin</a>
                        </li>
                        ' . $plugin_dropdown . '
                    </ul> ';

if (is_loggedin())
    echo '
                    <ul class="nav pull-right">
                        <li><a href="/admin/logout.php" >Logout (' . htmlentities($_SESSION['username']) . ')</a></li>
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

$graphPercent = graph_generator_percentage();

?>

        <div class="<?php global $container_class; echo (isset($container_class) ? $container_class : 'container-fluid'); ?>">

            <div class="row" id="graph-generator" style="text-align: center; width:50%; margin-left: 25%;<?php if ($graphPercent === NULL) echo ' display: none;'; ?>">
                <p>
                    <b> INFO: </b> Graphs are currently generating. Site performance may suffer.
                </p>

                <div class="progress progress-striped progress-success active">
                    <div class="bar" id="graph-generator-progress-bar" style="<?php if ($graphPercent !== NULL) echo 'width: ' . $graphPercent . '%;'; ?>"></div>
                </div>
            </div>
