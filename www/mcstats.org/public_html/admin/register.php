<?php

define('ROOT', '../');
session_start();

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

send_header();

if (isset($_POST['submit']))
{
    // Process login info
    $username = $_POST['username'];
    $password = $_POST['password'];
    $password2 = $_POST['password2'];

    if (strlen($password) < 3 || $password != $password2  || $password == $username || !preg_match('/[a-zA-Z0-9 ]/', $username))
    {
        err ('Care to try again? :-)');
        send_add_plugin();
    } else
    {
        // Hash the password
        $hashed_password = sha1($password);

        // Create a database entry
        $statement = $master_db_handle->prepare('INSERT INTO Author (Name, Password, Created) VALUES (?, ?, ?)');
        $statement->execute(array($username, $hashed_password, time()));

        // Redirect them
        echo '<div class="alert alert-success">Registration complete! If you are not automatically redirected, click <a href="/admin/">here</a></div>
              <meta http-equiv="refresh" content="2; /admin/" /> ';
    }


}
else
{
    send_registration();
}

send_footer();

function send_registration()
{
    echo '
            <div class="row-fluid">

                <div class="hero-unit">
                    <div class="offset4">
                        <p>Once you complete registration, you will want to get access to your plugin.</p>
                        <p>To do this you will need to contact Hidendra, the best place to do this is on IRC: irc.esper.net #metrics</p>

                        <form action="" method="post">
                            <div class="control-group">
                                <div class="controls">
                                    <input type="text" name="username" value="" placeholder="Username" /> <br/>
                                    <input type="password" name="password" value="" placeholder="Password" /> <br/>
                                    <input type="password" name="password2" value="" placeholder="Confirm password" />
                                </div>
                            </div>

                            <div class="control-group">
                                <div class="controls">
                                    <input type="submit" name="submit" value="Register" class="btn btn-success btn-large" />
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
';
}