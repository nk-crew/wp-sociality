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
            wp_enqueue_style( 'socicon', plugins_url( 'assets/vendor/socicon/style.css', __FILE__ ), array(), '3.6.2' );
            wp_enqueue_style( 'sociality', plugins_url( 'assets/sociality.min.css', __FILE__ ), array(), '@@plugin_version' );

            wp_enqueue_script( 'sociality', plugins_url( 'assets/sociality.min.js', __FILE__ ), array( 'jquery' ), '@@plugin_version', true );
            wp_enqueue_script( 'sociality-share', plugins_url( 'assets/sociality-share/sociality-share.min.js', __FILE__ ), array( 'jquery' ), '@@plugin_version', true );

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
            return apply_filters(
                'sociality_icons_array',
                array(
                    'socicon-500px',
                    'socicon-8tracks',
                    'socicon-airbnb',
                    'socicon-alliance',
                    'socicon-amazon',
                    'socicon-amplement',
                    'socicon-android',
                    'socicon-angellist',
                    'socicon-apple',
                    'socicon-appnet',
                    'socicon-baidu',
                    'socicon-bandcamp',
                    'socicon-battlenet',
                    'socicon-mixer',
                    'socicon-bebee',
                    'socicon-bebo',
                    'socicon-behance',
                    'socicon-blizzard',
                    'socicon-blogger',
                    'socicon-buffer',
                    'socicon-chrome',
                    'socicon-coderwall',
                    'socicon-curse',
                    'socicon-dailymotion',
                    'socicon-deezer',
                    'socicon-delicious',
                    'socicon-deviantart',
                    'socicon-diablo',
                    'socicon-digg',
                    'socicon-discord',
                    'socicon-disqus',
                    'socicon-douban',
                    'socicon-draugiem',
                    'socicon-dribbble',
                    'socicon-drupal',
                    'socicon-ebay',
                    'socicon-ello',
                    'socicon-endomondo',
                    'socicon-envato',
                    'socicon-etsy',
                    'socicon-facebook',
                    'socicon-feedburner',
                    'socicon-filmweb',
                    'socicon-firefox',
                    'socicon-flattr',
                    'socicon-flickr',
                    'socicon-formulr',
                    'socicon-forrst',
                    'socicon-foursquare',
                    'socicon-friendfeed',
                    'socicon-github',
                    'socicon-goodreads',
                    'socicon-google',
                    'socicon-googlegroups',
                    'socicon-googlephotos',
                    'socicon-googleplus',
                    'socicon-googlescholar',
                    'socicon-grooveshark',
                    'socicon-hackerrank',
                    'socicon-hearthstone',
                    'socicon-hellocoton',
                    'socicon-heroes',
                    'socicon-hitbox',
                    'socicon-horde',
                    'socicon-houzz',
                    'socicon-icq',
                    'socicon-identica',
                    'socicon-imdb',
                    'socicon-instagram',
                    'socicon-issuu',
                    'socicon-istock',
                    'socicon-itunes',
                    'socicon-keybase',
                    'socicon-lanyrd',
                    'socicon-lastfm',
                    'socicon-line',
                    'socicon-linkedin',
                    'socicon-livejournal',
                    'socicon-lyft',
                    'socicon-macos',
                    'socicon-mail',
                    'socicon-medium',
                    'socicon-meetup',
                    'socicon-mixcloud',
                    'socicon-modelmayhem',
                    'socicon-mumble',
                    'socicon-myspace',
                    'socicon-newsvine',
                    'socicon-nintendo',
                    'socicon-npm',
                    'socicon-odnoklassniki',
                    'socicon-openid',
                    'socicon-opera',
                    'socicon-outlook',
                    'socicon-overwatch',
                    'socicon-patreon',
                    'socicon-paypal',
                    'socicon-periscope',
                    'socicon-persona',
                    'socicon-pinterest',
                    'socicon-play',
                    'socicon-player',
                    'socicon-playstation',
                    'socicon-pocket',
                    'socicon-qq',
                    'socicon-quora',
                    'socicon-raidcall',
                    'socicon-ravelry',
                    'socicon-reddit',
                    'socicon-renren',
                    'socicon-researchgate',
                    'socicon-residentadvisor',
                    'socicon-reverbnation',
                    'socicon-rss',
                    'socicon-sharethis',
                    'socicon-skype',
                    'socicon-slideshare',
                    'socicon-smugmug',
                    'socicon-snapchat',
                    'socicon-songkick',
                    'socicon-soundcloud',
                    'socicon-spotify',
                    'socicon-stackexchange',
                    'socicon-stackoverflow',
                    'socicon-starcraft',
                    'socicon-stayfriends',
                    'socicon-steam',
                    'socicon-storehouse',
                    'socicon-strava',
                    'socicon-streamjar',
                    'socicon-stumbleupon',
                    'socicon-swarm',
                    'socicon-teamspeak',
                    'socicon-teamviewer',
                    'socicon-technorati',
                    'socicon-telegram',
                    'socicon-tripadvisor',
                    'socicon-tripit',
                    'socicon-triplej',
                    'socicon-tumblr',
                    'socicon-twitch',
                    'socicon-twitter',
                    'socicon-uber',
                    'socicon-ventrilo',
                    'socicon-viadeo',
                    'socicon-viber',
                    'socicon-viewbug',
                    'socicon-vimeo',
                    'socicon-vine',
                    'socicon-vkontakte',
                    'socicon-warcraft',
                    'socicon-wechat',
                    'socicon-weibo',
                    'socicon-whatsapp',
                    'socicon-wikipedia',
                    'socicon-windows',
                    'socicon-wordpress',
                    'socicon-wykop',
                    'socicon-xbox',
                    'socicon-xing',
                    'socicon-yahoo',
                    'socicon-yammer',
                    'socicon-yandex',
                    'socicon-yelp',
                    'socicon-younow',
                    'socicon-youtube',
                    'socicon-zapier',
                    'socicon-zerply',
                    'socicon-zomato',
                    'socicon-zynga',
                    'socicon-spreadshirt',
                    'socicon-trello',
                    'socicon-gamejolt',
                    'socicon-tunein',
                    'socicon-bloglovin',
                    'socicon-gamewisp',
                    'socicon-messenger',
                    'socicon-pandora',
                )
            );
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
