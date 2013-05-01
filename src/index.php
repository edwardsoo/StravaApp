<?php
/**
 * Created by JetBrains PhpStorm.
 * User: edwards
 * Date: 2013-04-30
 * Time: 9:48 AM
 * To change this template use File | Settings | File Templates.
 */

require_once('StravaApiLib.php');
require_once('application/config.inc.php');

/*
 *  =============================================
 *   Constants
 *  =============================================
 */
$HS_DASHBOARD = 'http://hootsuite.com/dashboard';
$DEFAULT_THEME = 'blue_steel';

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
        if(!in_array($key, $var)) {
            $return[] = "$key=$val";
        }
    }
    return implode('&', $return);
}

/*
 *  =============================================
 *   URL Parameters passed to the app iframe
 *  =============================================
 *   lang=en
 *   theme=blue_steel // blue_steel, magnum, classic
 *   timezone=7200
 *   uid=1234567      // HootSuite user ID
 *   i=1234567        // user identifier (optionally entered by user upon App installation)
 *   ts=1318362023    // timestamp
 *   token=123abc...  // security token (sha1 hash)
*/


session_start();

$_user = array(
    'authenticated' => false, // Hootsuite authentication
    'connected' => false, // Strava login
);

/*
 * HootSuite single sign-on
 * Verify user is logged into Hootsuite by hashing with SHA-1 (user id, timestamp, secret key)
 * and comparing to a token
 */

$hash = sha1($_REQUEST['i'] . $_REQUEST['ts'] . $_config['auth_secret']);
if ($hash == $_REQUEST['token']) {
    $_user['authenticated'] = true;
} // Redirect if unauthenticated
else {
    header("Location: $HS_DASHBOARD");
    exit();
}

/*
 * ==============================================
 *  Strava login
 * ==============================================
 */
// Stream specific id
$user_stream = $_REQUEST['uid'] . '-' . $_REQUEST['pid'];
// TODO: store user_stream data on a db
$account_data = $_SESSION[$user_stream];

if (is_array($account_data)) {
    // append stream specific data to _user array
    array_merge($_user, $account_data);
    // user is connected
    $_user['connected'] = true;
}

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
            <label class="hs_title">Textarea with info below:</label><textarea style="width: 158px;"></textarea><p class="hs_subDesc">Description text or more information</p>
            <label class="hs_title">Textarea with info above:</label><p class="hs_supDesc">Description text or more information</p><textarea style="width: 158px;"></textarea>
            <div class="hs_btns">
                <a class="hs_btn-del" href="#">Cancel</a>&nbsp;<a class="hs_btn-cmt" href="#">Submit</a>
            </div>

        </div>



        <!-- WRITE MESSAGE -->
        <div class="_writeMessage hs_btns-right">
            <label class="hs_title">Type:<br>
                <select id="activity_type" name="activity[type]" class="valid"><option value="Run">Run</option>
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
                    <option value="Swim">Swim</option></select>
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
                <a href="<?php echo '?'.query_without_vars('connect').'&disconnect'; ?>" class="hs_btn-cmt">Disconnect</a>
            <?php else: ?>
                <a href="<?php echo $_SERVER['REQUEST_URI'].'&connect'; ?>" class="hs_btn-cmt btn-connect">Connect your Strava account</a>
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
            The HootSuite University program is designed for professionals seeking to increase skills in HootSuite and
            other social media tools and tactics.
        </div>

    </div>

</div>


<!-- ======================== -->
<!-- = Some Sample Messages = -->
<!-- ======================== -->

<div class="hs_message">

    <div class="hs_controls">
        <a href="#" class="hs_icon hs_reply" title="Share">Share</a>
        <a href="#" class="hs_icon hs_favorite" title="Favorite">Favorite</a>

        <a href="#" class="hs_icon hs_expand">more...</a>

        <div class="hs_moreOptionsMenu">
            <a href="#"><span class="hs_icon hs_replyAll"></span>Reply All</a>
            <a href="#"><span class="hs_icon hs_retweet"></span>RT</a>
            <a href="#"><span class="hs_icon hs_directMessage"></span>DM</a>
            <a href="#"><span class="hs_icon hs_email"></span>Email</a>
        </div>
    </div>

    <a href="#" class="hs_networkAvatarLink" title="Username" is-draggable="1"></a><img class="hs_networkAvatar"
                                                                                        src="https://s3.amazonaws.com/twitter_production/profile_images/576275082/hootsuite-help-icon-512px-original_normal.png">
    <a href="#" class="hs_networkName" title="Username">Username</a>
    <a href="http://twitter.com/HootSuite_Help/status/636825056055297" class="hs_postTime" target="_blank">Sep 29,
        11:53am via HootSuite</a>

    <div class="hs_messageContent">
        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum nec diam quam, et viverra purus. Fusce
        venenatis tortor sed lacus varius ut aliquet leo vulputate. Integer dui nunc, pellentesque et consequat eget,
        facilisis non lectus. Curabitur a ligula eget mi vulputate blandit. Ut sed mauris massa, vitae porta turpis.
    </div>

    <div class="hs_messageComments">
        <span class="hs_arrow">☗</span>

        <!-- Compact comments and likes display: add class hs_inlineDetails -->

        <div class="hs_comment hs_details hs_inlineDetails">
            <a href="#">0 likes</a>
            &nbsp;&nbsp;
            <a href="#">0 comments</a>
        </div>
    </div>

