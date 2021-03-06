<?php
/**
 * Created by JetBrains PhpStorm.
 * User: edwards
 * Date: 2013-04-29
 * Time: 5:16 PM
 * To change this template use File | Settings | File Templates.
 */

require_once "../src/StravaApiLib.php";
require_once 'PHPUnit/Autoload.php';

class ApiTest extends PHPUnit_Framework_TestCase
{
    protected $strava;
    protected $user;

    protected function setUp()
    {
        $this->strava = new StravaApiLib();
        $this->user = array();
        $response = $this->strava->login("edward.soo@hootsuite.com", "password");
        $result = $response->result;
        $this->user['token'] = & $result['token'];
        $this->user['athlete'] = & $result['athlete'];

        //echo "token = ".$result['token'].PHP_EOL;
    }

    public function testLogin()
    {
        // Incorrect password
        $response = $this->strava->login("edward.soo@hootsuite.com", "passwor");
        $this->assertTrue($response->authFailed);

        // Correct login credentials
        $response = $this->strava->login("edward.soo@hootsuite.com", "password");
        $this->assertFalse($response->authFailed);
        $this->assertNull($response->error);
        $result = $response->result;
        $this->assertArrayHasKey('token', $result);
    }

    /*
     * @depends testLogin
     */
    public function testRidesIndex()
    {
        $response = $this->strava->ridesIndex($this->user['athlete']['id'], 10);
        $result = $response->result;

        // Token usable
        $this->assertFalse($response->authFailed);
        $this->assertNull($response->error);

        // Has result
        $this->assertArrayHasKey('rides', $result);
    }

    /*
     * @depends testRidesIndex
     */
    public function testGetMapDetails()
    {
        // Get list of rides
        $response = $this->strava->ridesIndex($this->user['athlete']['id']);
        $result = $response->result;

        if (count($result['rides']) > 0) {
            $ride = $result['rides'][0];
            $response = $this->strava->getMapDetails($this->user['token'], $ride['id']);
            $result = $response->result;

            if (isset($response->error) && $response->error['code'] == 400) {
                return;
            }
            $this->assertArrayHasKey('id', $result);
            $this->assertArrayHasKey('latlng', $result);
        }
    }

    /*
     * @depends testLogin
     */
    public function testCreateRide()
    {
        // Fake data
        $data = array();
        $fields = array('time', 'latitude', 'longitude', 'elevation', 'h_accuracy', 'v_accuracy');
        $data[] = new DataField(date(DATE_ATOM, mktime(0, 0, 0, 4, 30, 2013)), 49.26382, -123.10432, 109, 10, 10);
        $data[] = new DataField(date(DATE_ATOM, mktime(1, 0, 0, 4, 30, 2013)), 49.26382, -123.10432, 109, 10, 10);

        $response = $this->strava->createRide($this->user['token'], $fields, $data, 'test ride');
        $result = $response->result;

        $this->assertNull($response->error);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('upload_id', $result);

    }

    /*
     * @depends testCreateRide
     */
    public function testGetUploadStatus()
    {
        // Fake data
        $data = array();
        $fields = array('time', 'latitude', 'longitude', 'elevation', 'h_accuracy', 'v_accuracy');
        $data[] = new DataField(date(DATE_ATOM, mktime(0, 0, 0, 4, 30, 2013)), 49.26382, -123.10432, 109, 10, 10).getValueArray();
        $data[] = new DataField(date(DATE_ATOM, mktime(1, 0, 0, 4, 30, 2013)), 49.26382, -123.10432, 109, 10, 10).getValueArray();

        $response = $this->strava->createRide($this->user['token'], $fields, $data, 'test ride');
        $uploadResult = $response->result;
        $response = $this->strava->getUploadStatus($this->user['token'], $uploadResult['upload_id']);
        $statusResult = $response->result;
        $this->assertArrayHasKey('id', $statusResult);
        $this->assertArrayHasKey('upload_status', $statusResult);

    }

    /*
     * @depends testRidesIndex
     */
    public function testShowRide()
    {
        // Get list of rides
        $response = $this->strava->ridesIndex($this->user['athlete']['id']);
        $result = $response->result;

        if (count($result['rides']) > 0) {
            $ride = $result['rides'][0];
            $response = $this->strava->showRide($ride['id']);
            $result = $response->result;

            $this->assertNull($response->error);
            $this->assertArrayHasKey('id', $result);
            $this->assertArrayHasKey('ride', $result);
        }
    }
}

?>