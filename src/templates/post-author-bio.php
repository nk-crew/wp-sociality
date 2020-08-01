<?php
/**
 * Author BIO template for posts.
 *
 * @package sociality
 */

?>

<div class="sociality-author-bio">
    <?php
    $sclt_show_avatar       = sociality()->settings()->get_option( 'show_avatar', 'sociality_author_bio', true );
    $sclt_show_name         = sociality()->settings()->get_option( 'show_name', 'sociality_author_bio', true );
    $sclt_show_description  = sociality()->settings()->get_option( 'show_description', 'sociality_author_bio', true );
    $sclt_show_social_links = sociality()->settings()->get_option( 'show_social_links', 'sociality_author_bio', true );

    // avatar.
    if ( $sclt_show_avatar ) {
        $sclt_avatar_size = apply_filters( 'sociality_author_bio_avatar_size', 100 );
        ?>
        <div class="sociality-author-bio-avatar">
            <?php echo get_avatar( get_the_author_meta( 'user_email' ), $sclt_avatar_size ); ?>
        </div>
        <?php
    }

    // name.
    if ( $sclt_show_name ) {
        ?>
        <h4 class="sociality-author-bio-name">
            <a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" rel="author">
                <?php
                echo get_the_author();
                ?>
            </a>
        </h4>
        <?php
    }

    // description.
    if ( $sclt_show_description && get_the_author_meta( 'description' ) ) {
        ?>
        <div class="sociality-author-bio-description">
            <?php the_author_meta( 'description' ); ?>
        </div>
        <?php
    }

    // social links.
    if ( $sclt_show_social_links ) {
        $sclt_social_links = get_the_author_meta( 'user_sociality_links', get_the_author_meta( 'ID' ) );

        if ( is_array( $sclt_social_links ) && count( $sclt_social_links ) > 0 ) {
            ?>
            <div class="sociality-author-bio-links">
                <?php
                foreach ( $sclt_social_links as $sclt_social_item ) {
                    ?>
                    <a href="<?php echo esc_url( $sclt_social_item['url'] ); ?>" target="_blank" rel="noopener noreferrer">
                        <?php sociality()->svg_icons()->get_e( $sclt_social_item['icon'] ); ?>
                    </a>
                    <?php
                }
                ?>
            </div>
            <?php
        }
    }
    ?>
</div>
