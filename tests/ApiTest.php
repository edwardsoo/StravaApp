<?php
/**
 * Created by JetBrains PhpStorm.
 * User: edwards
 * Date: 2013-04-29
 * Time: 5:16 PM
 * To change this template use File | Settings | File Templates.
 */

require_once "../src/StravaApiLib.php";
require_once "../src/UserAuthentication.php";
require_once 'PHPUnit/Autoload.php';

class ApiTest extends PHPUnit_Framework_TestCase
{
    protected $strava;
    protected $user;

    protected function setUp()
    {
        $this->strava = new StravaApiLib();
        $this->user = new UserAuthentication();
        $response = $this->strava->login("edward.soo@hootsuite.com", "password");
        $result = $response->result;
        $this->user->token = &$result['token'];
        $this->user->athlete = &$result['athlete'];
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
    public function testRidesIndex() {
        $response = $this->strava->ridesIndex($this->user->athlete['id']);
        $result = $response->result;

        // Token usable
        $this->assertFalse($response->authFailed);
        $this->assertNull($response->error);

        // Has result
        $this->assertArrayHasKey('rides', $result);
    }
}

?>