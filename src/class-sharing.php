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
            // add action to show sharing buttons template
            add_action('sociality-sharing', array($this, 'custom_action'));
        }

        // sharing buttons custom action
        public function custom_action () {
	        echo $this->print_sharing();
        }

        /**
         * Print Sharing Buttons
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