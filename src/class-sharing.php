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
            add_action('sociality-sharing', array($this, 'sharing_custom_action'));

	        // add filter to show sharing buttons before or after content
	        add_filter('the_content', array($this, 'sharing_content'));

	        // add shortcode
	        add_shortcode('sociality_sharing', array($this, 'sharing_shortcode'));
        }

        // sharing buttons custom action
        public function sharing_custom_action () {
	        $place = sociality()->settings()->get_option('place','sociality_sharing',null);
	        if (is_array($place) && isset($place['custom_action']) || $place === null) {
		        echo $this->print_sharing();
	        }
        }

	    // sharing before/after content
	    public function sharing_content ($content) {
		    $place = sociality()->settings()->get_option('place','sociality_sharing',null);

		    if (is_array($place) && isset($place['before_content'])) {
			    $content = $this->print_sharing() . $content;
		    }
		    if (is_array($place) && isset($place['after_content'])) {
			    $content .= $this->print_sharing();
		    }

		    return $content;
	    }

	    // sharing shortcode
	    public function sharing_shortcode () {
		    return $this->print_sharing();
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