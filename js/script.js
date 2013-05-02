/**
 * Created with JetBrains PhpStorm.
 * User: edwards
 * Date: 2013-05-01
 * Time: 2:40 PM
 * To change this template use File | Settings | File Templates.
 */

var stravaStream = {
    xhr: null,
    las_api_call: '',
    elem: null,

    options: {
        limit: 10, // # of activities per "page"
        storiesLoadingHTML: '<div class="hs_noMessage">{{throbber}}</div>',
        showMoreHTML: '<div class="hs_message-more" id="show_more" data-loading_more="false"><a href="#" class="hs_messageMore">Show More</a></div>',

        // jquery-dateFormat plugin
        //
        // yyyy = year
        // MM = month
        // MMM = month abbreviation (Jan, Feb … Dec)
        // MMMM = long month (January, February … December)
        // ddd = day of the week in words (Monday, Tuesday … Sunday)
        // dd = day
        // hh = hour in am/pm (1-12)
        // HH = hour in day (0-23)
        // mm = minute
        // ss = second
        // a = am/pm marker
        // SSS = milliseconds
        //
        dateFormat: 'ddd, MMMM dd, yyyy', // Wednesday, May 1, 2013
        dateFormatWithTime: 'hh:mma on ddd, MM/dd/yyyy' // 12:00am on Wednesday, 05/01/2013
    },

    init: function (elem, options) {
        var self = this;
        if (elem === undefined || !elem.length) {
            return false;
        }
        this.elem = elem;
        this.options = $.extend({}, this.options, options);

        // themed throbber
        this.options.throbber = '<img src="http://s0.static.hootsuite.com/2-7-15/images/themes/' + this.options.hs_params.theme + '/loader.gif" alt="Loading..." class="throbber" style="vertical-align:middle;" />'; // margin-bottom:3px;
        this.options.storiesLoadingHTML = this.options.storiesLoadingHTML.replace(/\{\{throbber\}\}/g, this.options.throbber);

        this.elem.delegate('#show_more a.hs_messageMore', 'click', function (event) {
            // "show more" link
            // this is a fall-back if the automatic scroll-based load doesn't trigger

            if ($('#show_more').data('loading_more') === false) {
                $('#show_more').data('loading_more', true);
                $('#show_more').addClass('hs_noMessage');
                $('#show_more').html(self.options.throbber);
                self.show_default_stream(self.elem.children('.hs_message').length); // = offset
            }
            event.preventDefault();
        });

        // load messages
        this.show_default_stream();
    },

    refresh_stream: function (force) {
        if (!force) {
            // TODO: check for incomplete form
            if (false) {

            }
        }
        $('#app-stream-heading').hide();

        this.show_default_stream();

    },

    show_default_stream: function (offset) {
        offset = offset || 0;

        var self = this;
        this.las_api_call = 'default_stream';

        var params = {
            action: 'get_rides',
            limit: this.options.limit,
            offset: offset,
            hs_params: this.options.hs_params
        };

        if (offset == 0) {
            self.startLoading();
        }

        self.clearAjax();
        self.xhr = $.post('ajax.php', params, function (data) {
            console.debug(data);
            var rides = data.rides;
            var template = self.options.template;
            var html;

            if (data.error != undefined && data.error.length) {
                self.showError(data.error);
                return;
            }

            if (offset == 0) {
                self.stopLoading();
                if (!rides.length) {
                    $('#app-stream-heading').html('No tweets found');
                    return;
                }
            }
            html = '';
            if (rides.length) {
                html += $.map(rides,function (ride) {
                    return template
                        .replace(/\{\{name\}\}/g, ride.name)
                        .replace(/\{\{id\}\}/g, ride.id)
                        .replace(/\{\{permalink\}\}/g, 'http://app.strava.com/activities/' + ride.id)
                        .replace(/\{\{date\}\}/g, $.format.date(new Date(ride.ts * 1000), self.options.dateFormat))
                        .replace(/\{\{distance\}\}/g, ride.distance/1000)
                        .replace(/\{\{avg_speed\}\}/g, ride.average_speed.toFixed(2))
                        .replace(/\{\{moving_time\}\}/g, ride.moving_time / 3600 + 'hr ' + ride.moving_time % 3600 + 'm')
                        .replace(/\{\{elevation_gain\}\}/g, ride.elevation_gain)
                }).join('');
            }
            if (rides.length < self.options.limit) {
                html += '<div class="hs_noMessage"> No more rides.</div>';
            } else {
                html += self.options.showMoreHTML;
            }

            if (offset == 0) {
                self.elem.html(html);
            } else {
                // loaded via "show more" => replace "show more" bar with html
                $('#show_more').replaceWith(html);
            }

            if (rides.length == self.options.limit) {
                $(window).bind('scroll', function () {
                    if ($(window).scrollTop() >= $(document).height() - $(window).height() - 20) {
                        if ($('#show_more').data('loading_more') === false) {
                            $('#show_more').data('loading_more', true);
                            $('#show_more').addClass('hs_noMessage');
                            $('#show_more').html(self.options.throbber);
                            self.show_default_stream(offset + self.options.limit);
                        }
                    }
                });
            }
        }, "json");

    },

    clearAjax: function () {
        try {
            this.xhr.abort();
        } catch (e) {

        }
        $(window).unbind("scroll");
    },

    startLoading: function () {
        this.elem.html(this.options.storiesLoadingHTML);
    },

    stopLoading: function () {
        this.elem.html('');
    },

    showError: function (message) {
        this.elem.html('<div class="hs_noMessage">' + message + '</div>');
    }
};


