<?php
/**
 * Like Button
 *
 * @var $likes_count
 *
 * @package sociality
 */

?>

<span class="sociality-like-button nk-action-heart">
    <span class="sociality-like-icon fa fa-heart-o"></span>
    <span class="sociality-liked-icon fa fa-heart"></span>
    <span class="sociality-likes-count num"><?php echo esc_html( $likes_count ); ?></span>
</span>
