<?php
/**
 * Created by JetBrains PhpStorm.
 * User: edwards
 * Date: 2013-05-01
 * Time: 5:36 PM
 * To change this template use File | Settings | File Templates.
 */

// TODO: store user_stream data on a db
class db {
    function get($key) {
        return $_SESSION['simulated_db'][ $key ];
    }
    function put($key, $val) {
        $_SESSION['simulated_db'][ $key ] = $val;
    }
    function delete($key) {
        unset($_SESSION['simulated_db'][ $key ]);
    }
}