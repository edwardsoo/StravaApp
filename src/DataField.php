<?php
/**
 * Created by JetBrains PhpStorm.
 * User: edwards
 * Date: 2013-04-30
 * Time: 2:17 PM
 * To change this template use File | Settings | File Templates.
 */

class DataField
{
    /*
     * $time - string, in format of YYYY-MM-DDThh:mm:ssTZD, as specified in ISO 8601
     * $lat - float, latitude of point
     * $lng - float, longitude of point
     * $ele - integer, elevation of point
     * $h_acc - integer, horizontal accuracy of point in meter
     * $v_acc - integer, vertical accuracy of coordinate in meter
     */
    function __construct($time, $lat, $lng, $ele, $h_acc, $v_acc)
    {
        $this->time = $time;
        $this->latitude = $lat;
        $this->longitude = $lng;
        $this->elevation = $ele;
        $this->h_accuracy = $h_acc;
        $this->v_accuracy = $v_acc;
    }

    /*
     * Return values as an array
     */
    public function getValueArray() {
        return array_values((array) $this);
    }
}