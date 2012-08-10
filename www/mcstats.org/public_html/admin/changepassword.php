<?php

define('ROOT', '../');
session_start();

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

ensure_loggedin();

send_header();

if (isset($_POST['submit']))
{
    // Process login info
    $currpassword = $_POST['currpassword'];
    $password = $_POST['password'];
    $password2 = $_POST['password2'];

    if (strlen($password) < 3 || $password != $password2 || $password == $currpassword)
    {
        err ('Care to try again? :-)');
        send_change_password();
    } else
    {
        // the unique key prevents duplicate usernames but check first
        $statement = $master_db_handle->prepare('SELECT Password FROM Author where ID = ?');
        $statement->execute(array($_SESSION['uid']));

        if ($row = $statement->fetch())
        {
            $newPassword = sha1($password);

            if (sha1($currpassword) != $row['Password'])
            {
                err ('Invalid password.');
                send_change_password();
            } else
            {
                $statement = $master_db_handle->prepare('UPDATE Author SET Password = ? WHERE ID = ?');
                $statement->execute(array($newPassword, $_SESSION['uid']));
                echo '<div class="alert alert-success">Password changed successfully! If you are not automatically redirected, click <a href="/admin/">here</a></div>
              <meta http-equiv="refresh" content="2; /admin/" /> ';
            }
        }
    }


}
else
{
    send_change_password();
}

send_footer();

function send_change_password()
{
    echo '
            <div class="row-fluid">

                <div class="hero-unit">
                    <div class="offset4">

                        <form action="" method="post" class="form-horizontal">
                            <div class="control-group">
                                <label class="control-label" for="currpassword">Current Password</label>
                                <div class="controls">
                                    <input type="password" name="currpassword" />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label" for="password">New Password</label>
                                <div class="controls">
                                    <input type="password" name="password" />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label" for="password2">Confirm New Password</label>
                                <div class="controls">
                                    <input type="password" name="password2" />
                                </div>
                            </div>

                            <div class="control-group">
                                <div class="controls">
                                    <input type="submit" name="submit" value="Change Password" class="btn btn-success btn-large" />
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
';
}