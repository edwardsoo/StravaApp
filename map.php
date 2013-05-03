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
        html {
            height: 100%
        }

        body {
            height: 100%;
            margin: 0;
            padding: 0
        }

        #map-canvas {
            height: 100%
        }
    </style>
    <?php if ($_SERVER['HTTPS'] == 'on'): ?>
        <script type="text/javascript" src="https://d2l6uygi1pgnys.cloudfront.net/jsapi/0-5/hsp.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.6/jquery.min.js" type="text/javascript"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.6/jquery.min.js" type="text/javascript"></script>
    <?php else: ?>
        <script type="text/javascript" src="http://static.hootsuite.com/jsapi/0-5/hsp.js"></script>
        <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6/jquery.min.js" type="text/javascript"></script>
        <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6/jquery.min.js" type="text/javascript"></script>
    <?php endif; ?>

    <script type="text/javascript">
        function initialize() {
            var mapOptions = {
                mapTypeId: google.maps.MapTypeId.ROADMAP
            };
            var map = new google.maps.Map(document.getElementById("map-canvas"),
                mapOptions);
            var params = {
                action: 'get_ride_route',
                hs_params: <?php
            echo json_encode(array(
             'uid'   => $_GET['uid'],
             'pid'   => $_GET['pid'],
             'i'     => $_GET['i'],
             'ts'    => $_GET['ts'],
             'token' => $_GET['token'],
             'theme' => $_GET['theme'],
             ));
            ?>,
                ride_id: <?php echo $_GET['ride_id']; ?>
            };

            $.post('ajax.php', params, function (data) {
                var ride = data;
                var markers = new Array();
                var coords = new Array();
                var latlngBounds = new google.maps.LatLngBounds();
                for (var i = 0; i < ride.latlng.length; i++) {
                    var latlng = new google.maps.LatLng(ride.latlng[i][0], ride.latlng[i][1])
                    coords.push(latlng);
                    latlngBounds.extend(latlng);
                }
                if (coords.length) {
                    markers.push(new google.maps.Marker({
                        position: coords[0],
                        map: map,
                        icon: 'https://chart.googleapis.com/chart?chst=d_map_pin_letter&chld=s|00FF00|000000'
                    }));
                    if (coords.length > 1) {
                        markers.push(new google.maps.Marker({
                            position: coords[coords.length - 1],
                            map: map,
                            icon: 'https://chart.googleapis.com/chart?chst=d_map_pin_icon&chld=glyphish_flag|00CCFF'
                        }));
                    }
                }
                var path = new google.maps.Polyline({
                    path: coords,
                    strokeColor: "#FF0000",
                    strokeOpacity: 1.0,
                    strokeWeight: 2
                });
                path.setMap(map);
                map.setCenter(latlngBounds.getCenter());
                map.fitBounds(latlngBounds);
            });
        }
        google.maps.event.addDomListener(window, 'load', initialize);
    </script>
</head>
<body>
<div class="hs_messageContent" id="map-canvas"/>
</body>
</html>