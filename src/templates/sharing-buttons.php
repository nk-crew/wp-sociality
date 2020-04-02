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
$sclt_page_title    = get_the_title();
$sclt_page_excerpt  = get_the_excerpt();
$sclt_page_text     = $sclt_page_title . ( $sclt_page_excerpt ? ( ' : ' . $sclt_page_excerpt ) : '' );
$sclt_media         = get_the_post_thumbnail_url( null, 'full' );

if ( empty( $sclt_icons ) ) {
    return;
}

?>

<div class="sociality-share" data-url="<?php echo esc_url( $sclt_url ); ?>" data-title="<?php echo esc_attr( $sclt_page_title ); ?>" data-media="<?php echo esc_url( $sclt_media ); ?>" data-excerpt="<?php echo esc_attr( $sclt_page_excerpt ); ?>" data-text="<?php echo esc_attr( $sclt_page_text ); ?>" data-counters="<?php echo $sclt_show_counters ? 'true' : 'false'; ?>">
    <div class="sociality-share-inner">
        <?php
        foreach ( $sclt_icons as $sclt_icon ) {
            if ( ! sociality()->svg_icons()->exists( $sclt_icon ) ) {
                continue;
            }

            // translators: s - social brand name.
            $sclt_link_title = sprintf( __( 'Share page on %s', '@@text_domain' ), sociality()->svg_icons()->get_name( $sclt_icon ) );

            ?>
            <a rel="nofollow" href="#" class="sociality-share-button sociality-share-vendor-<?php echo esc_attr( $sclt_icon ); ?>" title="<?php echo esc_attr( $sclt_link_title ); ?>" data-share="<?php echo esc_attr( $sclt_icon ); ?>">
                <?php sociality()->svg_icons()->get_e( $sclt_icon ); ?>
                <span class="sociality-share-counter"></span>
            </a>
            <?php
        }
        ?>
    </div>
</div>
