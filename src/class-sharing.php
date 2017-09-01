<?php
/**
 * Sharing Block
 */
if (!class_exists( 'Sociality_Sharing' )) :
    class Sociality_Sharing {
        /**
         * The single class instance.
         */
        private static $_instance = null;

        /**
         * Main Instance
         * Ensures only one instance of this class exists in memory at any one time.
         */
        public static function instance () {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
                self::$_instance->init_actions();
            }
            return self::$_instance;
        }

        private function __construct () {
            /* We do nothing here! */
        }

        private function init_actions () {
            // add action to show author bio template
            add_action('sociality-sharing', array($this, 'custom_action'));
        }

        // bio custom action
        public function custom_action () {
            $place = sociality()->settings()->get_option('place','sociality_author_bio',null);
            if (is_array($place) && isset($place['custom_action']) || $place === null) {
                echo $this->print_sharing();
            }
        }

        /**
         * Print Buttons
         */
        public function print_sharing () {
            ob_start();
            sociality()->include_template('sharing-buttons.php');
            $output = ob_get_contents();
            ob_end_clean();

            return $output;
        }
    }
endif;