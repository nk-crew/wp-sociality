<?php
/**
 * Sharing Buttons
 *
 * @package sociality
 */

$sclt_icons         = sociality()->settings()->get_option(
    'socials',
    'sociality_sharing',
    array(
        'facebook'    => 'facebook',
        'twitter'     => 'twitter',
        'pinterest'   => 'pinterest',
    )
);
$sclt_show_counters = sociality()->settings()->get_option( 'show_counters', 'sociality_sharing', true );
$sclt_url           = get_the_permalink();
$sclt_title         = get_the_title();

?>

<div class="sociality-share" data-url="<?php echo esc_url( $sclt_url ); ?>" data-title="<?php echo esc_attr( $sclt_title ); ?>" data-counters="<?php echo $sclt_show_counters ? 'true' : 'false'; ?>">
    <?php
    foreach ( $sclt_icons as $sclt_icon ) {
        switch ( $sclt_icon ) {
            case 'facebook':
                ?>
                <div class="sociality-share-button" title="<?php esc_attr_e( 'Share page on Facebook', '@@text_domain' ); ?>" data-share="facebook">
                    <span class="socicon-facebook"></span>
                    <span class="sociality-share-counter"></span>
                </div>
                <?php
                break;
            case 'twitter':
                ?>
                <div class="sociality-share-button" title="<?php esc_attr_e( 'Share page on Twitter', '@@text_domain' ); ?>" data-share="twitter">
                    <span class="socicon-twitter"></span>
                    <span class="sociality-share-counter"></span>
                </div>
                <?php
                break;
            case 'pinterest':
                $sclt_media = get_the_post_thumbnail_url( null, 'full' );
                ?>
                <div class="sociality-share-button" title="<?php esc_attr_e( 'Share page on Pinterest', '@@text_domain' ); ?>" data-share="pinterest" data-media="<?php echo esc_url( $sclt_media ); ?>">
                    <span class="socicon-pinterest"></span>
                    <span class="sociality-share-counter"></span>
                </div>
                <?php
                break;
            case 'vkontakte':
                ?>
                <div class="sociality-share-button" title="<?php esc_attr_e( 'Share page on VK', '@@text_domain' ); ?>" data-share="vkontakte">
                    <span class="socicon-vkontakte"></span>
                    <span class="sociality-share-counter"></span>
                </div>
                <?php
                break;
        }
    }
    ?>
</div>
