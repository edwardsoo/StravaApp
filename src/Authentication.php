<?php
/**
 * Created by JetBrains PhpStorm.
 * User: edwards
 * Date: 2013-05-01
 * Time: 3:30 PM
 * To change this template use File | Settings | File Templates.
 */

require_once('db.php');

/*
 *  =============================================
 *   URL Parameters passed to the app iframe
 *  =============================================
 *   lang=en
 *   theme=blue_steel // blue_steel, magnum, classic
 *   timezone=7200
 *   uid=1234567      // HootSuite user ID
 *   i=1234567        // user identifier (optionally entered by user upon App installation)
 *   ts=1318362023    // timestamp
 *   token=123abc...  // security token (sha1 hash)
*/

// App secret key
$auth_secret = 'gbIkxZxICe7qqtGtOLx2';

$_user = array(
    'authenticated' => false, // Hootsuite authentication
    'connected' => false, // Strava login
);

/*
 * HootSuite single sign-on
 * Verify user is logged into Hootsuite by hashing with SHA-1 (user id, timestamp, secret key)
 * and comparing to a token
 */
function verify_authentication($i, $ts, $token)
{
    global $_user;
    global $auth_secret;
    $hash = sha1($i . $ts . $auth_secret);
    if ($hash == $token) {
        $_user['authenticated'] = true;
    }
}

/*
 * Verify user has connection to Strava for this stream
 */
function verify_connection($uid, $pid)
{
    global $_user;
    // user-stream specific id
    $user_stream = $uid . '-' . $pid;
    $account_data = db::get($user_stream);
    if(is_array($account_data)) {
        // append stream specific data to _user array
        array_merge($_user, $account_data);
        // user is connected
        $_user['connected'] = true;
    }
}
