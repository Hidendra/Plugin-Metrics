<?php

define('ROOT', '../');
session_start();

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

ensure_loggedin();

/// Is this an ajax call?
$ajax = isset($_GET['ajax']) || isset($_SERVER['HTTP_X_PJAX']) || isset($_SERVER['X-PJAX']);

// If not........
if (!$ajax)
{
    send_header();
}

/// Check for the plugin in $_GET
if (!isset($_GET['plugin']))
{
    err ('No plugin provided.');
}

else
{
    // Load the provided plugin
    $plugin = loadPlugin($_GET['plugin']);

    /// Can we access it?
    if (!can_admin_plugin($plugin))
    {
        err ('Invalid plugin.');
    }

    else
    {
?>

<?php
    if (!$ajax)
    {
        echo '            <div class="row-fluid">
';
        send_admin_sidebar();
        echo '
                <div class="span8" id="plugin-content">';
    }
?>

                    <div class="span5">

                        <form action="/admin/plugin/<?php echo $plugin->getName(); ?>/update" method="post" class="form-horizontal">
                            <legend>
                                Plugin information
                            </legend>

                            <div class="control-group">
                                <label class="control-label" for="name">Plugin name</label>

                                <div class="controls">
                                    <input type="text" name="name" value="<?php echo $plugin->getName(); ?>" id="name" disabled />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label" for="authors">Authors</label>

                                <div class="controls">
                                    <input type="text" name="authors" value="<?php echo $plugin->getAuthors(); ?>" id="authors" />
                                </div>
                            </div>
                            <?php
                            <<< END
                            <div class="control-group">
                                <label class="control-label">Graphs</label>

                                <div class="controls">

                                    <div class="row-fluid">
                                        <div class="btn-toolbar">

                                            <div class="btn-group">
                                                <a class="btn btn-info dropdown-toggle" data-toggle="dropdown" href="#">
                                                    Default
                                                    <span class="caret"></span>
                                                </a>

                                                <ul class="dropdown-menu">
                                                    <li><a href="#"><i class="icon-ok" /> Line</a></li>
                                                    <li><a href="#">Area</a></li>
                                                    <li><a href="#">Column</a></li>
                                                    <li><a href="#">Pie</a></li>
                                                </ul>
                                            </div>

                                        </div>

                                        <ul id="sort1" class="sort">
                                            <li class="ui-state-default">Total protections</li>
                                            <li class="ui-state-default">Private protections</li>
                                        </ul>
                                    </div>

                                    <div class="row-fluid">
                                        <div class="btn-toolbar">

                                            <div class="btn-group">
                                                <a class="btn btn-info dropdown-toggle" data-toggle="dropdown" href="#">
                                                    Percentage of server using Economy
                                                    <span class="caret"></span>
                                                </a>

                                                <ul class="dropdown-menu">
                                                    <li><a href="#"><i class="icon-ok" /> Line</a></li>
                                                    <li><a href="#">Area</a></li>
                                                    <li><a href="#">Column</a></li>
                                                    <li><a href="#">Pie</a></li>
                                                </ul>
                                            </div>

                                        </div>

                                        <ul id="sort2" class="sort">
                                            <li class="ui-state-default">Password protections</li>
                                            <li class="ui-state-default">Public protections</li>
                                        </ul>
                                    </div>

                                    <style>
                                        #sort1, #sort2 { font-size: 85%; list-style-type: none; margin: 0; padding: 0 0 1em; float: left; margin-right: 10px; }
                                        #sort1 li, #sort2 li { margin: 0 5px 5px 5px; padding: 5px; font-size: 1.2em; width: 120px; }
                                    </style>
                                    <script>
                                        $(function() {
                                            $("#sort1, #sort2").sortable({
                                                connectWith: ".sort"
                                            }).disableSelection();
                                        });
                                    </script>

                                </div>
                            </div>
END;
?>
                            <div class="form-actions">
                                <input type="submit" name="submit" value="Save" class="btn btn-primary" />
                                <a href="/admin/" class="btn">Cancel</a>
                            </div>

                        </form>

                    </div>

                <?php
                if (!$ajax)
                {
                    echo '
                </div>

            </div>';
                }
                ?>


<?php
    }

}

if (!$ajax)
{
    send_footer();
}