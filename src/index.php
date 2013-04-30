<?php
/**
 * Created by JetBrains PhpStorm.
 * User: edwards
 * Date: 2013-04-30
 * Time: 9:48 AM
 * To change this template use File | Settings | File Templates.
 */

require_once "../src/StravaApiLib.php";

$strava = new StravaApiLib();

$strava->login("edward.soo@hootsuite.com", "password");