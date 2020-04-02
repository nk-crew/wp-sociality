<?php
/**
 * Plugin Name:  Sociality
 * Description:  Social features for the theme authors
 * Version:      @@plugin_version
 * Author:       nK
 * Author URI:   https://nkdev.info
 * License:      GPLv2 or later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  @@text_domain
 *
 * @package sociality
 */

// Make sure we don't expose any info if called directly.
if ( ! function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

if ( ! class_exists( 'Sociality' ) ) :
    /**
     * Sociality Class
     */
    class Sociality {
        /**
         * The single class instance.
         *
         * @var $instance
         */
        private static $instance = null;

        /**
         * Path to the plugin directory
         *
         * @var $plugin_path
         */
        public $plugin_path;

        /**
         * URL to the plugin directory
         *
         * @var $plugin_url
         */
        public $plugin_url;

        /**
         * Plugin name
         *
         * @var $plugin_name
         */
        public $plugin_name;

        /**
         * Plugin version
         *
         * @var $plugin_version
         */
        public $plugin_version;

        /**
         * Plugin slug
         *
         * @var $plugin_slug
         */
        public $plugin_slug;

        /**
         * Plugin name sanitized
         *
         * @var $plugin_name_sanitized
         */
        public $plugin_name_sanitized;

        /**
         * Main Instance
         * Ensures only one instance of this class exists in memory at any one time.
         */
        public static function instance() {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
                self::$instance->init_text_domain();
                self::$instance->init_options();
                self::$instance->init_hooks();

                // include helper files.
                self::$instance->include_dependencies();

                // run some classes.
                self::$instance->settings();
                self::$instance->author_bio();
                self::$instance->likes();
                self::$instance->sharing();
            }
            return self::$instance;
        }

        /**
         * Sociality constructor.
         */
        public function __construct() {
            /* We do nothing here! */
        }

        /**
         * PHP translations.
         */
        public function init_text_domain() {
            load_plugin_textdomain( '@@text_domain', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
        }

        /**
         * Init options.
         */
        public function init_options() {
            $this->plugin_path = plugin_dir_path( __FILE__ );
            $this->plugin_url  = plugin_dir_url( __FILE__ );
        }

        /**
         * Init hooks.
         */
        public function init_hooks() {
            add_action( 'admin_init', array( $this, 'admin_init' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        }

        /**
         * Init admin.
         */
        public function admin_init() {
            // get current plugin data.
            $data                        = get_plugin_data( __FILE__ );
            $this->plugin_name           = $data['Name'];
            $this->plugin_version        = $data['Version'];
            $this->plugin_slug           = plugin_basename( __FILE__, '.php' );
            $this->plugin_name_sanitized = basename( __FILE__, '.php' );
        }

        /**
         * Enqueue assets.
         */
        public function enqueue_assets() {
            wp_enqueue_style( 'sociality', sociality()->plugin_url . 'assets/sociality.min.css', array(), '@@plugin_version' );

            wp_enqueue_script( 'sociality', sociality()->plugin_url . 'assets/sociality.min.js', array( 'jquery' ), '@@plugin_version', true );
            wp_enqueue_script( 'sociality-share', sociality()->plugin_url . 'assets/sociality-share/sociality-share.min.js', array( 'jquery' ), '@@plugin_version', true );

            wp_localize_script(
                'sociality',
                'socialityData',
                array(
                    'ajax_url'   => admin_url( 'admin-ajax.php' ),
                    'ajax_nonce' => wp_create_nonce( 'ajax-nonce' ),
                )
            );
        }

        /**
         * Enqueue admin assets.
         */
        public function admin_enqueue_scripts() {
            wp_enqueue_style( 'sociality-admin', sociality()->plugin_url . 'assets/sociality-admin.min.css', array(), '@@plugin_version' );
        }

        /**
         * Include template.
         * print template file (first check for theme /sociality-templates/...php)
         *
         * @param string $template_name - template name.
         * @param array  $args - additional arguments for template.
         */
        public function include_template( $template_name, $args = array() ) {
            if ( ! empty( $args ) && is_array( $args ) ) {
                // phpcs:ignore
                extract( $args );
            }

            // template in theme folder.
            $template = locate_template( array( 'sociality/' . $template_name, $template_name ) );

            // template from plugins folder.
            if ( ! $template ) {
                $template = locate_template( array( 'plugins/sociality/' . $template_name, $template_name ) );
            }

            // default template.
            if ( ! $template ) {
                $template = $this->plugin_path . 'templates/' . $template_name;
            }

            // Allow 3rd party plugin filter template file from their plugin.
            $template = apply_filters( 'sociality_include_template', $template, $template_name, $args );

            do_action( 'sociality_before_include_template', $template, $template_name, $args );

            include $template;

            do_action( 'sociality_after_include_template', $template, $template_name, $args );
        }

        /**
         * Include dependencies.
         */
        private function include_dependencies() {
            require_once $this->plugin_path . 'classes/class-svg-icons.php';
            require_once $this->plugin_path . 'classes/class-settings-api.php';
            require_once $this->plugin_path . 'classes/class-settings.php';
            require_once $this->plugin_path . 'classes/class-author-bio.php';
            require_once $this->plugin_path . 'classes/class-likes.php';
            require_once $this->plugin_path . 'classes/class-sharing.php';
        }

        /**
         * Class SVG Icons
         */
        public function svg_icons() {
            return Sociality_SVG_Icons::instance();
        }

        /**
         * Class Settings
         */
        public function settings() {
            return Sociality_Settings::instance();
        }

        /**
         * Class BIO
         */
        public function author_bio() {
            return Sociality_Author_Bio::instance();
        }

        /**
         * Class Likes
         */
        public function likes() {
            return Sociality_Likes::instance();
        }

        /**
         * Class Sharing
         */
        public function sharing() {
            return Sociality_Sharing::instance();
        }

        /**
         * Get used icons
         */
        public function get_icons_array() {
            $icons  = $this->svg_icons()->get_all_brands( true );
            $result = array();

            foreach ( $icons as $name => $data ) {
                $result[] = array(
                    'title'       => $name,
                    'svg'         => $data['svg'],
                    'searchTerms' => array( $name ),
                );
            }

            return apply_filters( 'sociality_icons_array', $result );
        }
    }
endif;

if ( ! function_exists( 'sociality' ) ) :
    /**
     * Function works with the Sociality class instance
     *
     * @return object Sociality
     */
    function sociality() {
        return Sociality::instance();
    }
endif;
add_action( 'plugins_loaded', 'sociality' );
