<?php
/**
 * Sharing Buttons
 */
?>
<div class="nk-sociality-sharing nk-share-icons" data-url="<?php the_permalink();?>">
    <?php
    $icons = sociality()->settings()->get_option('socials','sociality_sharing', array(
        'facebook'    => 'facebook',
        'twitter'     => 'twitter',
        'google_plus' => 'google_plus',
        'pinterest'   => 'pinterest'
    ));

    foreach($icons as $icon) {
        switch ($icon) {
            case 'facebook':
                ?>
                <div class="facebook nk-sociality-sharing-icon nk-share-icon fa fa-facebook" title="<?php esc_attr_e('Share page on Facebook', NK_SOCIALITY_DOMAIN)?>"></div>
                <?php
                break;
            case 'twitter':
                ?>
                <div class="twitter nk-sociality-sharing-icon nk-share-icon fa fa-twitter" title="<?php esc_attr_e('Share page on Twitter', NK_SOCIALITY_DOMAIN)?>"></div>
                <?php
                break;
            case 'google_plus':
                ?>
                <div class="plusone nk-sociality-sharing-icon nk-share-icon fa fa-google-plus" title="<?php esc_attr_e('Share page on Google+', NK_SOCIALITY_DOMAIN)?>"></div>
                <?php
                break;
            case 'pinterest':
                ?>
                <div class="pinterest nk-sociality-sharing-icon nk-share-icon fa fa-pinterest" title="<?php esc_attr_e('Share page on Pinterest', NK_SOCIALITY_DOMAIN)?>"></div>
                <?php
                break;
            case 'vkontakte':
                ?>
                <div class="vkontakte nk-sociality-sharing-icon nk-share-icon fa fa-vk" title="<?php esc_attr_e('Share page on VK', NK_SOCIALITY_DOMAIN)?>"></div>
                <?php
                break;
        }
    }
    ?>
</div>