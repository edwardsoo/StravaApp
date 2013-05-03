<?php
/**
 * Created by JetBrains PhpStorm.
 * User: edwards
 * Date: 2013-05-02
 * Time: 4:50 PM
 * To change this template use File | Settings | File Templates.
 */

error_reporting(E_ERROR | E_WARNING | E_PARSE);

require_once('src/Authentication.php');
require_once('src/StravaApiLib.php');

session_start();

verify_authentication($_GET['i'], $_GET['ts'], $_GET['token']);
if (!$_user['authenticated'] || !(!empty($_GET['uid']) && !empty($_GET['pid']))) {
    ?>
    <html>
    <body>
    <script type="text/javascript">
        window.close();
    </script>
    </body>
    </html>
    <?php
    exit();
}
verify_connection($_GET['uid'], $_GET['pid']);
if (!$_user['connected']) {
    ?>
    <html>
    <body>
    <script type="text/javascript">
        window.close();
    </script>
    </body>
    </html>
    <?php
    exit();
}
?>
<html>
<head>
    <style type="text/css">
        html { height: 100% }
        body { height: 100%; margin: 0; padding: 0 }
        #map-canvas { height: 100% }
    </style>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?sensor=false">
    </script>
    <script type="text/javascript">
        function initialize() {
            var mapOptions = {
                center: new google.maps.LatLng(-34.397, 150.644),
                zoom: 8,
                mapTypeId: google.maps.MapTypeId.ROADMAP
            };
            var map = new google.maps.Map(document.getElementById("map-canvas"),
                mapOptions);
        }
        google.maps.event.addDomListener(window, 'load', initialize);
    </script>
</head>
<body>
<div class="hs_messageContent" id="map-canvas"/>
</body>
</html>