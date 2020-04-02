<?php
/**
 * Plugin Settings.
 *
 * @package sociality
 */

if ( ! class_exists( 'Sociality_Settings' ) ) :
    /**
     * Sociality_Settings Class
     */
    class Sociality_Settings {
        /**
         * The single class instance.
         *
         * @var $instance
         */
        private static $instance = null;

        /**
         * Settings api.
         *
         * @var object
         */
        public $settings_api;

        /**
         * Main Instance
         * Ensures only one instance of this class exists in memory at any one time.
         */
        public static function instance() {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
                if ( is_admin() ) {
                    self::$instance->init_actions();
                }
            }
            return self::$instance;
        }

        /**
         * Sociality_Settings constructor.
         */
        private function __construct() {
            /* We do nothing here! */
        }

        /**
         * Get Option Value
         *
         * @param string $option - option name.
         * @param string $section - option section.
         * @param mixed  $default - default value.
         *
         * @return mixed
         */
        public function get_option( $option, $section, $default = '' ) {

            $options = get_option( $section );

            if ( isset( $options[ $option ] ) ) {
                return 'off' === $options[ $option ] ? false : ( 'on' === $options[ $option ] ? true : $options[ $option ] );
            }

            return $default;
        }

        /**
         * Init actions.
         */
        private function init_actions() {
            $this->settings_api = new Sociality_Settings_API();

            add_action( 'admin_init', array( $this, 'admin_init' ) );
            add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        }

        /**
         * Initialize the settings.
         */
        public function admin_init() {
            // set the settings.
            $this->settings_api->set_sections( $this->get_settings_sections() );
            $this->settings_api->set_fields( $this->get_settings_fields() );

            // initialize settings.
            $this->settings_api->admin_init();
        }

        /**
         * Register the admin settings menu
         *
         * @return void
         */
        public function admin_menu() {
            add_options_page( 'Sociality Settings', 'Sociality', 'manage_options', 'sociality', array( $this, 'print_settings_page' ) );
        }

        /**
         * Plugin settings sections
         *
         * @return array
         */
        public function get_settings_sections() {
            $sections = array(
                array(
                    'id'    => 'sociality_likes',
                    'title' => __( 'Likes', '@@text_domain' ),
                ),
                array(
                    'id'    => 'sociality_sharing',
                    'title' => __( 'Sharing', '@@text_domain' ),
                ),
                array(
                    'id'    => 'sociality_author_bio',
                    'title' => __( 'Posts Author BIO', '@@text_domain' ),
                ),
            );

            return $sections;
        }

        /**
         * Returns all the settings fields
         *
         * @return array settings fields
         */
        public function get_settings_fields() {
            $settings_fields = array(
                'sociality_likes' => array(
                    array(
                        'name'    => 'info',
                        'desc'    => __( 'To use these options you need to place actions in your theme templates.<br> See actions examples under settings on this page.', '@@text_domain' ),
                        'type'    => 'html',
                    ),
                    array(
                        'name'    => 'type_post',
                        'label'   => __( 'Posts', '@@text_domain' ),
                        'desc'    => '<code>do_action(\'sociality-likes\', get_the_ID(), \'post\');</code>',
                        'type'    => 'select',
                        'options' => array(
                            'disabled' => __( 'Disabled', '@@text_domain' ),
                            'heart'    => __( 'Heart', '@@text_domain' ),
                            'thumbs'   => __( 'Thumbs Up/Down', '@@text_domain' ),
                        ),
                        'default' => 'heart',
                    ),
                    array(
                        'name'    => 'type_page',
                        'label'   => __( 'Pages', '@@text_domain' ),
                        'desc'    => '<code>do_action(\'sociality-likes\', get_the_ID(), \'page\');</code>',
                        'type'    => 'select',
                        'options' => array(
                            'disabled' => __( 'Disabled', '@@text_domain' ),
                            'heart'    => __( 'Heart', '@@text_domain' ),
                            'thumbs'   => __( 'Thumbs Up/Down', '@@text_domain' ),
                        ),
                        'default' => 'disabled',
                    ),
                    array(
                        'name'    => 'type_comment',
                        'label'   => __( 'Comments', '@@text_domain' ),
                        'desc'    => '<code>do_action(\'sociality-likes\', get_comment_ID(), \'comment\');</code>',
                        'type'    => 'select',
                        'options' => array(
                            'disabled' => __( 'Disabled', '@@text_domain' ),
                            'heart'    => __( 'Heart', '@@text_domain' ),
                            'thumbs'   => __( 'Thumbs Up/Down', '@@text_domain' ),
                        ),
                        'default' => 'heart',
                    ),
                    array(
                        'name'    => 'type_wc_product',
                        'label'   => __( 'WooCommerce Products', '@@text_domain' ),
                        'desc'    => '<code>do_action(\'sociality-likes\', get_the_ID(), \'wc_product\');</code>',
                        'type'    => 'select',
                        'options' => array(
                            'disabled' => __( 'Disabled', '@@text_domain' ),
                            'heart'    => __( 'Heart', '@@text_domain' ),
                            'thumbs'   => __( 'Thumbs Up/Down', '@@text_domain' ),
                        ),
                        'default' => 'disabled',
                    ),
                    array(
                        'name'    => 'type_wc_review',
                        'label'   => __( 'WooCommerce Reviews', '@@text_domain' ),
                        'desc'    => '<code>do_action(\'sociality-likes\', get_comment_ID(), \'wc_review\');</code>',
                        'type'    => 'select',
                        'options' => array(
                            'disabled' => __( 'Disabled', '@@text_domain' ),
                            'heart'    => __( 'Heart', '@@text_domain' ),
                            'thumbs'   => __( 'Thumbs Up/Down', '@@text_domain' ),
                        ),
                        'default' => 'thumbs',
                    ),
                    array(
                        'name'    => 'type_bb_topic',
                        'label'   => __( 'bbPress Topics and Replies', '@@text_domain' ),
                        'desc'    => '<code>do_action(\'sociality-likes\', bbp_get_topic_id(), \'bb_topic\');</code><br><code>do_action(\'sociality-likes\', bbp_get_reply_id(), \'bb_reply\');</code>',
                        'type'    => 'select',
                        'options' => array(
                            'disabled' => __( 'Disabled', '@@text_domain' ),
                            'heart'    => __( 'Heart', '@@text_domain' ),
                            'thumbs'   => __( 'Thumbs Up/Down', '@@text_domain' ),
                        ),
                        'default' => 'heart',
                    ),
                    array(
                        'name'    => 'type_bp_activity',
                        'label'   => __( 'BuddyPress', '@@text_domain' ),
                        'desc'    => '<code>do_action(\'sociality-likes\', bp_get_activity_comment_id(), \'bp_activity\');</code><br><code>do_action(\'sociality-likes\', bp_get_activity_id(), \'bp_activity\');</code>',
                        'type'    => 'select',
                        'options' => array(
                            'disabled' => __( 'Disabled', '@@text_domain' ),
                            'heart'    => __( 'Heart', '@@text_domain' ),
                            'thumbs'   => __( 'Thumbs Up/Down', '@@text_domain' ),
                        ),
                        'default' => 'heart',
                    ),
                    array(
                        'name'    => 'type_custom_portfolio',
                        'label'   => __( 'Custom Portfolio Type', '@@text_domain' ),
                        'desc'    => '<code>do_action(\'sociality-likes\', get_the_ID(), \'portfolio\');</code>',
                        'type'    => 'select',
                        'options' => array(
                            'disabled' => __( 'Disabled', '@@text_domain' ),
                            'heart'    => __( 'Heart', '@@text_domain' ),
                            'thumbs'   => __( 'Thumbs Up/Down', '@@text_domain' ),
                        ),
                        'default' => 'heart',
                    ),
                ),
                'sociality_sharing' => array(
                    array(
                        'name'    => 'place',
                        'label'   => __( 'Place', '@@text_domain' ),
                        'desc'    => __( 'If you need to place sharing buttons in custom place, call <code>do_action(\'sociality-sharing\');</code> in your plugin or theme code. Also available shortcode <code>[sociality_sharing]</code>', '@@text_domain' ),
                        'type'    => 'multicheck',
                        'options' => array(
                            'after_content'  => 'After Content',
                            'before_content' => 'Before Content',
                            'custom_action'  => 'Custom Action \'sociality-sharing\'',
                        ),
                        'default' => array( 'custom_action' => 'custom_action' ),
                    ),
                    array(
                        'name'    => 'socials',
                        'label'   => __( 'Buttons', '@@text_domain' ),
                        'type'    => 'multicheck',
                        'options' => array(
                            'facebook'    => 'Facebook',
                            'twitter'     => 'Twitter',
                            'pinterest'   => 'Pinterest',
                            'vkontakte'   => 'VK',
                        ),
                        'default' => array(
                            'facebook'    => 'facebook',
                            'twitter'     => 'twitter',
                            'pinterest'   => 'pinterest',
                        ),
                    ),
                    array(
                        'name'    => 'show_counters',
                        'label'   => __( 'Show Sharing Counters', '@@text_domain' ),
                        'desc'    => __( 'Yes', '@@text_domain' ),
                        'type'    => 'checkbox',
                        'default' => 'on',
                    ),
                ),
                'sociality_author_bio' => array(
                    array(
                        'name'    => 'place',
                        'label'   => __( 'Place', '@@text_domain' ),
                        'desc'    => __( 'If you need to place bio block in custom place, call <code>do_action(\'sociality-author-bio\');</code> in your plugin or theme code. Also available shortcode <code>[sociality_author_bio]</code>', '@@text_domain' ),
                        'type'    => 'multicheck',
                        'options' => array(
                            'after_content'  => 'After Content',
                            'before_content' => 'Before Content',
                            'custom_action'  => 'Custom Action \'sociality-author-bio\'',
                        ),
                        'default' => array( 'custom_action' => 'custom_action' ),
                    ),
                    array(
                        'name'    => 'show_avatar',
                        'label'   => __( 'Show Avatar', '@@text_domain' ),
                        'desc'    => __( 'Yes', '@@text_domain' ),
                        'type'    => 'checkbox',
                        'default' => 'on',
                    ),
                    array(
                        'name'    => 'show_name',
                        'label'   => __( 'Show Name', '@@text_domain' ),
                        'desc'    => __( 'Yes', '@@text_domain' ),
                        'type'    => 'checkbox',
                        'default' => 'on',
                    ),
                    array(
                        'name'    => 'show_description',
                        'label'   => __( 'Show Biographical Info', '@@text_domain' ),
                        'desc'    => __( 'Yes', '@@text_domain' ),
                        'type'    => 'checkbox',
                        'default' => 'on',
                    ),
                    array(
                        'name'    => 'show_social_links',
                        'label'   => __( 'Show Social Links', '@@text_domain' ),
                        'desc'    => __( 'Yes. <small>[You can set social links in your profile settings page]</small>', '@@text_domain' ),
                        'type'    => 'checkbox',
                        'default' => 'on',
                    ),
                ),
            );

            return $settings_fields;
        }

        /**
         * The plguin page handler
         *
         * @return void
         */
        public function print_settings_page() {
            echo '<div class="wrap">';
            echo '<h2>' . esc_html__( 'Sociality Settings', '@@text_domain' ) . '</h2>';

            $this->settings_api->show_navigation();
            $this->settings_api->show_forms();

            echo '</div>';
        }
    }
endif;
