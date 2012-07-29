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

    // At the moment we only we basic authentication
    $real_username = check_login($username, $password);

    if ($real_username === FALSE)
    {
        /// Throw out an error first
        echo '<div class="alert alert-error">The username or password you have entered is incorrect.</div>';

        /// Resend the login form
        send_login();
    }

    else
    {
        echo '<div class="alert alert-success">You have now been logged in. If you are not automatically redirected, click <a href="/admin/">here</a></div>
              <meta http-equiv="refresh" content="2; /admin/" /> ';

        $_SESSION['loggedin'] = 1;
        $_SESSION['username'] = $real_username;
    }

}
else
{
    send_login();
}

send_footer();

function send_login()
{
echo '
            <div class="row-fluid">

                <div class="hero-unit">
                    <div class="offset4">
                        <p>Login to access the administrative interface</p>

                        <form action="" method="post">
                            <div class="control-group">
                                <div class="controls">
                                    <input type="text" name="username" value="" placeholder="Username" /> <br/>
                                    <input type="password" name="password" value="" placeholder="Password" />
                                </div>
                            </div>

                            <div class="control-group">
                                <div class="controls">
                                    <input type="submit" name="submit" value="Login" class="btn btn-success btn-large" />
                                </div>
                            </div>
                        </form>
                        
                        <p><a href="/admin/register.php">Need an account?</a></p>
                    </div>
                </div>

            </div>
';
}