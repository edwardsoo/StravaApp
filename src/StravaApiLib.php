<?php
/**
 * Created by JetBrains PhpStorm.
 * User: edwards
 * Date: 2013-04-29
 * Time: 3:40 PM
 * To change this template use File | Settings | File Templates.
 */

require_once "RestApiResponse.php";

class StravaApiLib
{
    const V1_API = "http://www.strava.com/api/v1/";
    const V2_API = "http://www.strava.com/api/v2/";

    public function login($email, $password)
    {
        $args[] = "email=$email";
        $args[] = "password=$password";

        return $this->curlPost(self::V2_API . 'authentication/login', $args);
    }

    public function ridesIndex($athleteId = null, $athleteName = null, $clubId = null, $startDate = null, $endDate = null, $startId = null)
    {
        $url = 'rides';

        $args = array();
        if ($clubId) $args[] = "clubId=$clubId";
        if ($athleteId) $args[] = "athleteId=$athleteId";
        if ($athleteName) $args[] = "athleteName=$athleteName";
        if ($startDate) $args[] = "startDate=$startDate";
        if ($endDate) $args[] = "endDate=$endDate";
        if ($startId) $args[] = "startId=$startId";

        echo "args count = ".count($args);
        if (count($args) > 0) {
            $url .= '?';
            $url .= implode('&', $args);
        }

        echo "URL: " . $url;
        return $this->curlGet(self::V1_API . $url);
    }

    /*
     * Default options
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

        if (count($args) > 0) {
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
        $data = curl_exec($ch);

        $errno = curl_errno($ch);
        if (!$errno) {
            $info = curl_getinfo($ch);
            if (isset($info['http_code']) && ((int)$info['http_code'] == 200)) {
                $response->result = json_decode($data, true);
                $response->authFailed = false;
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