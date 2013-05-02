/**
 * Created with JetBrains PhpStorm.
 * User: edwards
 * Date: 2013-05-01
 * Time: 2:40 PM
 * To change this template use File | Settings | File Templates.
 */

var stravaStream = {
    options: {

        limit: 5, // # of activities per "page"
        storiesLoadingHTML: '<div class="ht_noMessage">{{throbber}}</div>',
        showMoreHTML: '<div class="hs_message-more" id="show_more" data-loading_more="false"><a href="#" class="hs_messageMore">Show More</a></div>',
        template: '<div class="hs_message" data-username="{{username}}"'
    },

    refresh_stream: function(force) {
        if (!force) {
            if ()
        }
    }
}