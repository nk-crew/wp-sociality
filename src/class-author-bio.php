<?php
/**
 * Posts Author BIO Block
 */
if (!class_exists( 'Sociality_Author_Bio' )) :
    class Sociality_Author_Bio {
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
            add_action('sociality-author-bio', array($this, 'bio_custom_action'));

            // add filter to show bio before or after content
            add_filter('the_content', array($this, 'bio_content'));

            // add shortcode
            add_shortcode('sociality_author_bio', array($this, 'bio_shortcode'));

            // add admin author settings
            add_action( 'show_user_profile', array($this, 'print_admin_author_settings') );
            add_action( 'edit_user_profile', array($this, 'print_admin_author_settings') );

            // save admin author settings
            add_action( 'personal_options_update', array($this, 'save_admin_author_settings') );
            add_action( 'edit_user_profile_update', array($this, 'save_admin_author_settings') );
        }

        // bio custom action
        public function bio_custom_action () {
            $place = sociality()->settings()->get_option('place','sociality_author_bio',null);
            if (is_array($place) && isset($place['custom_action']) || $place === null) {
               echo $this->print_author_bio();
            }
        }

        // bio before/after content
        public function bio_content ($content) {
            $place = sociality()->settings()->get_option('place','sociality_author_bio',null);

            if (is_array($place) && isset($place['before_content'])) {
                $content = $this->print_author_bio() . $content;
            }
            if (is_array($place) && isset($place['after_content'])) {
                $content .= $this->print_author_bio();
            }

            return $content;
        }

        // bio shortcode
        public function bio_shortcode () {
            return $this->print_author_bio();
        }

        /**
         * Print Author Bio
         */
        public function print_author_bio () {
            $output = '';

            if (is_single()) {
                ob_start();
                sociality()->include_template('post-author-bio.php');
                $output = ob_get_contents();
                ob_end_clean();
            }

            return $output;
        }

        public function admin_author_settings_enqueue_assets () {
            // css
            wp_enqueue_style('bootstrap-custom', plugins_url( 'assets/bootstrap/css/bootstrap-custom.css', __FILE__ ), false);
            wp_enqueue_style('socicon', plugins_url( 'assets/socicon/style.css', __FILE__ ), false);
            wp_enqueue_style('fontawesome-iconpicker', plugins_url( 'assets/iconpicker/css/fontawesome-iconpicker.min.css', __FILE__ ), false);
            wp_enqueue_style('sociality-admin', plugins_url( 'assets/sociality-admin.css', __FILE__ ), false);

            // js
            wp_enqueue_script('bootstrap', plugins_url( 'assets/bootstrap/js/bootstrap.min.js', __FILE__ ), array('jquery'), '', true);
            wp_enqueue_script('fontawesome-iconpicker', plugins_url( 'assets/iconpicker/js/fontawesome-iconpicker.min.js', __FILE__ ), array('bootstrap'), '', true);
            wp_enqueue_script('sociality-admin', plugins_url( 'assets/sociality-admin.js', __FILE__ ), array('jquery'), '', true);


            wp_localize_script('sociality-admin', 'socialityAdmin', array(
                'icons' => sociality()->get_icons_array()
            ));
        }

        /**
         * Extra settings in user profile
         */
        public function print_admin_author_settings ($user) {
            $this->admin_author_settings_enqueue_assets();

            $user_social_links = get_the_author_meta('user_sociality_links', $user->ID);
            ?>
                <h3>Social Links</h3>
                <table class="form-table">
                    <tr>
                        <th></th>

                        <td class="sociality-icon-picker">
                            <?php if (is_array($user_social_links)) : ?>
                                <?php foreach($user_social_links as $k => $val) { ?>
                                    <div class="input-group">
                                        <span class="btn btn-default sociality-icp iconpicker-component input-group-btn">
                                            <i class="<?php echo esc_attr($val['icon'] ? $val['icon'] : ''); ?>"><?php echo ($val['icon'] ? '' : 'Icon'); ?></i>
                                            <input type="hidden" class="iconpicker-input" name="user_sociality_links[<?php echo esc_attr($k); ?>][icon]" value="<?php echo esc_attr($val['icon'] ? $val['icon'] : ''); ?>">
                                        </span>
                                        <input class="form-control" value="<?php echo esc_attr(isset($val['url']) ? $val['url'] : ''); ?>" type="url" placeholder="https://..." name="user_sociality_links[<?php echo esc_attr($k); ?>][url]">
                                        <span class="btn btn-danger input-group-btn sociality-icon-picker-remove">
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

        // save settings
        public function save_admin_author_settings ($user_id) {
            if ( !current_user_can( 'edit_user', $user_id ) ) {
                return;
            }
            update_user_meta($user_id, 'user_sociality_links', $_POST['user_sociality_links']);
        }
    }
endif;