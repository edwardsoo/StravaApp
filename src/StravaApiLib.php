<?php
/**
 * Created by JetBrains PhpStorm.
 * User: edwards
 * Date: 2013-04-29
 * Time: 3:40 PM
 * To change this template use File | Settings | File Templates.
 */

require_once "RestApiResponse.php";
require_once "UserAuthentication.php";
require_once "DataField.php";
require_once "Activity.php";

class StravaApiLib
{
    // API URLs
    const V1_API = "http://www.strava.com/api/v1/";
    const V2_API = "http://www.strava.com/api/v2/";
    const LOGIN_URL = 'authentication/login';
    const RIDES_URL = 'rides';
    const UPLOAD_URL = 'upload';
    const UPLOAD_STATUS_URL = 'upload/status';

    /*
     * User authentication
     * $email - user login email
     * $password - user password
     */
    public function login($email, $password)
    {
        $url = self::V2_API . self::LOGIN_URL;
        $args[] = "email=$email";
        $args[] = "password=$password";

        return $this->curlPost($url, $args);
    }

    /*
     * Gets up to 50 rides, searches by athlete, club or date.
     * All arguments are optional
     */
    public function ridesIndex($athleteId = null, $athleteName = null, $clubId = null, $startDate = null, $endDate = null, $startId = null)
    {
        $url = self::V1_API . self::RIDES_URL;

        $args = array();
        if ($clubId) $args[] = "clubId=$clubId";
        if ($athleteId) $args[] = "athleteId=$athleteId";
        if ($athleteName) $args[] = "athleteName=$athleteName";
        if ($startDate) $args[] = "startDate=$startDate";
        if ($endDate) $args[] = "endDate=$endDate";
        if ($startId) $args[] = "startId=$startId";

        if (count($args) > 0) {
            $url .= '?';
            $url .= implode('&', $args);
        }

        return $this->curlGet($url);
    }

    /*
     * Create a new ride (or other activity)
     * $token - the authentication access token
     * $dataFieldArray - an array of ordered DataField
     * $activityName - Optional, name of activity
     * $activityType - Optional, type of activity, all valid values are listed in class Activity
     */
    public function createRide($token, $dataFieldArray, $activityName = null, $activityType = null)
    {
        $url = self::V2_API . self::UPLOAD_URL;

        $args = array();
        $args['token'] = $token;
        $args['type'] = "json";
        $args['data_fields'] = array('time', 'latitude', 'longitude', 'elevation', 'h_accuracy', 'v_accuracy');
        $data = array();
        foreach ($dataFieldArray as $dataField) {
            $data[] = $dataField->getValueArray();
        }
        $args['data'] = $data;

        if ($activityName) $args['activity_name'] = $activityName;
        if ($activityType) $args['activity_type'] = $activityType;

        $json = json_encode($args);

        return $this->curlPost($url, null, $json);
    }

    /*
     * Get status of an upload
     * $token - the authentication access token
     * $uploadId - the id of the upload
     */
    public function getUploadStatus($token, $uploadId)
    {
        $url = self::V2_API . self::UPLOAD_STATUS_URL . "/$uploadId";
        $args = array("token=$token");
        return $this->curlGet($url, $args);
    }

    /*
     * Get an array of lat/lng points of a ride and and array of effort segments covered by the points.
     * Each segment has an Id an 2 indices which index into the lat/lng array
     * $token - the authentication access token
     * $id - ride id
     */
    public function getMapDetails($token, $id)
    {
        $url = self::V2_API . self::RIDES_URL . "/$id/map_details";
        $args = array("token=$token");
        return $this->curlGet($url, $args);
    }


    /*
     * Get details about a specific ride
     */
    public function showRide($id)
    {
        $url = self::V2_API . self::RIDES_URL . "/$id";
        return $this->curlGet($url);

    }



    /**************************************************************************
     * Helper functions
     *************************************************************************/

    /*
     * Default options for cUrl
     */
    private function curlSetup()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        return $ch;
    }

    /*
     * Do HTTP POST method, supply urlencoded parameters with array $args or a JSON string $json
     */
    private function curlPost($url, $args = null, $json = null)
    {
        $ch = $this->curlSetup();
        curl_setopt($ch, CURLOPT_POST, true);

        if ($args && count($args) > 0) {
            $requestData = implode('&', $args);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);

        } else if ($json != null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        }
        curl_setopt($ch, CURLOPT_URL, $url);

        return $this->curlExec($ch);

    }


    /*
     * Do HTTP GET method, supply parameters with array $args
     */
    private function curlGet($url, $args = null)
    {
        $ch = $this->curlSetup();

        if (count($args) > 0) {
            $url .= '?' . implode('&', $args);
        }
        curl_setopt($ch, CURLOPT_URL, $url);

        return $this->curlExec($ch);

    }

    /*
     * Execute the cURL handler and return a RestApiResponse
     */
    private function curlExec($ch)
    {
        $response = new RestApiResponse();
        $data_json = curl_exec($ch);

        $errno = curl_errno($ch);
        if (!$errno) {
            $info = curl_getinfo($ch);
            if (isset($info['http_code']) && ((int)$info['http_code'] == 200)) {
                $result = json_decode($data_json, true);
                $response->authFailed = false;
                if ($result['error']) {
                    $response->error = array('message' => $result['error']);
                } else {
                    $response->result = $result;
                }
            } else {
                $response->error = array('code' => $info['http_code'], 'message' => 'HTTP exception');
                $response->authFailed = ((int)$info['http_code'] == 401);
            }
        } else {
            $response->error = array('code' => $errno, 'message' => curl_error($ch));
        }
        return $response;
    }
}