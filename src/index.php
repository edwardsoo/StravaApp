<?php
/**
 * Created by JetBrains PhpStorm.
 * User: edwards
 * Date: 2013-04-30
 * Time: 9:48 AM
 * To change this template use File | Settings | File Templates.
 */

require_once('Authentication.php');

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
    unset($_SESSION[$user_stream]);
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
    <link rel='stylesheet' href='css/<?php echo $theme; ?>' type="text/css" media="screen"/>

    <script type="text/javascript">
        $(document).ready(function () {
            <?php
            $protocol = ($_SERVER['HTTPS'] == 'on')? 'https://' : 'http://';
            $receiver_path = $protocol.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']).'/app_receiver.html';
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
            });
            hsp.bind('senttoapp', function () {
            });
            hsp.bind('sendcomposedmsgtoapp', function () {
            });
            hsp.bind('sendprofiletoapp', function () {
            });
            hsp.bind('sendassignmentupdates', function () {
            });


        })
    </script>

    <script id="activity_template" type="text/template">
        <div class="hs_message" data-username="{{username}}">
            <div class="hs_controls">
                <a href="#" class="hs_icon hs_reply" title="Reply">Reply</a>
                <a href="#" class="hs_icon hs_directMessage" title="Direct Message">DM</a>
            </div>
            <a href="#" class="hs_networkName">{{username}}</a>
            <a href="{{permalink}}" target="_blank" class="hs_postTime">{{post_date}}</a>

            <div class="hs_messageContent">{{message_text}}</div>
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
                <a href="#" dropdown="_menuList" title="More"><span
                        class="icon-19 dropdown"></span>
                </a>
            </div>

            <!-- CUSTOM CONTENT -->

        </div>

        <!-- DROPDOWNS -->
        <div class="hs_dropdown">


            <div class="hs_message">

                <span class="hs_networkName">Sample Form Elements</span>

                <p>
                    Beow are some sample form elements for re-use anywhere in the stream.
                </p>

                <label class="hs_title">Input:<br><input type="text"></label>
                <label class="hs_title">Textarea:<br><textarea style="width: 158px;"></textarea></label>
                <label class="hs_title">Textarea with info below:</label><textarea style="width: 158px;"></textarea>

                <p class="hs_subDesc">Description text or more information</p>
                <label class="hs_title">Textarea with info above:</label>

                <p class="hs_supDesc">Description text or more information</p><textarea
                    style="width: 158px;"></textarea>

                <div class="hs_btns">
                    <a class="hs_btn-del" href="#">Cancel</a>&nbsp;<a class="hs_btn-cmt" href="#">Submit</a>
                </div>

            </div>


            <!-- WRITE MESSAGE -->
            <div class="_writeMessage hs_btns-right">
                <label class="hs_title">Type:<br>
                    <select id="activity_type" name="activity[type]" class="valid">
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
                </label>
                <label class="hs_title">Name:<br>
                    <input id="activity_name" size="30" type="text">
                </label>

                <div class="hs_btns-right">
                    <a class="hs_btn-cmt" href="#">Send</a>
                </div>
            </div>

            <!-- SEARCH -->
            <div class="_search hs_btns-right">
                <input type="text">&nbsp;<a class="hs_btn-cmt">Search</a>
            </div>

            <!-- SETTINGS -->
            <div class="_settings hs_btns-right">
                <?php if ($_user['connected']): ?>
                    <strong>Connected account:</strong> <?php echo $_user['connected_user_name'] ?>
                    &nbsp;
                    <a href="<?php echo '?' . query_without_vars('connect') . '&disconnect'; ?>" class="hs_btn-cmt">Disconnect</a>
                <?php else: ?>
                    <a href="<?php echo $_SERVER['REQUEST_URI'] . '&connect'; ?>" class="hs_btn-cmt btn-connect">Connect
                        your Strava account</a>
                <?php endif ?>
                <a href="#">Settings Link</a>
                <label class="hs_title"><input type="checkbox"> Setting 1</label>
                <a class="hs_btn-cmt" href="#">Save</a>
            </div>

            <!-- MENU LIST -->
            <div class="_menuList hs_btns-right">
                <a href="#">dropdown link 1</a>
                <hr>
                <a href="#">dropdown link 2</a>
                <hr>
                <a href="#">dropdown link 3</a>
            </div>

        </div>
    </div>
    <div class="hs_topBarSpace"></div>
    <!-- Spacer underneath "hs_topBar" to prevent clipping of content -->

    <div class="hs_noMessage">
        Space for a user message or other text
    </div>


    <!-- ==================== -->
    <!-- = MESSAGE TEMPLATE = -->
    <!-- ==================== -->
    <!--
    NOTE: This template contains all possible elements at once.
          Re-use only what you need and
    ß
          =============================
          = read the in-line comments =
          =============================

    API documentation: https://sites.google.com/site/hootsuiteappdevelopers/jsapi

     -->
    <div class="hs_message">


        <!-- MESSAGE CONTROLS -->

        <div class="hs_controls">
            <a href="#" class="hs_icon hs_reply" title="Share">Share</a>
            <a href="#" class="hs_icon hs_directMessage" title="Direct Message">DM</a>
        </div>


        <!-- MESSAGE -->
        <!--
        Clicking on a username or avatar should open the user's bio via hsp.customUserInfo()
        or hsp.showUser(twitterHandle) for Twitter users
        -->
        <a href="#" class="hs_networkAvatarLink"></a><img class="hs_networkAvatar" src="[AVATAR URL]"
                                                          alt="[Username or Heading]">

        <a href="#" class="hs_networkName">Username or Heading</a>

        <!-- This should link out directly to the source message or story -->
        <a href="[PERMALINK / SOURCE URL]" class="hs_postTime" target="_blank">Jan 01, 12:34am via [Platform or
            Username]</a>

        <div class="hs_messageContent">
            Message Content...
            <a href="#" class="demo_user_info">This link</a> opens a user info popup (when called from within the
            dashboard).
        </div>


        <!-- ATTACHMENT -->

        <div class="hs_postAttachment">

            <!-- This should link out directly to the source page -->
            <a class="hs_attachedLink " href="http://ow.ly/6Ou4N" target="_blank">
                <!-- Thumb: max. 130x200px (gets scaled down automatically) -->
                <img alt="Owly Graduate" src="owly-graduate-90x90.jpg">
            </a>

            <div class="hs_title">
                <!-- This should link out directly to the source page -->
                <a href="http://ow.ly/6Ou4N" target="_blank">HootSuite University, Social Media Certification</a>
            </div>

            <div class="hs_caption">ow.ly</div>
            <!-- The link's domain name -->
            <div class="hs_description">
                The HootSuite University program is designed for professionals seeking to increase skills in HootSuite
                and
                other social media tools and tactics.
            </div>

        </div>

    </div>

</div>
<!-- .hs_stream -->


<!-- ================== -->
<!-- = LOAD MORE LINK = -->
<!-- ================== -->
<!--
This should be triggered automatically when the scroll position nears the
the bottom of the stream.
 -->
<div id=""><!-- optional wrapper div -->
    <a href="#" class="hs_messageMore">Show More</a>
</div>


<iframe name="hspframe" id="hspframe" style="width: 1px; height: 1px; display: none; position: absolute;"
        src="http://hootsuite.com/dc_receiver.html#?action=init&amp;p1=&amp;p2=481755055398&amp;key=app-exchange-demo&amp;pid="></iframe>
</body>
</html>