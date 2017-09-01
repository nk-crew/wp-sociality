<?php
/**
 * Author BIO template for posts
 */
?>

<div class="sociality-author-bio">
    <?php
    $show_avatar = sociality()->settings()->get_option('show_avatar','sociality_author_bio',true);
    $show_name = sociality()->settings()->get_option('show_name','sociality_author_bio',true);
    $show_description = sociality()->settings()->get_option('show_description','sociality_author_bio',true);
    $show_social_links = sociality()->settings()->get_option('show_social_links','sociality_author_bio',true);

    // avatar
    if ($show_avatar) {
        $avatar_size = apply_filters('sociality_author_bio_avatar_size', 100);
        ?>
        <div class="sociality-author-bio-avatar">
            <?php echo get_avatar(get_the_author_meta('user_email'), $avatar_size); ?>
        </div>
        <?php
    }

    // name
    if ($show_name) { ?>
        <h4 class="sociality-author-bio-name">
            <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>" rel="author">
                <?php
                echo get_the_author(); ?>
            </a>
        </h4>
    <?php }

    // description
    if ($show_description) {
        ?>
        <div class="sociality-author-bio-description">
            <?php the_author_meta('description'); ?>
        </div>
        <?php
    }

    // social links
    if ($show_social_links) {
        $social_links = get_the_author_meta('user_sociality_links', get_the_author_meta('ID'));

        if (is_array($social_links) && count($social_links) > 0) {
            ?> <div class="sociality-author-bio-links"> <?php
            foreach($social_links as $social_item) {
                ?>
                <a href="<?php echo esc_url($social_item['url']); ?>"><i class="<?php echo khaki_sanitize_class($social_item['icon']); ?>"></i></a>
                <?php
            }
            ?> </div> <?php
        }
    } ?>
</div>