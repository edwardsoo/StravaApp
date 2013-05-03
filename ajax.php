<?php
/**
 * Created by JetBrains PhpStorm.
 * User: edwards
 * Date: 2013-05-01
 * Time: 3:26 PM
 * To change this template use File | Settings | File Templates.
 */
require_once('src/StravaApiLib.php');
require_once('src/Authentication.php');

/*
 * Some pre-processing
 */

if (!empty($_POST['offset']))
    $_POST['offset'] = (int)$_POST['offset'];
if (!empty($_POST['limit']))
    $_POST['limit'] = (int)$_POST['limit'];
else {
    $_POST['limit'] = 15;
}

$strava = new StravaApiLib();
const BAD_TOKEN = 1;
const NO_MAP = 2;
const OTHER_ERR = 3;
const BAD_TOKEN_MSG = 'Your Strava authentication token has expired. Please re-connect.';
const NO_MAP_MSG = 'This activity does not have location.';
const OTHER_MSG = 'An error has occured, please try again later.';
$bad_token_resp = array(
    'error' => BAD_TOKEN_MSG,
    'errno' => BAD_TOKEN
    );
$no_map_resp = array(
    'error' => NO_MAP_MSG,
    'errno' => NO_MAP
    );
$other_resp = array(
    'error' => OTHER_MSG,
    'errno' => OTHER_ERR
    );

function handle_strava_api_response($response)
{
    global $bad_token_resp;
    global $other_resp;
    header('Content-type: application/json');
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

switch ($_POST['action']) {
    case 'login':
    $response = $strava->login($_POST['user_email'], $_POST['user_password']);
    handle_strava_api_response($response);
    break;
    case 'search_rides':
    $response = $strava->ridesIndex(null, $_POST['offset'], $_POST['athlete_name'], null, $_POST['start_date'], $_POST['end_date'], $_POST['start_id']);
        // limit if has result
    if (isset($response->result['rides'])) {
        $response->result['rides'] = array_slice($response->result['rides'], 0, $_POST['limit']);
    }
    handle_strava_api_response($response);
    break;
    case 'get_rides':
    $athlete_id = $_user['connected_user_id'];
    $response = $strava->ridesIndex($athlete_id, $_POST['offset'], null, null, $_POST['start_date'], $_POST['end_Date']);
        // limit if has result
    if (isset($response->result['rides'])) {
        $response->result['rides'] = array_slice($response->result['rides'], 0, $_POST['limit']);
        foreach ($response->result['rides'] as &$ride) {
            $show_resp = $strava->showRide($ride['id']);
            if (is_null($show_resp->error)) {
                $ride = array_merge($ride, $show_resp->result['ride']);
            }
            if (isset($ride['start_date_local'])) {
                $ride['ts'] = strtotime($ride['start_date_local']);
            }
        }
    }
    handle_strava_api_response($response);
    break;
    case 'show_ride':
    $response = $strava->showRide($_POST['ride_id']);
    if (isset($response->result['ride']['start_date_local'])) {
        $response->result['ride']['ts'] = strtotime($response->result['ride']['start_date_local']);
    }
    handle_strava_api_response($response);
    break;
    case 'get_ride_route':
    $token = $_user['connected_user_token'];
    $response = $strava->getMapDetails($token, $_POST['ride_id']);
    if ($response->error['code'] == 400) {
        header('Content-type: application/json');
        echo json_encode($no_map_resp);
    } else {
        handle_strava_api_response($response);
    }
    break;
    case 'get_upload_status':
    $token = $_user['connected_user_token'];
    $response = $strava->getUploadStatus($token, $_POST['upload_id']);
    handle_strava_api_response($response);
    break;
    case 'create_ride':
    $token = $_user['connected_user_token'];
    $response = $strava->createRide($token, null, $_POST['activity_name'], $_POST['activity_type']);
    handle_strava_api_response($response);
    break;
    default:
    header('HTTP/1.0 404 Not found');
    exit();

}

?>
