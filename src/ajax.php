<?php
/**
 * Created by JetBrains PhpStorm.
 * User: edwards
 * Date: 2013-05-01
 * Time: 3:26 PM
 * To change this template use File | Settings | File Templates.
 */
require_once('StravaApiLib.php');
require_once('Authentication.php');

/*
 * Some pre-processing
 */

$_POST['offset'] = (int)$_POST['offset'];
$_POST['limit'] = (int)$_POST['limit'];
if (empty($_POST['limit'])) {
    $_POST['limit'] = 15;
}

$strava = new StravaApiLib();
const BAD_TOKEN_ERR = 0;
const BAD_TOKEN_MSG = 'Your Strava authentication token has expired. Please re-connect.';
const OTHER_ERR = 1;
const OTHER_MSG = 'An error has occured, please try again later.';
$bad_token_resp = array(
    'errno' => BAD_TOKEN,
    'message' => BAD_TOKEN_MSG
);
$other_resp = array(
    'errno' => OTHER_ERR,
    'message' => OTHER_MSG
);

function handle_strava_api_response($response)
{
    global $bad_token_resp;
    global $other_resp;
    if (is_null($response->error)) {
        echo json_encode($response->result);
    } else if ($response->authFailed) {
        echo json_encode($bad_token_resp);
    } else {
        echo json_encode($other_resp);
    }
}

error_reporting(E_ERROR | E_WARNING | E_PARSE);

session_start();

// Ajax caller should send along all parameters received from HootSuite via POST
if (
    empty($_POST['hs_params']['uid']) ||
    empty($_POST['hs_params']['i']) ||
    empty($_POST['hs_params']['ts']) ||
    empty($_POST['hs_params']['token']) ||
    empty($_POST['hs_params']['pid'])
) {
    header('HTTP/1.0 401 Unauthorized');
    exit();
}

// HS authentication
verify_authentication($_POST['hs_params']['i'], $_POST['hs_params']['ts'], $_POST['hs_params']['token']);
if (!$_user['authenticated']) {
    header('HTTP/1.0 401 Unauthorized');
    exit();
}
verify_connection($_POST['hs_params']['uid'], $_POST['hs_params']['pid']);


/*
 * Process request
 */

// debug
var_dump($_POST);

switch ($_POST['action']) {
    case 'login':
        $response = $strava->login($_POST['user_email'], $_POST['user_password']);
        break;
    case 'search_rides':
        $response = $strava->ridesIndex(null, $_POST['offset'], $_POST['athlete_name'], null, $_POST['start_date'], $_POST['end_date'], $_POST['start_id']);
        // limit if has result
        if (isset($response->result['rides'])) {
            $response->result = array_slice($response->result['rides'], 0, $_POST['limit']);
        }
        break;
    case 'get_rides':
        $athlete_id = $_user['connected_user_id'];
        $response = $strava->ridesIndex($athlete_id, $_POST['offset']);
        // limit if has result
        if (isset($response->result['rides'])) {
            $response->result = array_slice($response->result['rides'], 0, $_POST['limit']);
        }
        break;
    case 'show_ride':
        $response = $strava->showRide($_POST['ride_id']);
        break;
    case 'get_ride_route':
        $token = $_user['connected_user_token'];
        $response = $strava->getMapDetails($token, $_POST['ride_id']);
        break;
    case 'get_upload_status':
        $token = $_user['connected_user_token'];
        $response = $strava->getUploadStatus($token, $_POST['upload_id']);
        break;
    case 'create_ride':
        $token = $_user['connected_user_token'];
        $response = $strava->createRide($token, null, $_POST['activity_name'], $_POST['activity_type']);
        break;
    default:
        header('HTTP/1.0 404 Not found');
        exit();

}
handle_strava_api_response($response);

?>
