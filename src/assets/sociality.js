(function ($) {
    "use strict";
    var $document = $(document);

    // thumbs/heart click
    $document.on('click', '[data-sociality-like="thumbs"]:not(.busy) .sociality-like-icon, [data-sociality-like="thumbs"]:not(.busy) .sociality-dislike-icon, [data-sociality-like="heart"]:not(.busy) .sociality-like-button', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $this = $(this);
        var $parent = $this.closest('[data-sociality-like]:not(.busy)');

        if (!$parent.length) {
            return;
        }
        $parent.addClass('busy');

        var $count = $parent.find('.sociality-likes-count');
        var post_id = $parent.attr('data-post-id');
        var post_type = $parent.attr('data-post-type');
        var action = $parent.attr('data-sociality-like');

        if ('thumbs' === action) {
            if ($this.hasClass('sociality-like-icon')) {
                action = 'thumb-up';
            } else {
                action = 'thumb-down';
            }
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