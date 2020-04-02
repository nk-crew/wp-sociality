<?php
/**
 * SVG Icons
 *
 * @package sociality
 */

if ( ! class_exists( 'Sociality_SVG_Icons' ) ) :
    /**
     * Sociality_SVG_Icons class
     */
    class Sociality_SVG_Icons {
        /**
         * The single class instance.
         *
         * @var $instance
         */
        private static $instance = null;

        /**
         * Data for SVG icon.
         *
         * @var Array
         */
        private $svg_data = array(
            'class' => 'sociality-icon',
        );

        /**
         * Main Instance
         * Ensures only one instance of this class exists in memory at any one time.
         */
        public static function instance() {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
                self::$instance->init();
            }
            return self::$instance;
        }

        /**
         * Sociality_SVG_Icons constructor.
         */
        private function __construct() {
            /* We do nothing here! */
        }

        /**
         * Init.
         */
        private function init() {
            require_once $this->plugin_path . 'vendor/brand-svg-please.php';
        }

        /**
         * Get Sharing Buttons
         *
         * @param String $name - brand name.
         *
         * @return String
         */
        public function get( $name ) {
            return Brand_SVG_Please::get( $name, $this->svg_data );
        }

        /**
         * Output Sharing Buttons
         *
         * @param String $name - brand name.
         */
        public function get_e( $name ) {
            Brand_SVG_Please::get_e( $name, $this->svg_data );
        }

        /**
         * Get the SVG string for a given icon.
         *
         * @param String $name - brand name.
         *
         * @return String
         */
        public function get_name( $name ) {
            return Brand_SVG_Please::get_name( $name );
        }

        /**
         * Check if SVG icon exists.
         *
         * @param String $name - brand name.
         *
         * @return Boolean
         */
        public function exists( $name ) {
            return Brand_SVG_Please::exists( $name );
        }

        /**
         * Get all available brands.
         *
         * @param Boolean $get_svg - get SVG and insert it inside array.
         *
         * @return Array
         */
        public function get_all_brands( $get_svg = false ) {
            return Brand_SVG_Please::get_all_brands( $get_svg, $this->svg_data );
        }
    }
endif;