</div>

<div class="hs_message">
    <div class="hs_controls">
        <a href="#" class="hs_icon hs_reply" title="Reply">Reply</a>
        <a href="#" class="hs_icon hs_favorite" title="Favorite">Favorite</a>

        <a href="#" class="hs_icon hs_expand">more...</a>

        <div class="hs_moreOptionsMenu">
            <a href="#"><span class="hs_icon hs_replyAll"></span>Reply All</a>
            <a href="#"><span class="hs_icon hs_retweet"></span>RT</a>
            <a href="#"><span class="hs_icon hs_directMessage"></span>DM</a>
            <a href="#"><span class="hs_icon hs_email"></span>Email</a>
        </div>
    </div>
    <a title="Username" href="#" class="hs_networkAvatarLink"></a><img class="hs_networkAvatar"
                                                                       src="https://s3.amazonaws.com/twitter_production/profile_images/576281063/hootsuite-iphone-icon-512px-original_normal.png">
    <a title="Username" class="hs_networkName" href="#">Username</a>
    <a target="_blank" href="http://twitter.com/HootSuiteiPhone/status/1765521028554752" class="hs_postTime">Jan 01,
        12:34am via HootSuite</a>

    <div class="hs_messageContent">For more information on HootSuite, follow @<a title="HootSuite"
                                                                                 href="#">HootSuite</a> and @<a
            title="HootSuite_Help" href="#">HootSuite_Help</a></div>
</div>

<div class="hs_message">
    <div class="hs_controls">
        <a href="#" class="hs_icon hs_reply" title="Reply">Reply</a>
        <a href="#" class="hs_icon hs_favorite" title="Favorite">Favorite</a>

        <a href="#" class="hs_icon hs_expand">more...</a>

        <div class="hs_moreOptionsMenu">
            <a href="#"><span class="hs_icon hs_replyAll"></span>Reply All</a>
            <a href="#"><span class="hs_icon hs_retweet"></span>RT</a>
            <a href="#"><span class="hs_icon hs_directMessage"></span>DM</a>
            <a href="#"><span class="hs_icon hs_email"></span>Email</a>
        </div>
    </div>
    <a title="Username" href="#" class="hs_networkAvatarLink"></a><img class="hs_networkAvatar"
                                                                       src="https://s3.amazonaws.com/twitter_production/profile_images/541333937/hootsuite-icon_normal.png">
    <a title="Username" class="hs_networkName" href="#">Username</a>
    <a target="_blank" href="http://twitter.com/HootSuite/status/661876870483969" class="hs_postTime">Jan 01, 12:34am
        via HootSuite</a>

    <div class="hs_messageContent">This is sample text for the message body <a href="#" class="hs_hash"
                                                                               title="HashTagExample">#HashTagExample</a>
    </div>
</div>

<div class="hs_message">
    <div class="hs_controls">
        <a href="#" class="hs_icon hs_reply" title="Reply">Reply</a>
        <a href="#" class="hs_icon hs_favorite" title="Favorite">Favorite</a>

        <a href="#" class="hs_icon hs_expand">more...</a>

        <div class="hs_moreOptionsMenu">
            <a href="#"><span class="hs_icon hs_replyAll"></span>Reply All</a>
            <a href="#"><span class="hs_icon hs_retweet"></span>RT</a>
            <a href="#"><span class="hs_icon hs_directMessage"></span>DM</a>
            <a href="#"><span class="hs_icon hs_email"></span>Email</a>
        </div>
    </div>
    <a title="Username" href="#" class="hs_networkAvatarLink"></a><img class="hs_networkAvatar"
                                                                       src="https://s3.amazonaws.com/twitter_production/profile_images/1168118802/512px-icon-bb_normal.png">
    <a title="Username" class="hs_networkName" href="#">Username</a>
    <a target="_blank" href="http://twitter.com/iamgavitron/status/4311897587322880" class="hs_postTime">Jan 01, 12:34am
        via HootSuite</a>

    <div class="hs_messageContent">This is sample text for the message body</div>

</div>

<div class="hs_message">
    <div class="hs_controls">
        <a href="#" class="hs_icon hs_reply" title="Reply">Reply</a>
        <a href="#" class="hs_icon hs_favorite" title="Favorite">Favorite</a>

        <a href="#" class="hs_icon hs_expand">more...</a>

        <div class="hs_moreOptionsMenu">
            <a href="#"><span class="hs_icon hs_replyAll"></span>Reply All</a>
            <a href="#"><span class="hs_icon hs_retweet"></span>RT</a>
            <a href="#"><span class="hs_icon hs_directMessage"></span>DM</a>
            <a href="#"><span class="hs_icon hs_email"></span>Email</a>
        </div>
    </div>
    <a title="Username" href="#" class="hs_networkAvatarLink"></a><img class="hs_networkAvatar"
                                                                       src="https://s3.amazonaws.com/twitter_production/profile_images/1158522018/512px-icon-hootsuite-hsu_normal.png">
    <a title="Username" class="hs_networkName" href="#">Username</a>
    <a target="_blank" href="http://twitter.com/HootSuite/status/661876870483969" class="hs_postTime">Jan 01, 12:34am
        via HootSuite</a>

    <div class="hs_messageContent">This is sample text for the message body</div>
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