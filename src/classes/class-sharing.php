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
            // add action to show sharing buttons template.
            add_action( 'sociality-sharing', array( $this, 'sharing_custom_action' ) );

            // add filter to show sharing buttons before or after content.
            add_filter( 'the_content', array( $this, 'sharing_content' ) );

            // add shortcode.
            add_shortcode( 'sociality_sharing', array( $this, 'sharing_shortcode' ) );
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
            $place = sociality()->settings()->get_option( 'place', 'sociality_sharing', null );

            if ( is_array( $place ) && isset( $place['before_content'] ) ) {
                $content = $this->print_sharing() . $content;
            }
            if ( is_array( $place ) && isset( $place['after_content'] ) ) {
                $content .= $this->print_sharing();
            }

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
