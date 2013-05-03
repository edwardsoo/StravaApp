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

            this.elem.
            delegate('#show_more a.hs_messageMore', 'click',function (event) {
                    // "show more" link
                    // this is a fall-back if the automatic scroll-based load doesn't trigger

                    if ($('#show_more').data('loading_more') === false) {
                        $('#show_more').data('loading_more', true);
                        $('#show_more').addClass('hs_noMessage');
                        $('#show_more').html(self.options.throbber);
                        self.show_default_stream(self.elem.children('.hs_message').length); // = offset
                    }
                    event.preventDefault();
                }).delegate('.hs_message .hs_controls a.hs_reply', 'click',function (event) {
                    var activity_id = $(this).closest('.hs_message').data('item-id');
                    var distance = $(this).closest('.hs_message').data('value');
                    hsp.composeMessage('went for a ' + distance + ' kilometer ride. http://app.strava.com/activities/' + activity_id + ' #strava');
                    event.preventDefault();
                }).delegate('.hs_message .hs_controls a.hs_expand', 'click', function (event) {
                    var activity_id = $(this).closest('.hs_message').data('item-id');
                    self.getMapDetails(activity_id);
                    event.preventDefault();
                });

            // Cancel/Clear links
            $('a.refresh_stream').live('click', function (event) {
                self.refresh_stream(true);
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


            if (!this.options.connected) {
                self.showError("Please connect to your Strava account.");
                return;
            }

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
                        $('#app-stream-heading').html('No rides found');
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
                        .replace(/\{\{distance\}\}/g, (ride.distance / 1000).toFixed(2))
                        .replace(/\{\{avg_speed\}\}/g, ride.average_speed.toFixed(2))
                        .replace(/\{\{moving_time\}\}/g, Math.floor(ride.moving_time / 3600) + 'hr ' + Math.floor((ride.moving_time % 3600) / 60) + 'm')
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

search: function (start, end, offset) {
    var self = this;

    this.last_api_call = 'search';

    offset = offset || 0;

    if (!this.options.connected) {
        self.showError("Please connect to your Strava account to see your rides.");
        return;
    }

    var search_str = '';
    if (start.length && end.length) {
        search_str = 'between ' + start + ' and ' + end;
    } else if (start.length) {
        search_str = 'after ' + start;
    } else if (end.length) {
        search_str = 'before ' + end;
    }


    var params = {
        action: 'get_rides',
        limit: this.options.limit,
        offset: offset,
        hs_params: this.options.hs_params,
        start_date: start,
        end_date: end
    };

    if (offset == 0) {
        $('#app-stream-heading').html('Searching for rides ' + search_str +
            '... (<a href="#" class="refresh_stream">Cancel</a>)'
            )
        ;
        $('#app-stream-heading').show();
        self.startLoading();
    }

    self.clearAjax();

    self.xhr = $.post('ajax.php', params, function (data) {
        var rides = data.rides;
        var template = self.options.template;
        var html;

        if (data.error != undefined && data.error.length) {
            self.showError(data.error);
            return;
        }

        if (offset == 0) {
            $('#app-stream-heading').html('Search results for rides ' + search_str + '.(<a href="#" class="refresh_stream">Clear</a>)');
            self.stopLoading();
            if (!rides.length) {
                $('#app-stream-heading').html('No rides found ' + search_str + '.(<a href="#" class="refresh_stream">Clear</a>)');
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
                .replace(/\{\{distance\}\}/g, (ride.distance / 1000).toFixed(2))
                .replace(/\{\{avg_speed\}\}/g, ride.average_speed.toFixed(2))
                .replace(/\{\{moving_time\}\}/g, Math.floor(ride.moving_time / 3600) + 'hr ' + Math.floor((ride.moving_time % 3600) / 60) + 'm')
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

getMapDetails: function (ride_id) {
    var self = this;

    this.last_api_call = 'getMapDetails';

    if (!this.options.connected) {
        self.showError("Please connect to your Strava account to see your rides.");
        return;
    }

    var params = {
        action: 'get_ride_route',
        hs_params: this.options.hs_params,
        ride_id: ride_id
    };

    self.xhr = $.post('ajax.php', params, function (data) {
        var ride = data;
        var hs_params = self.options.hs_params;

        if (data.error != undefined && data.error.length) {
            hsp.showStatusMessage(data.error, (data.errno == 2)?'warning':'error');
            return;
        } 
        var qdata = new Array();
        for (prop in hs_params) {
            qdata.push(prop + '=' + hs_params[prop]);
        }
        qdata.push('ride_id='+ride_id);
        hsp.showCustomPopup('http://' + location.host + '/map.php?' + qdata.join('&'), ride.name, 600, 380)
        
    });
},

create: function (name, type, date, time, duration, lat, lng) {
    var self = this;

    this.last_api_call = 'create';

    if (!this.options.connected) {
        self.showError("Please connect to your Strava account to see your rides.");
        return;
    }

    if (!name || !type || !date || !time || !duration) {
        hsp.showStatusMessage('Please fill in all fields.', 'error');
        return;
    }
    var dparts = date.split("-");
    var tparts = time.split(":");
    var d = new Date()
    var n = d.getTimezoneOffset();
    var start_time = new Date(dparts[0], dparts[1]-1, dparts[2], tparts[0], tparts[1]);
    var start_ts = start_time.getTime()/1000;
    var end_ts = start_ts + duration*60;


    var params = {
        action: 'create_ride',
        hs_params: this.options.hs_params,
        activity_name: name,
        activity_type: type,
        activity_fields: ["time", "latitude", "longitude"],
        activity_start: start_ts,
        activity_end: end_ts,
        activity_lat: lat,
        activity_lng: lng
    };

    self.xhr = $.post('ajax.php', params, function (data) {

        if (data.error != undefined && data.error.length) {
            hsp.showStatusMessage(data.error, 'error');
            return;
        }
        hsp.showStatusMessage('Activity created.', 'success');

        $('.hs_topBar .hs_dropdown').hide();
        $('.hs_topBar .hs_controls a.active').removeClass('active');
        $('#create_activity_form').find('input, select').each(function(){
            $(this).val('');
        });
        
    });
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
}
;


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

