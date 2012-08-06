<?php
define('ROOT', '../');
session_start();

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

if (isset($_POST['submit']))
{
    $authorID = $_POST['author'];
    $pluginID = $_POST['plugin'];
    $email = $_POST['email'];
    $action = $_POST['submit'];

    if ($action == 'Accept')
    {
        $statement = $master_db_handle->prepare('INSERT INTO AuthorACL (Author, Plugin) VALUES (?, ?)');
        $statement->execute(array($authorID, $pluginID));
    }

    // both actions require the request to become fulfilled
    $statement = $master_db_handle->prepare('DELETE FROM PluginRequest WHERE Author = ? and Plugin = ?');
    $statement->execute(array($authorID, $pluginID));

    // email them
    // TODO

    header('Location: /secure/plugin-requests.php');
    exit;
}

/// Templating
$page_title = 'Plugin Metrics :: Secure';
send_header();

echo '
<div class="row" style="margin-left: 15%;">
    <table class="table table-striped" style="width: 70%;">
        <thead>
            <tr>
                <th> Author (ID) </th>
                <th> Plugin (ID) </th>
                <th> Email </th>
                <th> Submitted </th>
                <th> Relative </th>
                <th> </th>
            </tr>
        </thead>

        <tbody>';


$statement = get_slave_db_handle()->prepare('SELECT
                                    -- Author rows
                                    Author.ID AS AuthorID, Author.Name AS AuthorName,

                                    -- Plugin, match resolvePlugin
                                    Plugin.ID AS ID, Parent, Plugin.Name AS Name, Plugin.Author AS Author, Hidden, GlobalHits, Plugin.Created AS Created,

                                    -- Generic
                                    Email, PluginRequest.Created AS RequestCreated FROM PluginRequest
                                    LEFT OUTER JOIN Author on Author.ID = PluginRequest.Author
                                    LEFT OUTER JOIN Plugin ON Plugin.ID = PluginRequest.Plugin
                                    ORDER BY Created desc');
$statement->execute();

while ($row = $statement->fetch())
{
    $authorID = $row['AuthorID'];
    $authorName = $row['AuthorName'];
    $pluginID = $row['Plugin'];
    $email = $row['Email'];
    $created = $row['RequestCreated'];

    // resolve the plugin
    $plugin = resolvePlugin($row);

    $safeAuthorName = htmlentities($row['AuthorName']);
    $safePluginName = htmlentities($plugin->getName());

    echo '
            <tr>
                <td>
                    ' . htmlentities($authorName) . ' (' . $authorID . ')
                </td>
                <td>
                    ' . htmlentities($plugin->getName()) . ' (' . $plugin->getID() . ')
                </td>
                <td>
                    ' . htmlentities($email) . '
                </td>
                <td>
                    ' . date('D, F d H:i T', $created) . '
                </td>
                <td>
                    <b>' . number_format(floor((time() - $created) / 60)) . '</b> minutes ago
                </td>
                <td>
                    <form action="" method="POST">
                        <input type="hidden" name="author" value="' . $authorID . '" />
                        <input type="hidden" name="plugin" value="' . $plugin->getID() . '" />
                        <input type="hidden" name="email" value="' . htmlentities($email) . '" />
                        <input type="submit" name="submit" class="btn btn-success" value="Accept"/>
                    </form>
                </td>
                <td>
                    <form action="" method="POST">
                        <input type="hidden" name="author" value="' . $authorID . '" />
                        <input type="hidden" name="plugin" value="' . $plugin->getID() . '" />
                        <input type="hidden" name="email" value="' . htmlentities($email) . '" />
                        <input type="submit" name="submit" class="btn btn-danger" value="Reject"/>
                    </form>
                </td>
            </tr>
';

}

echo '
        </tbody>
    </table>
</div>';


send_footer();