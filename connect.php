<?php
/**
 * Created by JetBrains PhpStorm.
 * User: edwards
 * Date: 2013-05-01
 * Time: 5:13 PM
 * To change this template use File | Settings | File Templates.
 */

error_reporting(E_ERROR | E_WARNING | E_PARSE);

require_once('src/Authentication.php');
require_once('src/StravaApiLib.php');

session_start();

verify_authentication($_GET['i'], $_GET['ts'], $_GET['token']);
if (!$_user['authenticated'] || !(!empty($_GET['uid']) && !empty($_GET['pid']))) {
    ?>
    <html>
    <head>
        <title>Login | Strava</title>
    </head>
    <body>
    <script type="text/javascript">
        window.close();
    </script>
    </body>
    </html>
    <?php
    exit();
}
$user_stream = $_GET['uid'] . '-' . $_GET['pid'];
if (!empty($_POST['email']) && !empty($_POST['password'])) {
    // Call Strava login API
    $strava = new StravaApiLib();
    $response = $strava->login($_POST['email'], $_POST['password']);
    if (is_null($response->error) && isset($response->result['token'])) {
        $account_data = array(
            'connected_user_name' => $response->result['athlete']['name'],
            'connected_user_id' => $response->result['athlete']['id'],
            'connected_user_token' => $response->result['token']
        );
        // Store authenticated Strava user info so user does not need to login again
        db::put($user_stream, $account_data);
    } else {
        $login_error = true;
    }

} else {
    // Try to get account data
    $account_data = db::get($user_stream);
}
?>

<html>
    <head>
        <title>
            Login | Strava
        </title>
        <link href="https://d26ifou2tyrp3u.cloudfront.net/assets/application-99ce040b43b04612b1cfa1baef01fc1f.css"
              media="screen" rel="stylesheet" type="text/css"/>
    </head>
<body class="cycling-background-1 full-width fullscreen logged-out responsive">

<?php if (is_array($account_data)) { ?>
    <script type="text/javascript">
        window.opener.location.reload();
        window.close();
    </script>
<?php } else { ?>
    <div class="container">
        <header>
            <nav class="user-bar">
                <div class="inner-content">
                    <div class="branding"><a href="/" class="strava-logo md"
                                             title="Return to the Strava home page">Strava</a></div>
                    <ul class="user-nav">
                        <li class="logged_out_nav"><a target="_blank" href="https://www.strava.com/register/free">Sign Up</a></li>
                    </ul>
                </div>
            </nav>
        </header>
        <div class="page">
            <div class="pageContent">
                <div class="message page-status-message" style="display:none;">Loading …</div>
                <div class="login-panel">
                    <h1>
                        Log In
                    </h1>
                    <?php if ($login_error) { ?>
                        <div class="error message simple">
                            <p>The username or password did not match. Please try again.</p>
                        </div>
                    <?php } ?>
                    <form accept-charset="UTF-8" class="website" id="login_form" method="post">
                        <label class="placeholder-label" for="email" style="display: block;"></label>
                        <input id="email" name="email" type="email" placeholder="Email Address"
                               style="min-height: 30px">
                        <label class="placeholder-label" for="password" style="display: block;"></label>
                        <input id="password" name="password" type="password" placeholder="Password"
                               style="min-height: 30px">
                        <button class="alt" type="submit">Log In</button>
                    </form>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
    </body>
    </html>
<?php } ?>