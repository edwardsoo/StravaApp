<?php
/**
 * Created by JetBrains PhpStorm.
 * User: edwards
 * Date: 2013-05-01
 * Time: 5:13 PM
 * To change this template use File | Settings | File Templates.
 */

error_reporting(E_ERROR | E_WARNING | E_PARSE);

require_once('Authentication.php');
require_once('StravaApiLib.php');

session_start();

verify_authentication($_GET['i'], $_GET['ts'], $_GET['token']);
if (!$_user['authenticated']) {
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
if (!empty($_POST['username']) && !empty($_POST['password'])) {
    // Call Strava login API
    $strava = new StravaApiLib();
    $response = $strava->login($_POST['username'], $_POST['password']);
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
<body>

<?php if (is_array($account_data)): ?>
    <script type="text/javascript">
        window.opener.location.reload();
        window.close();
    </script>
<?php else: ?>
</body>
</html>
<?php endif ?>
