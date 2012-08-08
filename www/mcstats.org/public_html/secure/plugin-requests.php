<?php
define('ROOT', '../');
session_start();

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

if (isset($_POST['submit']))
{
    require_once 'Mail.php'; // pear-Mail
    require_once 'Mail/mime.php';

    $authorID = $_POST['author'];
    $pluginID = $_POST['plugin'];
    $email = trim($_POST['email']);
    $action = $_POST['submit'];
    $approved = $action == 'Accept';

    // load the plugin
    $plugin = loadPluginByID($pluginID);

    if ($approved)
    {
        $statement = $master_db_handle->prepare('UPDATE AuthorACL SET Pending = 0 WHERE Author = ? AND Plugin = ?');
        $statement->execute(array($authorID, $pluginID));
    } else {
        $statement = $master_db_handle->prepare('DELETE FROM AuthorACL WHERE Author = ? and Plugin = ?');
        $statement->execute(array($authorID, $pluginID));
    }

    // both actions require the request to become fulfilled
    $statement = $master_db_handle->prepare('DELETE FROM PluginRequest WHERE Author = ? and Plugin = ?');
    $statement->execute(array($authorID, $pluginID));

    // Should we send an email ?
    if (!empty($email))
    {
        // email params
        $pluginName = htmlentities($plugin->getName());
        $subject = sprintf('Plugin approval for %s: %s', $pluginName, $approved ? 'Approved!' : 'Rejected');
        if ($approved)
        {
            $body = <<<END
            <p style="margin:0 0 9px;font-size: 16px;">
                Hello,
            </p>
            <p style="margin:0 0 9px;">
                You recently submitted a plugin request for the plugin <b>$pluginName</b> which has been <b>approved</b>!
            </p>
            <p style="margin:0 0 9px;">
                You will now be able to access administrative functions for your plugin immediately. To go there, please click <a href="http://mcstats.org/admin/plugin/$pluginName/view">here</a>.
            </p>
            <p style="margin:0 0 9px;">
                If you have any questions at all or just want to relax please feel free to join us in IRC at <code style='padding:2px 4px;font-family:Menlo,Monaco,Consolas,"Courier New",monospace;font-size:12px;color:#d14;-webkit-border-radius:3px;-moz-border-radius:3px;border-radius:3px;background-color:#f7f7f9;border:1px solid #e1e1e8;'>irc.esper.net #metrics</code> anytime.
            </p>
            <p style="margin:0 0 9px;">
                Thank you,
            </p>
            <p style="margin:0 0 9px;">
                The MCStats.org Staff (currently 1 strong!)
            </p>
END;
        } else // Rejected
        {
            $body = <<<END
            <p style="margin:0 0 9px;font-size: 16px;">
                Hello,
            </p>
            <p style="margin:0 0 9px;">
                You recently submitted a plugin request for the plugin <b>$pluginName</b> which has been <b>rejected</b>.
            </p>
            <p style="margin:0 0 9px;">
                To ensure smooth processing, please ensure you provide a url to a <a href="http://dev.bukkit.org" style="color:#366ddc;text-decoration:none;">dev.bukkit.org</a> submission or a forum post
                (such as from <a href="http://forums.bukkit.org" style="color:#366ddc;text-decoration:none;">bukkit.org)</a> where this plugin's information/documentation can be found. This is done to help
                identify your plugin as a real plugin and to mostly ensure we add the correct person.
            </p>
            <p style="margin:0 0 9px;">
                When you are ready, please do <a href="/admin/add-plugin/" style="color:#366ddc;text-decoration:none;">resubmit</a> your plugin and hopefully we can get you added this time.
            </p>
            <p style="margin:0 0 9px;">
                If you still experience issues or would like a better explanation of why your request was rejected please visit us in IRC at <code style='padding:2px 4px;font-family:Menlo,Monaco,Consolas,"Courier New",monospace;font-size:12px;color:#d14;-webkit-border-radius:3px;-moz-border-radius:3px;border-radius:3px;background-color:#f7f7f9;border:1px solid #e1e1e8;'>irc.esper.net #metrics</code>
            </p>
            <p style="margin:0 0 9px;">
                Thank you,
            </p>
            <p style="margin:0 0 9px;">
                The MCStats.org Staff (currently 1 strong!)
            </p>
END;
        }

        $full_body = <<<END
<html>
<head>
	<meta charset="UTF-8" />
	<base href="http://mcstats.org/" />
	<title>MCStats</title>
</head>
<body style='margin:0;font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;font-size:13px;line-height:18px;color:#555555;background-color:#f3f3f3;'>

<br><div class="container-fluid" style="padding-right:20px;padding-left:20px;*zoom:1;">

    <div class="row-fluid" style="width:100%;">
        <div class="span6 well" style="min-height:20px;padding:19px;margin-bottom:20px;background-color:#ffffff;border:none;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px;-webkit-box-shadow:0 1px 1px rgba(0, 0, 0, 0.3);-moz-box-shadow:0 1px 1px rgba(0, 0, 0, 0.3);box-shadow:0 1px 1px rgba(0, 0, 0, 0.3);">
$body
        </div>
    </div>

    <footer class="row-fluid" style="display:block;width:100%;*zoom:1;"><hr style="margin:18px 0;border:0;border-top:1px solid #eeeeee;border-bottom:1px solid #ffffff;">
        <p style="margin:0 0 9px;"> MCStats backend created by Hidendra. Plugins are owned by their respective authors. </p>
        <p style="margin:0 0 9px;">  <a href="/plugin-list/" style="color:#366ddc;text-decoration:none;">plugin list</a> | <a href="/status/" style="color:#366ddc;text-decoration:none;">backend status</a> | <a href="/admin/" style="color:#366ddc;text-decoration:none;">admin</a> | <a href="http://github.com/Hidendra/mcstats.org" style="color:#366ddc;text-decoration:none;">github</a> | irc.esper.net #metrics </p>
    </footer>
</div>

</body>
</html>
END;



        // email them
        $headers = array ('From' => 'Tyler Blair <noreply@mcstats.org>', 'To' => $email, 'Subject' => $subject);
        $smtp = Mail::factory('smtp', array(
            'host' => 'ssl://smtp.gmail.com',
            'port' => '465',
            'auth' => true,
            'username' => $config['email']['username'],
            'password' => $config['email']['password']
        ));

        // create the email
        $mime = new Mail_mime("\n");

        // set the bodies
        $mime->setTXTBody(strip_tags($full_body));
        $mime->setHTMLBody($full_body);

        // send the email
        $mail = $smtp->send($email, $mime->headers($headers), $mime->get());

        if (PEAR::isError($mail))
        {
            error_log('SMTP error: ' . $mail->getMessage());
        }
    }

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
                <th> Author </th>
                <th> Plugin </th>
                <th> DBO </th>
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
                                    Email, DBO, PluginRequest.Created AS RequestCreated FROM PluginRequest
                                    LEFT OUTER JOIN Author on Author.ID = PluginRequest.Author
                                    LEFT OUTER JOIN Plugin ON Plugin.ID = PluginRequest.Plugin
                                    ORDER BY PluginRequest.Created ASC');
$statement->execute();

while ($row = $statement->fetch())
{
    $authorID = $row['AuthorID'];
    $authorName = $row['AuthorName'];
    $email = $row['Email'];
    $dbo = $row['DBO'];
    $created = $row['RequestCreated'];

    // resolve the plugin
    $plugin = resolvePlugin($row);

    if (strstr($dbo, 'http') !== FALSE || strstr($dbo, 'com') !== FALSE || strstr($dbo, 'org'))
    {
        $dbo_link = '<a href="' . htmlentities($dbo) . '" target="_blank">' . htmlentities($dbo) . '</a>';
    } else
    {
        $dbo_link = htmlentities($dbo);
    }

    $createdMinutesAgo = floor((time() - $created) / 60);
    if ($createdMinutesAgo < 0) $createdMinutesAgo = 0;

    echo '
            <tr>
                <td>
                    ' . htmlentities($authorName) . ' (' . $authorID . ')
                </td>
                <td>
                    ' . htmlentities($plugin->getName()) . ' (' . $plugin->getID() . ')
                </td>
                <td>
                    ' . $dbo_link . '
                </td>
                <td>
                    ' . htmlentities($email) . '
                </td>
                <td>
                    ' . date('D, F d H:i T', $created) . '
                </td>
                <td>
                    <b>' . number_format($createdMinutesAgo) . '</b> minutes ago
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