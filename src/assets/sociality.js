const { socialityData, jQuery: $ } = window;

const $document = $(document);

// thumbs/heart click
$document.on(
  'click',
  '[data-sociality-like="thumbs"]:not(.busy) .sociality-like-icon, [data-sociality-like="thumbs"]:not(.busy) .sociality-dislike-icon, [data-sociality-like="heart"]:not(.busy) .sociality-like-button',
  function (e) {
    e.preventDefault();
    e.stopPropagation();

    const $this = $(this);
    const $parent = $this.closest('[data-sociality-like]:not(.busy)');

    if (!$parent.length) {
      return;
    }
    $parent.addClass('busy');

    const $count = $parent.find('.sociality-likes-count');
    const postId = $parent.attr('data-post-id');
    const postType = $parent.attr('data-post-type');
    let action = $parent.attr('data-sociality-like');

    if (action === 'thumbs') {
      if ($this.hasClass('sociality-like-icon')) {
        action = 'thumb-up';
      } else {
        action = 'thumb-down';
      }
    }

    $.ajax({
      type: 'post',
      url: socialityData.ajax_url,
      data: `action=sociality-like-action&nonce=${socialityData.ajax_nonce}&post_id=${postId}&post_type=${postType}&like_action=${action}`,
      success(data) {
        if (typeof data === 'object' && data.success) {
          $count.text(data.likes_count);
          $parent.attr('data-post-likes-count', data.likes_count);
          $parent.attr('data-post-liked', data.post_liked);
        }
        $parent.removeClass('busy');
      },
      error(data) {
        // eslint-disable-next-line
        console.log(data);
        $parent.removeClass('busy');
      },
    });
  }
);