// jquery-dateFormat plugin
//
// https://github.com/phstc/jquery-dateFormat

(function ($) {

    var daysInWeek = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
    var shortMonthsInYear = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    var longMonthsInYear = ["January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"];
    var shortMonthsToNumber = [];
    shortMonthsToNumber["Jan"] = "01";
    shortMonthsToNumber["Feb"] = "02";
    shortMonthsToNumber["Mar"] = "03";
    shortMonthsToNumber["Apr"] = "04";
    shortMonthsToNumber["May"] = "05";
    shortMonthsToNumber["Jun"] = "06";
    shortMonthsToNumber["Jul"] = "07";
    shortMonthsToNumber["Aug"] = "08";
    shortMonthsToNumber["Sep"] = "09";
    shortMonthsToNumber["Oct"] = "10";
    shortMonthsToNumber["Nov"] = "11";
    shortMonthsToNumber["Dec"] = "12";

    $.format = (function () {
        function strDay(value) {
            return daysInWeek[parseInt(value, 10)] || value;
        }

        function strMonth(value) {
            var monthArrayIndex = parseInt(value, 10) - 1;
            return shortMonthsInYear[monthArrayIndex] || value;
        }

        function strLongMonth(value) {
            var monthArrayIndex = parseInt(value, 10) - 1;
            return longMonthsInYear[monthArrayIndex] || value;
        }

        var parseMonth = function (value) {
            return shortMonthsToNumber[value] || value;
        };

        var parseTime = function (value) {
            var retValue = value;
            var millis = "";
            if (retValue.indexOf(".") !== -1) {
                var delimited = retValue.split('.');
                retValue = delimited[0];
                millis = delimited[1];
            }

            var values3 = retValue.split(":");

            if (values3.length === 3) {
                hour = values3[0];
                minute = values3[1];
                second = values3[2];

                return {
                    time: retValue,
                    hour: hour,
                    minute: minute,
                    second: second,
                    millis: millis
                };
            } else {
                return {
                    time: "",
                    hour: "",
                    minute: "",
                    second: "",
                    millis: ""
                };
            }
        };

        return {
            date: function (value, format) {
                /*
                 value = new java.util.Date()
                 2009-12-18 10:54:50.546
                 */
                try {
                    var date = null;
                    var year = null;
                    var month = null;
                    var dayOfMonth = null;
                    var dayOfWeek = null;
                    var time = null;
                    if (typeof value.getFullYear === "function") {
                        year = value.getFullYear();
                        month = value.getMonth() + 1;
                        dayOfMonth = value.getDate();
                        dayOfWeek = value.getDay();
                        time = parseTime(value.toTimeString());
                    } else if (value.search(/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.?\d{0,3}[-+]?\d{2}:\d{2}/) != -1) { /* 2009-04-19T16:11:05+02:00 */
                        var values = value.split(/[T\+-]/);
                        year = values[0];
                        month = values[1];
                        dayOfMonth = values[2];
                        time = parseTime(values[3].split(".")[0]);
                        date = new Date(year, month - 1, dayOfMonth);
                        dayOfWeek = date.getDay();
                    } else {
                        var values = value.split(" ");
                        switch (values.length) {
                            case 6:
                                /* Wed Jan 13 10:43:41 CET 2010 */
                                year = values[5];
                                month = parseMonth(values[1]);
                                dayOfMonth = values[2];
                                time = parseTime(values[3]);
                                date = new Date(year, month - 1, dayOfMonth);
                                dayOfWeek = date.getDay();
                                break;
                            case 2:
                                /* 2009-12-18 10:54:50.546 */
                                var values2 = values[0].split("-");
                                year = values2[0];
                                month = values2[1];
                                dayOfMonth = values2[2];
                                time = parseTime(values[1]);
                                date = new Date(year, month - 1, dayOfMonth);
                                dayOfWeek = date.getDay();
                                break;
                            case 7:
                            /* Tue Mar 01 2011 12:01:42 GMT-0800 (PST) */
                            case 9:
                            /*added by Larry, for Fri Apr 08 2011 00:00:00 GMT+0800 (China Standard Time) */
                            case 10:
                                /* added by Larry, for Fri Apr 08 2011 00:00:00 GMT+0200 (W. Europe Daylight Time) */
                                year = values[3];
                                month = parseMonth(values[1]);
                                dayOfMonth = values[2];
                                time = parseTime(values[4]);
                                date = new Date(year, month - 1, dayOfMonth);
                                dayOfWeek = date.getDay();
                                break;
                            default:
                                return value;
                        }
                    }

                    var pattern = "";
                    var retValue = "";
                    /*
                     Issue 1 - variable scope issue in format.date
                     Thanks jakemonO
                     */
                    for (var i = 0; i < format.length; i++) {
                        var currentPattern = format.charAt(i);
                        pattern += currentPattern;
                        switch (pattern) {
                            case "ddd":
                                retValue += strDay(dayOfWeek);
                                pattern = "";
                                break;
                            case "dd":
                                if (format.charAt(i + 1) == "d") {
                                    break;
                                }
                                if (String(dayOfMonth).length === 1) {
                                    dayOfMonth = '0' + dayOfMonth;
                                }
                                retValue += dayOfMonth;
                                pattern = "";
                                break;
                            case "MMMM":
                                retValue += strLongMonth(month);
                                pattern = "";
                                break;
                            case "MMM":
                                if (format.charAt(i + 1) === "M") {
                                    break;
                                }
                                retValue += strMonth(month);
                                pattern = "";
                                break;
                            case "MM":
                                if (format.charAt(i + 1) == "M") {
                                    break;
                                }
                                if (String(month).length === 1) {
                                    month = '0' + month;
                                }
                                retValue += month;
                                pattern = "";
                                break;
                            case "yyyy":
                                retValue += year;
                                pattern = "";
                                break;
                            case "HH":
                                retValue += time.hour;
                                pattern = "";
                                break;
                            case "hh":
                                /* time.hour is "00" as string == is used instead of === */
                                retValue += (time.hour == 0 ? 12 : time.hour < 13 ? time.hour : time.hour - 12);
                                pattern = "";
                                break;
                            case "mm":
                                retValue += time.minute;
                                pattern = "";
                                break;
                            case "ss":
                                /* ensure only seconds are added to the return string */
                                retValue += time.second.substring(0, 2);
                                pattern = "";
                                break;
                            case "SSS":
                                retValue += time.millis.substring(0, 3);
                                pattern = "";
                                break;
                            case "a":
                                retValue += time.hour >= 12 ? "PM" : "AM";
                                pattern = "";
                                break;
                            case " ":
                                retValue += currentPattern;
                                pattern = "";
                                break;
                            case "/":
                                retValue += currentPattern;
                                pattern = "";
                                break;
                            case ":":
                                retValue += currentPattern;
                                pattern = "";
                                break;
                            default:
                                if (pattern.length === 2 && pattern.indexOf("y") !== 0 && pattern != "SS") {
                                    retValue += pattern.substring(0, 1);
                                    pattern = pattern.substring(1, 2);
                                } else if ((pattern.length === 3 && pattern.indexOf("yyy") === -1)) {
                                    pattern = "";
                                }
                        }
                    }
                    return retValue;
                } catch (e) {
                    console.log(e);
                    return value;
                }
            }
        };
    }());
}(jQuery));

