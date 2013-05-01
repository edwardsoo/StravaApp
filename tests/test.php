<?php
/**
 * Created by JetBrains PhpStorm.
 * User: edwards
 * Date: 2013-04-30
 * Time: 3:11 PM
 * To change this template use File | Settings | File Templates.
 */

require_once "../src/DataField.php";

$data = new DataField(date(DATE_ATOM, mktime(0,0,0,4,30,2013)),49.26382,-123.10432,109,10,10);
var_dump($data);

var_dump($data->getValueArray());

echo json_encode($data->getValueArray());