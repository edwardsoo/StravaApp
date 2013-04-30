<?php
/**
 * Created by JetBrains PhpStorm.
 * User: edwards
 * Date: 2013-04-29
 * Time: 3:40 PM
 * To change this template use File | Settings | File Templates.
 */

class StravaApiLib
{
    const strava_api_v1 = "http://www.strava.com/api/v1/";
    const strava_api_v2 = "http://www.strava.com/api/v2/";

    public function login($email, $password)
    {
        $request = new HttpRequest(self::strava_api_v2, HttpRequest::METH_POST);
        $request->addPostFields(array('email' => $email, 'password' => $password));
        try {
            $request->send();
            echo $request->getBody();
            echo $request->getResponseBody();
        } catch (HttpException $e) {
            echo $e;
        }
        return $request->getResponseCode();
    }

}