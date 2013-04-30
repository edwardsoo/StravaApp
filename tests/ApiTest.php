<?php
/**
 * Created by JetBrains PhpStorm.
 * User: edwards
 * Date: 2013-04-29
 * Time: 5:16 PM
 * To change this template use File | Settings | File Templates.
 */

require_once "../src/StravaApiLib.php";

class ApiTest extends PHPUnit_Framework_TestCase
{
    protected $strava;

    protected function setUp()
    {
        $this->strava = new StravaApiLib();
    }

    public function testLogin()
    {
        $this->assertEquals(200, $this->strava->login("edward.soo@hootsuite.com", "password"));
    }
}

?>