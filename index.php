<?php
/**
 * Created by JetBrains PhpStorm.
 * User: edwards
 * Date: 2013-04-30
 * Time: 9:48 AM
 * To change this template use File | Settings | File Templates.
 */

require_once('src/Authentication.php');

/*
 *  =============================================
 *   Constants
 *  =============================================
 */
$DEFAULT_THEME = 'blue_steel';
$HS_DASHBOARD = 'http://hootsuite.com/dashboard';

/*
 *  =============================================
 *   Helpers
 *  =============================================
 */
function query_without_vars($var)
{
    if (!is_array($var)) {
        $var = array($var);
    }
    $return = array();
    foreach ($_GET as $key => $val) {
        if (!in_array($key, $var)) {
            $return[] = "$key=$val";
        }
    }
    return implode('&', $return);
}

error_reporting(E_ERROR | E_WARNING | E_PARSE);
session_start();


// Redirect if unauthenticated
verify_authentication($_GET['i'], $_GET['ts'], $_GET['token']);
if (!$_user['authenticated']) {
    header("Location: $HS_DASHBOARD");
    exit();
}

// Get Strava connection
verify_connection($_GET['uid'], $_GET['pid']);

// disconnect requested
if (isset($_GET['disconnect'])) {
    db::delete($_GET['uid'] . '-' . $_GET['pid']);
    // redirect to self without the 'disconnect' parameter
    unset($_GET['disconnect']);
    header('Location: ?' . implode('&', $_GET));
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
    <?php
    // Load the HootSuite App Directory SDK JS Api
    // use different URLs when using https
    if ($_SERVER['HTTPS'] == 'on') {
        ?>
        <script type="text/javascript" src="https://d2l6uygi1pgnys.cloudfront.net/jsapi/0-5/hsp.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.6/jquery.min.js" type="text/javascript"></script>
        <?php
    }else {
        ?>
        <script type="text/javascript" src="http://static.hootsuite.com/jsapi/0-5/hsp.js"></script>
        <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6/jquery.min.js" type="text/javascript"></script>
        <?php
    }
    // Load CSS
    $theme = $_GET['theme'];
    if (is_null($theme) || empty($theme)) {
        $theme = $DEFAULT_THEME;
    }
    ?>
    <link rel='stylesheet' href='css/<?php echo $theme; ?>.css' type="text/css" media="screen"/>
    <style type="text/css">
    li {
        display: inline;
        list-style-type: none;
    }
    </style>
    <script type="text/javascript" src="js/stream-controls.js"></script>
    <script type="text/javascript" src="js/script.js"></script>
    <script type="text/javascript">
    $(document).ready(function () {
        <?php
        $protocol = ($_SERVER['HTTPS'] == 'on')? 'https://' : 'http://';
        $receiver_path = $protocol.$_SERVER['HTTP_HOST'].'/app_receiver.html';
        ?>
        var hsp_params = {
            apiKey: 'bppghhj1h008gg8wwg808g80g3ia90a2l6i',
            useTheme: true
        };

        hsp_params.receiverPath = '<?php echo $receiver_path ?>';
        hsp_params.subtitle = '<?php echo ($_user['connected']? $_user['connected_user_name'] : '') ?>';
        hsp.init(hsp_params);

        hsp.bind('closepopup', function () {
        });
        hsp.bind('dropuser', function () {
        });
        hsp.bind('refresh', function () {
            stravaStream.refresh_stream();
        });
        hsp.bind('senttoapp', function () {
        });
        hsp.bind('sendcomposedmsgtoapp', function () {
        });
        hsp.bind('sendprofiletoapp', function () {
        });
        hsp.bind('sendassignmentupdates', function () {
        });

            // Strava app JS
            stravaStream.init($('#app-stream'), {
                connected: <?php echo (int) $_user['connected']; ?>,
                <?php if($_user['connected']): ?>
                connected_user_id: <?php echo $_user['connected_user_id']?>,
                connected_user_token: '<?php echo $_user['connected_user_token']?>',
                <?php endif; ?>
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
                template: $('#activity_template').html()
            });

            $('a.btn-connect').click(function (e) {
                window.open('connect.php?<?php print $_SERVER['QUERY_STRING']; ?>', 'Login | Strava', 'width=600,height=380,toolbar=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,modal=yes');
                e.preventDefault();
            });

            // Strava stream function handler

            // Search
            var search = function () {
                var start = $('#start_date').val();
                var end = $('#end_date').val();
                if (start.length || end.length) {
                    stravaStream.search(start, end);
                    $('.hs_topBar .hs_dropdown').hide();
                    $('.hs_topBar .hs_controls a.active').removeClass('active');
                    $(window).scrollTop(0);
                }
            };
            $('.hs_topBar a.search').click(function (e) {
                search();
            });
            $('#start_date, #end_date').keypress(function (e) {
                if (e.keyCode == 13) { // enter button
                    search();
                }
            });


            // Create Activity
            var create = function () {
                var name = $('#activity_name').val();
                var type = $('#activity_type').val();
                var date = $('#activity_date').val();
                var time = $('#activity_time').val();
                var duration = $('#activity_duration').val();
                stravaStream.create(name, type, date, time, duration, lat, lng);

            };
            $('#create_activity_form').find('a.create').click(function (e) {
                create();
            });
            $('#create_activity_form').find('#activity_name, #activity_type, #activity_date, #activity_time, #activity_duration, #activity_distance').keypress(function (e) {
                if (e.keyCode == 13) { // enter button
                    create();
                }
            });

            var lat = 0;
            var lng = 0;
            var getLatLng = function(position) {
                lat = position.coords.latitude;
                lng = position.coords.longitude;
            };
            var getLocation = function () {
                if(navigator.geolocation) {
                  navigator.geolocation.getCurrentPosition(getLatLng,function(err) {
                  },{timeout:60000});
              }
          };
          getLocation();

      })
</script>

<script id="activity_template" type="text/template">
<div class="hs_message" data-item-id="{{id}}" data-value="{{distance}}">
<div class="hs_controls">
<a href="#" class="hs_icon hs_reply" title="Share">Share</a>
<a href="#" class="hs_icon hs_expand" title="More">more...</a>
</div>
<a href="{{permalink}}" target="_blank" class="hs_networkName">{{name}}</a>
<a href="#" class="hs_postTime">{{date}}</a>

<div class="hs_messageContent">
<ul>
<li class="hs_tooltip" title="Distance">{{distance}}km&nbsp;&nbsp;</li>
<li class="hs_tooltip" title="Average Pace">{{avg_speed}}km/h&nbsp;&nbsp;</li>
<li class="hs_tooltip" title="Moving Time">{{moving_time}}&nbsp;&nbsp;</li>
<li class="hs_tooltip" title="Elevation Gain">{{elevation_gain}}m&nbsp;&nbsp;</li>
</ul>

</div>

</div>
</script>

<script type="text/css" media="screen">
</script>

</head>
<body>


    <!-- Stream -->
    <div class="hs_stream">


        <!-- Top Bar -->
        <div class="hs_topBar">

            <?php if ($_user['connected']): ?>

            <div class="hs_content">
                <!-- ICONS -->
                <div class="hs_controls">
                    <a href="#" dropdown="_uploadActivity" title="Upload Activity"><span
                        class="icon-19 write"></span>
                    </a>
                    <a href="#" dropdown="_search" title="Search"><span
                        class="icon-19 search"></span>
                    </a>

                    <a href="#" dropdown="_settings" title="Settings"><span
                        class="icon-19 settings"></span>
                    </a>
                </div>

                <!-- CUSTOM CONTENT -->

            </div>


            <!-- DROPDOWNS -->
            <div class="hs_dropdown">

                <!-- CREATE ACTIVITY -->
                <div class="_uploadActivity hs_btns-right">
                    <form id="create_activity_form">
                        <label class="hs_title">Name<br></label>
                        <input id="activity_name" name="activity[name]" size="30" type="text" style="width:165px" required>

                        <label class="hs_title">Type<br></label>
                        <select id="activity_type" name="activity[type]" class="valid" style="width:175px" required>
                            <option value="Run">Run</option>
                            <option value="Walk">Walk</option>
                            <option value="Hike">Hike</option>
                            <option value="Ride" selected="selected">Ride</option>
                            <option value="NordicSki">Nordic Ski</option>
                            <option value="AlpineSki">Alpine Ski</option>
                            <option value="BackcountrySki">Backcountry Ski</option>
                            <option value="IceSkate">Ice Skate</option>
                            <option value="InlineSkate">Inline Skate</option>
                            <option value="Kitesurf">Kitesurf Session</option>
                            <option value="RollerSki">Roller Ski</option>
                            <option value="Windsurf">Windsurf Session</option>
                            <option value="Workout">Workout</option>
                            <option value="Snowboard">Snowboard</option>
                            <option value="Snowshoe">Snowshoe</option>
                            <option value="Swim">Swim</option>
                        </select>

                        <label class="hs_title">Date<br></label>
                        <input type="date" id="activity_date" name="activity[date]" required>

                        <label class="hs_title">Start Time<br></label>
                        <input type="time" id="activity_time" name="activity[time]" required>

                        <label class="hs_title">Duration (minutes)<br></label>
                        <input type="number" id="activity_duration" name="activity[duration]" required min="0">

                        <br><br>

                        <div class="hs_btns-right">
                            <a class="hs_btn-cmt create">Create</a>
                        </div>
                    </form>
                </div>

                <!-- SEARCH -->
                <div class="_search hs_btns-right">
                    <label class="hs_title">Start Date<br></label>
                    <input type="date" id="start_date" name="start_date" style="width:165px">


                    <label class="hs_title">End Date<br></label>
                    <input type="date" id="end_date" name="end_date" style="width:165px">

                    <br><br>
                    <a class="hs_btn-cmt search">Search</a>
                </div>

                <!-- SETTINGS -->
                <div class="_settings hs_btns-right">
                    <strong>Connected user:</strong> <?php echo $_user['connected_user_name'] ?>
                    &nbsp;
                    <a href="<?php echo '?' . query_without_vars('connect') . '&disconnect'; ?>" class="hs_btn-cmt">Disconnect</a>
                </div>
            </div>
        <?php else: ?>
        <div class="hs_content">
            <?php if (!$_user['connected']): ?>
            <a href="<?php echo $_SERVER['REQUEST_URI'] . '&connect'; ?>" class="hs_btn-cmt btn-connect">Connect
                your Strava account</a>
            <?php endif ?>
        </div>
    <?php endif; ?>
</div>

<div class="hs_topBarSpace"></div>

<div class="hs_noMessage" id="app-stream-heading" style="display:none;"></div>

<div id="app-stream"></div>

</div>
<!-- .hs_stream -->


</body>
</html>
