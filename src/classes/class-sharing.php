<?php
/**
 * Sharing Block
 *
 * @package sociality
 */

if ( ! class_exists( 'Sociality_Sharing' ) ) :
    /**
     * Sociality_Sharing Class
     */
    class Sociality_Sharing {
        /**
         * The single class instance.
         *
         * @var $instance
         */
        private static $instance = null;

        /**
         * Main Instance
         * Ensures only one instance of this class exists in memory at any one time.
         */
        public static function instance() {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
                self::$instance->init_actions();
            }
            return self::$instance;
        }

        /**
         * Sociality_Sharing constructor.
         */
        private function __construct() {
            /* We do nothing here! */
        }

        /**
         * Init actions.
         */
        private function init_actions() {
            // Render sharing page for WeChat.
            add_action( 'wp', array( $this, 'register_scripts' ) );
            add_action( 'template_redirect', array( $this, 'wechat_share_render' ) );

            // add action to show sharing buttons template.
            add_action( 'sociality_sharing', array( $this, 'sharing_custom_action' ) );
            add_action( 'sociality-sharing', array( $this, 'sharing_custom_action' ) ); // fallback.

            // add filter to show sharing buttons before or after content.
            add_filter( 'the_content', array( $this, 'sharing_content' ) );

            // add shortcode.
            add_shortcode( 'sociality_sharing', array( $this, 'sharing_shortcode' ) );
        }

        /**
         * Register scripts.
         */
        public function register_scripts() {
            wp_register_style( 'sociality-share-wechat', sociality()->plugin_url . 'assets/sociality-share/sociality-share-wechat.min.css', array(), '@@plugin_version' );

            wp_register_script( 'qrcode', sociality()->plugin_url . 'assets/vendor/qrcode/qrcode.min.js', array( 'jquery' ), '@@plugin_version', false );
            wp_register_script( 'sociality-share-wechat', sociality()->plugin_url . 'assets/sociality-share/sociality-share-wechat.min.js', array( 'jquery' ), '@@plugin_version', false );
        }

        /**
         * Render sharing page for WeChat.
         */
        public function wechat_share_render() {
            // phpcs:ignore
            if ( ! isset( $_GET['sociality_share_wechat'] ) || ! isset( $_GET['url'] ) || ! is_string( $_GET['url'] ) ) {
                return;
            }

            // phpcs:ignore
            $share_url = esc_url_raw( wp_unslash( $_GET['url'] ) );

            ?>
            <!DOCTYPE html>
            <html <?php language_attributes(); ?>>
                <head>
                    <title><?php echo esc_html__( 'Share to WeChat', '@@text_domain' ); ?></title>
                    <meta name="viewport" content="width=device-width, initial-scale=1">

                    <?php wp_styles()->do_item( 'sociality-share-wechat' ); ?>
                </head>
                <body data-share-url="<?php echo esc_url( $share_url ); ?>">
                    <h1>
                        <?php echo esc_html__( 'Share to WeChat', '@@text_domain' ); ?>
                    </h1>
                    <noscript>
                        <p><?php echo esc_html__( 'This page requires JavaScript to be enabled in your browser.', '@@text_domain' ); ?></p>
                    </noscript>

                    <div class="sociality-share-wechat-desktop">
                        <p><?php echo wp_kses_post( __( '"Scan QR Code" in WeChat and tap <span class="sociality-share-wechat-share-how">···</span> to share.', '@@text_domain' ) ); ?></p>
                        <div class="sociality-share-wechat-qrcode"></div>
                    </div>
                    <div class="sociality-share-wechat-mobile">
                        <p><?php echo esc_html__( 'Copy the link and open WeChat to share.', '@@text_domain' ); ?></p>
                        <input class="sociality-share-wechat-copy-url" readonly type="text">
                        <span class="sociality-share-wechat-copied"><?php echo esc_html__( 'Copied!', '@@text_domain' ); ?></span>
                        <a class="sociality-share-wechat-button" href="weixin://"><?php echo esc_html__( 'Open WeChat', '@@text_domain' ); ?></a>
                    </div>

                    <?php
                    wp_scripts()->print_scripts( 'qrcode' );
                    wp_scripts()->print_scripts( 'sociality-share-wechat' );
                    ?>
                </body>
            </html>
            <?php

            exit;
        }

        /**
         * Sharing buttons custom action.
         */
        public function sharing_custom_action() {
            $place = sociality()->settings()->get_option( 'place', 'sociality_sharing', null );
            if ( is_array( $place ) && isset( $place['custom_action'] ) || null === $place ) {
                // phpcs:ignore
                echo $this->print_sharing();
            }
        }

        /**
         * Sharing before/after content.
         *
         * @param string $content - post content.
         *
         * @return string
         */
        public function sharing_content( $content ) {
            // Check AMP.
            if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) {
                return $content;
            }

            // Single posts only.
            if ( ! is_singular( 'post' ) || ! is_single( get_the_ID() ) ) {
                return $content;
            }

            // Skip sharing using global variable.
            global $sociality_sharing_skip;
            if ( $sociality_sharing_skip ) {
                return $content;
            }

            $sociality_sharing_skip = true;

            $place = sociality()->settings()->get_option( 'place', 'sociality_sharing', null );

            if ( is_array( $place ) && isset( $place['before_content'] ) ) {
                $content = $this->print_sharing() . $content;
            }
            if ( is_array( $place ) && isset( $place['after_content'] ) ) {
                $content .= $this->print_sharing();
            }

            $sociality_sharing_skip = false;

            return $content;
        }

        /**
         * Sharing shortcode.
         *
         * @return string
         */
        public function sharing_shortcode() {
            return $this->print_sharing();
        }

        /**
         * Print Sharing Buttons
         *
         * @return string
         */
        public function print_sharing() {
            ob_start();
            sociality()->include_template( 'sharing-buttons.php' );
            return ob_get_clean();
        }
    }
endif;
