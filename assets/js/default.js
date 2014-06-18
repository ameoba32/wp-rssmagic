function isValidUrl( value ) {
    // contributed by Scott Gonzalez: http://projects.scottsplayground.com/iri/
    return /^(https?|s?ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(value);
}


jQuery(document).ajaxError(function() {
    jQuery('#rss-message p').text('Opps, sorry... Internal Server Error');
    jQuery('#rss-message').addClass('error');
    jQuery('#rss-message').show();
    jQuery('#rss-message')[0].scrollIntoView();
});

jQuery(document).ready(function() {
    /**
     * Ajax form support
     */
    jQuery('form.ajax').submit(function() {
        var postData = jQuery(this).serialize() + '&action=rssmagic&ajaxaction=' + jQuery(this).data('action');
        jQuery.post(ajaxurl, postData , function(data) {
            if (data.code == 0) {
                jQuery('#rss-message p').text(data.message);
                jQuery('#rss-message').removeClass('error');
                jQuery('#rss-message').show();
                if (data.redirect != '') {
                    location.href = data.redirect;
                }
            }
        }).error(
            function() {
                jQuery('#rss-message p').text('Opps, sorry... Internal Server Error');
                jQuery('#rss-message').addClass('error');
                jQuery('#rss-message').show();
                jQuery('#rss-message')[0].scrollIntoView()
            }
        );
        return false;
    });

    /**
     * Ajax link support
     */
    jQuery('a.ajax').click(function() {
        if (jQuery(this).hasClass('confirm') && !confirm('Are you sure?')) {
            return false;
        }
        jQuery.post(ajaxurl, 'action=rssmagic&' + jQuery(this).data('post'), function(data) {
            if (data.code == 0) {
                jQuery('#rss-message p').text(data.message);
                jQuery('#rss-message').removeClass('error');
                jQuery('#rss-message').show();
                if (data.redirect != '') {
                    location.href = data.redirect;
                }
            }
        }).error(
            function() {
                jQuery('#rss-message p').text('Opps, sorry... Internal Server Error');
                jQuery('#rss-message').addClass('error');
                jQuery('#rss-message').show();
                jQuery('#rss-message')[0].scrollIntoView()
            }
        );
        return false;
    });

    if (typeof formData != 'undefined') {
        jQuery('form.ajax').deserialize(formData);
    }


    jQuery('form.addfeed input[name=furl]').on('paste', function() {
        if (feedInfoTimeout) clearTimeout(feedInfoTimeout);
        feedInfoTimeout = setTimeout("feedInfo()", 100);
    });
    jQuery('form.addfeed input[name=furl]').on('keyup', function() {
        if (feedInfoTimeout) clearTimeout(feedInfoTimeout);
        feedInfoTimeout = setTimeout("feedInfo()", 500);
    });


    /**
     * Update now support
     */
    if (jQuery('#updatenow').length >  0) {
        jQuery.post(ajaxurl, '&action=rssmagic&ajaxaction=updatenowlist' , function(response) {
            feedInfo = false;
            if (response.code == 0) {
                updateList = response.data;
                jQuery('#updatenow').html('Updating...');
                setTimeout('updateOne()', 100);
            }
        });
    };
});

var updateList;

function updateAll(list) {
    updateList = list;
    updateOne();
}

function updateOne() {
    if (updateList.length == 0) {
        jQuery('<div></div>', {className:'item', html:'DONE'}).appendTo(jQuery('#updatenow'));
        return;
    }
    var feed = updateList.pop();
    var title = feed.ftitle;
    if (title == '') {
        title = feed.furl;
    }
    jQuery('<div></div>', {className:'item', html:title + '...'}).appendTo(jQuery('#updatenow'));
    jQuery.post(ajaxurl, '&action=rssmagic&ajaxaction=updatenowfeed&id=' + feed.fid , function(response) {
        if (response.code == 0) {
            jQuery('#updatenow div:last').html( jQuery('#updatenow div:last').html() + response.data);
            setTimeout('updateOne()', 100);
        }
    });
}

var feedInfoAjax = false;
var feedInfoTimeout;
function feedInfo() {
    var form = jQuery('form.addfeed');
    var url = form.find('input[name=furl]').val();
    if (!isValidUrl(url) || feedInfoAjax) return;
    var postData = form.serialize() + '&action=rssmagic&ajaxaction=feedinfo';
    feedInfoAjax = true;
    jQuery.post(ajaxurl, postData , function(response) {
        feedInfoAjax = false;
        if (response.code == 0) {
            if (response.data.urlFound) {
                showMessage('Feed URL is already in database');
            } else if (response.data.hostFound) {
                showMessage('Feed HOST is already in database with url ' + response.data.hostFound.furl);
            }
            if (response.data.feed) {
                form.find('input[name=ftitle]').val(response.data.feed.title);
                form.find('textarea[name=fdescription]').val(response.data.feed.description);
            } else {
                showMessage('Invalid feed format');
            }
        }
    });
}

function showMessage(text) {
    jQuery('#rss-message p').text(text);
    jQuery('#rss-message').removeClass('error');
    jQuery('#rss-message').show();
}

function hideMessage() {
    jQuery('#rss-message').hide();
}


