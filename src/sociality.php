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
            wp_enqueue_style( 'socicon', sociality()->plugin_url . 'assets/vendor/socicon/style.css', array(), '3.6.2' );
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
                    array(
                        'title'       => 'socicon-500px',
                        'searchTerms' => array( '500px' ),
                    ),
                    array(
                        'title'       => 'socicon-8tracks',
                        'searchTerms' => array( '8tracks' ),
                    ),
                    array(
                        'title'       => 'socicon-airbnb',
                        'searchTerms' => array( 'airbnb' ),
                    ),
                    array(
                        'title'       => 'socicon-alliance',
                        'searchTerms' => array( 'alliance' ),
                    ),
                    array(
                        'title'       => 'socicon-amazon',
                        'searchTerms' => array( 'amazon' ),
                    ),
                    array(
                        'title'       => 'socicon-amplement',
                        'searchTerms' => array( 'amplement' ),
                    ),
                    array(
                        'title'       => 'socicon-android',
                        'searchTerms' => array( 'android' ),
                    ),
                    array(
                        'title'       => 'socicon-angellist',
                        'searchTerms' => array( 'angellist' ),
                    ),
                    array(
                        'title'       => 'socicon-apple',
                        'searchTerms' => array( 'apple' ),
                    ),
                    array(
                        'title'       => 'socicon-appnet',
                        'searchTerms' => array( 'appnet' ),
                    ),
                    array(
                        'title'       => 'socicon-baidu',
                        'searchTerms' => array( 'baidu' ),
                    ),
                    array(
                        'title'       => 'socicon-bandcamp',
                        'searchTerms' => array( 'bandcamp' ),
                    ),
                    array(
                        'title'       => 'socicon-battlenet',
                        'searchTerms' => array( 'battlenet' ),
                    ),
                    array(
                        'title'       => 'socicon-mixer',
                        'searchTerms' => array( 'mixer' ),
                    ),
                    array(
                        'title'       => 'socicon-bebee',
                        'searchTerms' => array( 'bebee' ),
                    ),
                    array(
                        'title'       => 'socicon-bebo',
                        'searchTerms' => array( 'bebo' ),
                    ),
                    array(
                        'title'       => 'socicon-behance',
                        'searchTerms' => array( 'behance' ),
                    ),
                    array(
                        'title'       => 'socicon-blizzard',
                        'searchTerms' => array( 'blizzard' ),
                    ),
                    array(
                        'title'       => 'socicon-blogger',
                        'searchTerms' => array( 'blogger' ),
                    ),
                    array(
                        'title'       => 'socicon-buffer',
                        'searchTerms' => array( 'buffer' ),
                    ),
                    array(
                        'title'       => 'socicon-chrome',
                        'searchTerms' => array( 'chrome' ),
                    ),
                    array(
                        'title'       => 'socicon-coderwall',
                        'searchTerms' => array( 'coderwall' ),
                    ),
                    array(
                        'title'       => 'socicon-curse',
                        'searchTerms' => array( 'curse' ),
                    ),
                    array(
                        'title'       => 'socicon-dailymotion',
                        'searchTerms' => array( 'dailymotion' ),
                    ),
                    array(
                        'title'       => 'socicon-deezer',
                        'searchTerms' => array( 'deezer' ),
                    ),
                    array(
                        'title'       => 'socicon-delicious',
                        'searchTerms' => array( 'delicious' ),
                    ),
                    array(
                        'title'       => 'socicon-deviantart',
                        'searchTerms' => array( 'deviantart' ),
                    ),
                    array(
                        'title'       => 'socicon-diablo',
                        'searchTerms' => array( 'diablo' ),
                    ),
                    array(
                        'title'       => 'socicon-digg',
                        'searchTerms' => array( 'digg' ),
                    ),
                    array(
                        'title'       => 'socicon-discord',
                        'searchTerms' => array( 'discord' ),
                    ),
                    array(
                        'title'       => 'socicon-disqus',
                        'searchTerms' => array( 'disqus' ),
                    ),
                    array(
                        'title'       => 'socicon-douban',
                        'searchTerms' => array( 'douban' ),
                    ),
                    array(
                        'title'       => 'socicon-draugiem',
                        'searchTerms' => array( 'draugiem' ),
                    ),
                    array(
                        'title'       => 'socicon-dribbble',
                        'searchTerms' => array( 'dribbble' ),
                    ),
                    array(
                        'title'       => 'socicon-drupal',
                        'searchTerms' => array( 'drupal' ),
                    ),
                    array(
                        'title'       => 'socicon-ebay',
                        'searchTerms' => array( 'ebay' ),
                    ),
                    array(
                        'title'       => 'socicon-ello',
                        'searchTerms' => array( 'ello' ),
                    ),
                    array(
                        'title'       => 'socicon-endomondo',
                        'searchTerms' => array( 'endomondo' ),
                    ),
                    array(
                        'title'       => 'socicon-envato',
                        'searchTerms' => array( 'envato' ),
                    ),
                    array(
                        'title'       => 'socicon-etsy',
                        'searchTerms' => array( 'etsy' ),
                    ),
                    array(
                        'title'       => 'socicon-facebook',
                        'searchTerms' => array( 'facebook' ),
                    ),
                    array(
                        'title'       => 'socicon-feedburner',
                        'searchTerms' => array( 'feedburner' ),
                    ),
                    array(
                        'title'       => 'socicon-filmweb',
                        'searchTerms' => array( 'filmweb' ),
                    ),
                    array(
                        'title'       => 'socicon-firefox',
                        'searchTerms' => array( 'firefox' ),
                    ),
                    array(
                        'title'       => 'socicon-flattr',
                        'searchTerms' => array( 'flattr' ),
                    ),
                    array(
                        'title'       => 'socicon-flickr',
                        'searchTerms' => array( 'flickr' ),
                    ),
                    array(
                        'title'       => 'socicon-formulr',
                        'searchTerms' => array( 'formulr' ),
                    ),
                    array(
                        'title'       => 'socicon-forrst',
                        'searchTerms' => array( 'forrst' ),
                    ),
                    array(
                        'title'       => 'socicon-foursquare',
                        'searchTerms' => array( 'foursquare' ),
                    ),
                    array(
                        'title'       => 'socicon-friendfeed',
                        'searchTerms' => array( 'friendfeed' ),
                    ),
                    array(
                        'title'       => 'socicon-github',
                        'searchTerms' => array( 'github' ),
                    ),
                    array(
                        'title'       => 'socicon-goodreads',
                        'searchTerms' => array( 'goodreads' ),
                    ),
                    array(
                        'title'       => 'socicon-google',
                        'searchTerms' => array( 'google' ),
                    ),
                    array(
                        'title'       => 'socicon-googlegroups',
                        'searchTerms' => array( 'googlegroups' ),
                    ),
                    array(
                        'title'       => 'socicon-googlephotos',
                        'searchTerms' => array( 'googlephotos' ),
                    ),
                    array(
                        'title'       => 'socicon-googleplus',
                        'searchTerms' => array( 'googleplus' ),
                    ),
                    array(
                        'title'       => 'socicon-googlescholar',
                        'searchTerms' => array( 'googlescholar' ),
                    ),
                    array(
                        'title'       => 'socicon-grooveshark',
                        'searchTerms' => array( 'grooveshark' ),
                    ),
                    array(
                        'title'       => 'socicon-hackerrank',
                        'searchTerms' => array( 'hackerrank' ),
                    ),
                    array(
                        'title'       => 'socicon-hearthstone',
                        'searchTerms' => array( 'hearthstone' ),
                    ),
                    array(
                        'title'       => 'socicon-hellocoton',
                        'searchTerms' => array( 'hellocoton' ),
                    ),
                    array(
                        'title'       => 'socicon-heroes',
                        'searchTerms' => array( 'heroes' ),
                    ),
                    array(
                        'title'       => 'socicon-hitbox',
                        'searchTerms' => array( 'hitbox' ),
                    ),
                    array(
                        'title'       => 'socicon-horde',
                        'searchTerms' => array( 'horde' ),
                    ),
                    array(
                        'title'       => 'socicon-houzz',
                        'searchTerms' => array( 'houzz' ),
                    ),
                    array(
                        'title'       => 'socicon-icq',
                        'searchTerms' => array( 'icq' ),
                    ),
                    array(
                        'title'       => 'socicon-identica',
                        'searchTerms' => array( 'identica' ),
                    ),
                    array(
                        'title'       => 'socicon-imdb',
                        'searchTerms' => array( 'imdb' ),
                    ),
                    array(
                        'title'       => 'socicon-instagram',
                        'searchTerms' => array( 'instagram' ),
                    ),
                    array(
                        'title'       => 'socicon-issuu',
                        'searchTerms' => array( 'issuu' ),
                    ),
                    array(
                        'title'       => 'socicon-istock',
                        'searchTerms' => array( 'istock' ),
                    ),
                    array(
                        'title'       => 'socicon-itunes',
                        'searchTerms' => array( 'itunes' ),
                    ),
                    array(
                        'title'       => 'socicon-keybase',
                        'searchTerms' => array( 'keybase' ),
                    ),
                    array(
                        'title'       => 'socicon-lanyrd',
                        'searchTerms' => array( 'lanyrd' ),
                    ),
                    array(
                        'title'       => 'socicon-lastfm',
                        'searchTerms' => array( 'lastfm' ),
                    ),
                    array(
                        'title'       => 'socicon-line',
                        'searchTerms' => array( 'line' ),
                    ),
                    array(
                        'title'       => 'socicon-linkedin',
                        'searchTerms' => array( 'linkedin' ),
                    ),
                    array(
                        'title'       => 'socicon-livejournal',
                        'searchTerms' => array( 'livejournal' ),
                    ),
                    array(
                        'title'       => 'socicon-lyft',
                        'searchTerms' => array( 'lyft' ),
                    ),
                    array(
                        'title'       => 'socicon-macos',
                        'searchTerms' => array( 'macos' ),
                    ),
                    array(
                        'title'       => 'socicon-mail',
                        'searchTerms' => array( 'mail' ),
                    ),
                    array(
                        'title'       => 'socicon-medium',
                        'searchTerms' => array( 'medium' ),
                    ),
                    array(
                        'title'       => 'socicon-meetup',
                        'searchTerms' => array( 'meetup' ),
                    ),
                    array(
                        'title'       => 'socicon-mixcloud',
                        'searchTerms' => array( 'mixcloud' ),
                    ),
                    array(
                        'title'       => 'socicon-modelmayhem',
                        'searchTerms' => array( 'modelmayhem' ),
                    ),
                    array(
                        'title'       => 'socicon-mumble',
                        'searchTerms' => array( 'mumble' ),
                    ),
                    array(
                        'title'       => 'socicon-myspace',
                        'searchTerms' => array( 'myspace' ),
                    ),
                    array(
                        'title'       => 'socicon-newsvine',
                        'searchTerms' => array( 'newsvine' ),
                    ),
                    array(
                        'title'       => 'socicon-nintendo',
                        'searchTerms' => array( 'nintendo' ),
                    ),
                    array(
                        'title'       => 'socicon-npm',
                        'searchTerms' => array( 'npm' ),
                    ),
                    array(
                        'title'       => 'socicon-odnoklassniki',
                        'searchTerms' => array( 'odnoklassniki' ),
                    ),
                    array(
                        'title'       => 'socicon-openid',
                        'searchTerms' => array( 'openid' ),
                    ),
                    array(
                        'title'       => 'socicon-opera',
                        'searchTerms' => array( 'opera' ),
                    ),
                    array(
                        'title'       => 'socicon-outlook',
                        'searchTerms' => array( 'outlook' ),
                    ),
                    array(
                        'title'       => 'socicon-overwatch',
                        'searchTerms' => array( 'overwatch' ),
                    ),
                    array(
                        'title'       => 'socicon-patreon',
                        'searchTerms' => array( 'patreon' ),
                    ),
                    array(
                        'title'       => 'socicon-paypal',
                        'searchTerms' => array( 'paypal' ),
                    ),
                    array(
                        'title'       => 'socicon-periscope',
                        'searchTerms' => array( 'periscope' ),
                    ),
                    array(
                        'title'       => 'socicon-persona',
                        'searchTerms' => array( 'persona' ),
                    ),
                    array(
                        'title'       => 'socicon-pinterest',
                        'searchTerms' => array( 'pinterest' ),
                    ),
                    array(
                        'title'       => 'socicon-play',
                        'searchTerms' => array( 'play' ),
                    ),
                    array(
                        'title'       => 'socicon-player',
                        'searchTerms' => array( 'player' ),
                    ),
                    array(
                        'title'       => 'socicon-playstation',
                        'searchTerms' => array( 'playstation' ),
                    ),
                    array(
                        'title'       => 'socicon-pocket',
                        'searchTerms' => array( 'pocket' ),
                    ),
                    array(
                        'title'       => 'socicon-qq',
                        'searchTerms' => array( 'qq' ),
                    ),
                    array(
                        'title'       => 'socicon-quora',
                        'searchTerms' => array( 'quora' ),
                    ),
                    array(
                        'title'       => 'socicon-raidcall',
                        'searchTerms' => array( 'raidcall' ),
                    ),
                    array(
                        'title'       => 'socicon-ravelry',
                        'searchTerms' => array( 'ravelry' ),
                    ),
                    array(
                        'title'       => 'socicon-reddit',
                        'searchTerms' => array( 'reddit' ),
                    ),
                    array(
                        'title'       => 'socicon-renren',
                        'searchTerms' => array( 'renren' ),
                    ),
                    array(
                        'title'       => 'socicon-researchgate',
                        'searchTerms' => array( 'researchgate' ),
                    ),
                    array(
                        'title'       => 'socicon-residentadvisor',
                        'searchTerms' => array( 'residentadvisor' ),
                    ),
                    array(
                        'title'       => 'socicon-reverbnation',
                        'searchTerms' => array( 'reverbnation' ),
                    ),
                    array(
                        'title'       => 'socicon-rss',
                        'searchTerms' => array( 'rss' ),
                    ),
                    array(
                        'title'       => 'socicon-sharethis',
                        'searchTerms' => array( 'sharethis' ),
                    ),
                    array(
                        'title'       => 'socicon-skype',
                        'searchTerms' => array( 'skype' ),
                    ),
                    array(
                        'title'       => 'socicon-slideshare',
                        'searchTerms' => array( 'slideshare' ),
                    ),
                    array(
                        'title'       => 'socicon-smugmug',
                        'searchTerms' => array( 'smugmug' ),
                    ),
                    array(
                        'title'       => 'socicon-snapchat',
                        'searchTerms' => array( 'snapchat' ),
                    ),
                    array(
                        'title'       => 'socicon-songkick',
                        'searchTerms' => array( 'songkick' ),
                    ),
                    array(
                        'title'       => 'socicon-soundcloud',
                        'searchTerms' => array( 'soundcloud' ),
                    ),
                    array(
                        'title'       => 'socicon-spotify',
                        'searchTerms' => array( 'spotify' ),
                    ),
                    array(
                        'title'       => 'socicon-stackexchange',
                        'searchTerms' => array( 'stackexchange' ),
                    ),
                    array(
                        'title'       => 'socicon-stackoverflow',
                        'searchTerms' => array( 'stackoverflow' ),
                    ),
                    array(
                        'title'       => 'socicon-starcraft',
                        'searchTerms' => array( 'starcraft' ),
                    ),
                    array(
                        'title'       => 'socicon-stayfriends',
                        'searchTerms' => array( 'stayfriends' ),
                    ),
                    array(
                        'title'       => 'socicon-steam',
                        'searchTerms' => array( 'steam' ),
                    ),
                    array(
                        'title'       => 'socicon-storehouse',
                        'searchTerms' => array( 'storehouse' ),
                    ),
                    array(
                        'title'       => 'socicon-strava',
                        'searchTerms' => array( 'strava' ),
                    ),
                    array(
                        'title'       => 'socicon-streamjar',
                        'searchTerms' => array( 'streamjar' ),
                    ),
                    array(
                        'title'       => 'socicon-stumbleupon',
                        'searchTerms' => array( 'stumbleupon' ),
                    ),
                    array(
                        'title'       => 'socicon-swarm',
                        'searchTerms' => array( 'swarm' ),
                    ),
                    array(
                        'title'       => 'socicon-teamspeak',
                        'searchTerms' => array( 'teamspeak' ),
                    ),
                    array(
                        'title'       => 'socicon-teamviewer',
                        'searchTerms' => array( 'teamviewer' ),
                    ),
                    array(
                        'title'       => 'socicon-technorati',
                        'searchTerms' => array( 'technorati' ),
                    ),
                    array(
                        'title'       => 'socicon-telegram',
                        'searchTerms' => array( 'telegram' ),
                    ),
                    array(
                        'title'       => 'socicon-tripadvisor',
                        'searchTerms' => array( 'tripadvisor' ),
                    ),
                    array(
                        'title'       => 'socicon-tripit',
                        'searchTerms' => array( 'tripit' ),
                    ),
                    array(
                        'title'       => 'socicon-triplej',
                        'searchTerms' => array( 'triplej' ),
                    ),
                    array(
                        'title'       => 'socicon-tumblr',
                        'searchTerms' => array( 'tumblr' ),
                    ),
                    array(
                        'title'       => 'socicon-twitch',
                        'searchTerms' => array( 'twitch' ),
                    ),
                    array(
                        'title'       => 'socicon-twitter',
                        'searchTerms' => array( 'twitter' ),
                    ),
                    array(
                        'title'       => 'socicon-uber',
                        'searchTerms' => array( 'uber' ),
                    ),
                    array(
                        'title'       => 'socicon-ventrilo',
                        'searchTerms' => array( 'ventrilo' ),
                    ),
                    array(
                        'title'       => 'socicon-viadeo',
                        'searchTerms' => array( 'viadeo' ),
                    ),
                    array(
                        'title'       => 'socicon-viber',
                        'searchTerms' => array( 'viber' ),
                    ),
                    array(
                        'title'       => 'socicon-viewbug',
                        'searchTerms' => array( 'viewbug' ),
                    ),
                    array(
                        'title'       => 'socicon-vimeo',
                        'searchTerms' => array( 'vimeo' ),
                    ),
                    array(
                        'title'       => 'socicon-vine',
                        'searchTerms' => array( 'vine' ),
                    ),
                    array(
                        'title'       => 'socicon-vkontakte',
                        'searchTerms' => array( 'vkontakte' ),
                    ),
                    array(
                        'title'       => 'socicon-warcraft',
                        'searchTerms' => array( 'warcraft' ),
                    ),
                    array(
                        'title'       => 'socicon-wechat',
                        'searchTerms' => array( 'wechat' ),
                    ),
                    array(
                        'title'       => 'socicon-weibo',
                        'searchTerms' => array( 'weibo' ),
                    ),
                    array(
                        'title'       => 'socicon-whatsapp',
                        'searchTerms' => array( 'whatsapp' ),
                    ),
                    array(
                        'title'       => 'socicon-wikipedia',
                        'searchTerms' => array( 'wikipedia' ),
                    ),
                    array(
                        'title'       => 'socicon-windows',
                        'searchTerms' => array( 'windows' ),
                    ),
                    array(
                        'title'       => 'socicon-wordpress',
                        'searchTerms' => array( 'wordpress' ),
                    ),
                    array(
                        'title'       => 'socicon-wykop',
                        'searchTerms' => array( 'wykop' ),
                    ),
                    array(
                        'title'       => 'socicon-xbox',
                        'searchTerms' => array( 'xbox' ),
                    ),
                    array(
                        'title'       => 'socicon-xing',
                        'searchTerms' => array( 'xing' ),
                    ),
                    array(
                        'title'       => 'socicon-yahoo',
                        'searchTerms' => array( 'yahoo' ),
                    ),
                    array(
                        'title'       => 'socicon-yammer',
                        'searchTerms' => array( 'yammer' ),
                    ),
                    array(
                        'title'       => 'socicon-yandex',
                        'searchTerms' => array( 'yandex' ),
                    ),
                    array(
                        'title'       => 'socicon-yelp',
                        'searchTerms' => array( 'yelp' ),
                    ),
                    array(
                        'title'       => 'socicon-younow',
                        'searchTerms' => array( 'younow' ),
                    ),
                    array(
                        'title'       => 'socicon-youtube',
                        'searchTerms' => array( 'youtube' ),
                    ),
                    array(
                        'title'       => 'socicon-zapier',
                        'searchTerms' => array( 'zapier' ),
                    ),
                    array(
                        'title'       => 'socicon-zerply',
                        'searchTerms' => array( 'zerply' ),
                    ),
                    array(
                        'title'       => 'socicon-zomato',
                        'searchTerms' => array( 'zomato' ),
                    ),
                    array(
                        'title'       => 'socicon-zynga',
                        'searchTerms' => array( 'zynga' ),
                    ),
                    array(
                        'title'       => 'socicon-spreadshirt',
                        'searchTerms' => array( 'spreadshirt' ),
                    ),
                    array(
                        'title'       => 'socicon-trello',
                        'searchTerms' => array( 'trello' ),
                    ),
                    array(
                        'title'       => 'socicon-gamejolt',
                        'searchTerms' => array( 'gamejolt' ),
                    ),
                    array(
                        'title'       => 'socicon-tunein',
                        'searchTerms' => array( 'tunein' ),
                    ),
                    array(
                        'title'       => 'socicon-bloglovin',
                        'searchTerms' => array( 'bloglovin' ),
                    ),
                    array(
                        'title'       => 'socicon-gamewisp',
                        'searchTerms' => array( 'gamewisp' ),
                    ),
                    array(
                        'title'       => 'socicon-messenger',
                        'searchTerms' => array( 'messenger' ),
                    ),
                    array(
                        'title'       => 'socicon-pandora',
                        'searchTerms' => array( 'pandora' ),
                    ),

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
