(function ($) {
    "use strict";
    var $body = $('body');

    // heart click
    $body.on('click', '[data-sociality-like="heart"]:not(.busy)', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $this = $(this).addClass('busy');
        var $count = $this.find('.sociality-likes-count');
        var post_id = $this.attr('data-post-id');
        var post_type = $this.attr('data-post-type');
        var liked = $this.attr('data-post-type');
        var action = 'heart';

        $.ajax({
            type: "post",
            url: socialityData.ajax_url,
            data: "action=sociality-like-action&nonce=" + socialityData.ajax_nonce + "&post_id=" + post_id + "&post_type=" + post_type + "&like_action=" + action,
            success: function(data) {
                if(typeof data === 'object' && data.success) {
                    $count.text(data.likes_count);
                    $this.attr('data-post-likes-count', data.likes_count);
                    $this.attr('data-post-liked', data.post_liked);
                }
                $this.removeClass('busy');
            },
            error: function(data) {
                console.log(data);
                $this.removeClass('busy');
            }
        });
    });

    // thumbs click
    $body.on('click', '.sociality-like-icon, .sociality-dislike-icon', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $this = $(this);
        var $parent = $this.closest('[data-sociality-like="thumbs"]:not(.busy)');

        if (!$parent.length) {
            return;
        }
        $parent.addClass('busy');

        var $count = $parent.find('.sociality-likes-count');
        var post_id = $parent.attr('data-post-id');
        var post_type = $parent.attr('data-post-type');
        var liked = $parent.attr('data-post-type');


        var action = 'thumb';
        if ($this.hasClass('sociality-like-icon')) {
            action += '-up';
        } else {
            action += '-down';
        }

        $.ajax({
            type: "post",
            url: socialityData.ajax_url,
            data: "action=sociality-like-action&nonce=" + socialityData.ajax_nonce + "&post_id=" + post_id + "&post_type=" + post_type + "&like_action=" + action,
            success: function(data) {
                if(typeof data === 'object' && data.success) {
                    $count.text(data.likes_count);
                    $parent.attr('data-post-likes-count', data.likes_count);
                    $parent.attr('data-post-liked', data.post_liked);
                }
                $parent.removeClass('busy');
            },
            error: function(data) {
                console.log(data);
                $parent.removeClass('busy');
            }
        });
    });
}(jQuery));