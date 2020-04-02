<?php
/**
 * Posts Author BIO Block.
 *
 * @package sociality
 */

if ( ! class_exists( 'Sociality_Author_Bio' ) ) :
    /**
     * Sociality_Author_Bio Class
     */
    class Sociality_Author_Bio {
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
         * Sociality_Author_Bio constructor.
         */
        private function __construct() {
            /* We do nothing here! */
        }

        /**
         * Init actions.
         */
        private function init_actions() {
            // add action to show author bio template.
            add_action( 'sociality-author-bio', array( $this, 'bio_custom_action' ) );

            // add filter to show bio before or after content.
            add_filter( 'the_content', array( $this, 'bio_content' ) );

            // add shortcode.
            add_shortcode( 'sociality_author_bio', array( $this, 'bio_shortcode' ) );

            // add admin author settings.
            add_action( 'show_user_profile', array( $this, 'print_admin_author_settings' ) );
            add_action( 'edit_user_profile', array( $this, 'print_admin_author_settings' ) );

            // save admin author settings.
            add_action( 'personal_options_update', array( $this, 'save_admin_author_settings' ) );
            add_action( 'edit_user_profile_update', array( $this, 'save_admin_author_settings' ) );
        }

        /**
         * Custom BIO.
         */
        public function bio_custom_action() {
            $place = sociality()->settings()->get_option( 'place', 'sociality_author_bio', null );
            if ( is_array( $place ) && isset( $place['custom_action'] ) || null === $place ) {
                // phpcs:ignore
                echo $this->print_author_bio();
            }
        }

        /**
         * BIO before/after content.
         *
         * @param String $content - post content.
         *
         * @return String
         */
        public function bio_content( $content ) {
            $place = sociality()->settings()->get_option( 'place', 'sociality_author_bio', null );

            if ( is_array( $place ) && isset( $place['before_content'] ) ) {
                $content = $this->print_author_bio() . $content;
            }
            if ( is_array( $place ) && isset( $place['after_content'] ) ) {
                $content .= $this->print_author_bio();
            }

            return $content;
        }

        /**
         * BIO Shortcode.
         */
        public function bio_shortcode() {
            return $this->print_author_bio();
        }

        /**
         * Print Author Bio
         */
        public function print_author_bio() {
            $output = '';

            if ( is_single() ) {
                ob_start();
                sociality()->include_template( 'post-author-bio.php' );
                $output = ob_get_contents();
                ob_end_clean();
            }

            return $output;
        }

        /**
         * Admin Enqueue Assets.
         */
        public function admin_author_settings_enqueue_assets() {
            // css.
            wp_enqueue_style( 'bootstrap-custom', sociality()->plugin_url . 'assets/vendor/bootstrap/css/bootstrap-custom.css', array(), '3.3.7' );
            wp_enqueue_style( 'fontawesome-iconpicker', sociality()->plugin_url . 'assets/vendor/iconpicker/css/fontawesome-iconpicker.min.css', array(), '3.2.0' );
            wp_enqueue_style( 'sociality-admin', sociality()->plugin_url . 'assets/sociality-admin.min.css', array(), '@@plugin_version' );

            // js.
            wp_enqueue_script( 'bootstrap', sociality()->plugin_url . 'assets/vendor/bootstrap/js/bootstrap.min.js', array( 'jquery' ), '3.3.7', true );
            wp_enqueue_script( 'fontawesome-iconpicker', sociality()->plugin_url . 'assets/vendor/iconpicker/js/fontawesome-iconpicker.min.js', array( 'bootstrap' ), '3.2.0', true );
            wp_enqueue_script( 'sociality-admin', sociality()->plugin_url . 'assets/sociality-admin.min.js', array( 'jquery' ), '@@plugin_version', true );

            wp_localize_script(
                'sociality-admin',
                'socialityAdmin',
                array(
                    'icons' => sociality()->get_icons_array(),
                )
            );
        }

        /**
         * Extra settings in user profile
         *
         * @param Object $user - user data.
         */
        public function print_admin_author_settings( $user ) {
            $this->admin_author_settings_enqueue_assets();

            $user_social_links = get_the_author_meta( 'user_sociality_links', $user->ID );
            ?>
                <h3><?php echo esc_html__( 'Social Links', '@@text_domain' ); ?></h3>
                <table class="form-table">
                    <tr>
                        <th></th>

                        <td class="sociality-icon-picker">
                            <?php if ( is_array( $user_social_links ) ) : ?>
                                <?php foreach ( $user_social_links as $k => $val ) { ?>
                                    <div class="input-group sociality-icp">
                                        <span class="btn btn-default iconpicker-component input-group-btn">
                                            <i>
                                                <?php
                                                if ( sociality()->svg_icons()->exists( isset( $val['icon'] ) ? $val['icon'] : '' ) ) {
                                                    sociality()->svg_icons()->get_e( $val['icon'] );
                                                } else {
                                                    echo esc_html__( 'Icon', '@@text_domain' );
                                                }
                                                ?>
                                            </i>
                                            <input type="hidden" class="iconpicker-input" name="user_sociality_links[<?php echo esc_attr( $k ); ?>][icon]" value="<?php echo esc_attr( isset( $val['icon'] ) ? $val['icon'] : '' ); ?>">
                                        </span>
                                        <input class="form-control" value="<?php echo esc_attr( isset( $val['url'] ) ? $val['url'] : '' ); ?>" type="url" placeholder="https://..." name="user_sociality_links[<?php echo esc_attr( $k ); ?>][url]">
                                        <span class="btn btn-danger sociality-icon-picker-remove input-group-btn">
                                            <i class="dashicons dashicons-no-alt"></i>
                                        </span>
                                    </div>
                                <?php } ?>
                            <?php endif; ?>

                            <br>
                            <span class="btn btn-primary sociality-icon-picker-add">
                                <i class="dashicons dashicons-plus"></i>
                            </span>
                        </td>
                    </tr>
                </table>
            <?php
        }

        /**
         * Save Settings.
         *
         * @param Number $user_id - used ID.
         */
        public function save_admin_author_settings( $user_id ) {
            if ( ! current_user_can( 'edit_user', $user_id ) ) {
                return;
            }

            // phpcs:ignore
            update_user_meta( $user_id, 'user_sociality_links', $_POST['user_sociality_links'] );
        }
    }
endif;
