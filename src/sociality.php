<?php
/**
 * Plugin Name:  Sociality
 * Description:  Social links for posts authors
 * Version:      1.0.2
 * Author:       nK
 * Author URI:   https://nkdev.info
 * License:      GPLv2 or later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  sociality
 */
define('NK_SOCIALITY_DOMAIN', 'sociality');

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}


/**
 * Sociality Class
 */
if (!class_exists( 'Sociality' )) :
class Sociality {
    /**
     * The single class instance.
     */
    private static $_instance = null;

    /**
    * Main Instance
    * Ensures only one instance of this class exists in memory at any one time.
    */
    public static function instance () {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
            self::$_instance->init_text_domain();
            self::$_instance->init_options();
            self::$_instance->init_hooks();

            // include helper files
            self::$_instance->include_dependencies();

            // clear caches
            self::$_instance->clear_expired_caches();

            // run some classes
            self::$_instance->settings();
            self::$_instance->author_bio();
            self::$_instance->likes();
            self::$_instance->sharing();
        }
        return self::$_instance;
    }

    public $plugin_path;
    public $plugin_url;
    public $plugin_name;
    public $plugin_version;
    public $plugin_slug;
    public $plugin_name_sanitized;

    public function __construct() {
        /* We do nothing here! */
    }

    public function init_text_domain () {
        load_plugin_textdomain( NK_SOCIALITY_DOMAIN, false, dirname( plugin_basename(__FILE__) ) . '/languages/' );
    }

    public function init_options() {
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);
    }

    public function init_hooks() {
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function admin_init () {
        // get current plugin data
        $data = get_plugin_data(__FILE__);
        $this->plugin_name = $data['Name'];
        $this->plugin_version = $data['Version'];
        $this->plugin_slug = plugin_basename(__FILE__, '.php');
        $this->plugin_name_sanitized = basename(__FILE__, '.php');

        // init updater
        $this->updater();
    }

    public function enqueue_assets() {
        wp_enqueue_style('socicon', plugins_url( 'assets/socicon/style.css', __FILE__ ), false);
        wp_enqueue_style('sociality', plugins_url( 'assets/sociality.css', __FILE__ ), false);

        wp_enqueue_script('sociality', plugins_url( 'assets/sociality.js', __FILE__ ), array('jquery'), '', true);
        wp_enqueue_script('sociality-share', plugins_url( 'assets/sociality-share/sociality-share.js', __FILE__ ), array('jquery'), '', true);

        wp_localize_script('sociality', 'socialityData', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'ajax_nonce'   => wp_create_nonce( 'ajax-nonce' )
        ));
    }

    // print template file (first check for theme /sociality-templates/...php)
    public function include_template ($template_name, $args = array()) {
        if (!empty($args) && is_array($args)) {
            extract($args);
        }

        // template in theme folder
        $template = locate_template(array('sociality/' . $template_name, $template_name));

        // template from plugins folder
        if (!$template) {
            $template = locate_template(array('plugins/sociality/' . $template_name, $template_name));
        }

        // default template
        if (!$template) {
            $template = $this->plugin_path . 'templates/' . $template_name;
        }

        // Allow 3rd party plugin filter template file from their plugin.
        $template = apply_filters('sociality_include_template', $template, $template_name, $args);

        include $template;
    }

    // include
    private function include_dependencies () {
        require_once($this->plugin_path . 'class-settings.php');
        require_once($this->plugin_path . 'class-updater.php');
        require_once($this->plugin_path . 'class-author-bio.php');
        require_once($this->plugin_path . 'class-likes.php');
        require_once($this->plugin_path . 'class-sharing.php');
    }


    /**
     * Additional Classes
     */
    public function updater () {
        return Sociality_Updater::instance();
    }
    public function settings () {
        return Sociality_Settings::instance();
    }
    public function author_bio () {
        return Sociality_Author_Bio::instance();
    }
    public function likes () {
        return Sociality_Likes::instance();
    }
    public function sharing () {
        return Sociality_Sharing::instance();
    }


    /**
     * Options
     */
    private function get_options () {
        $options_slug = 'sociality_options';
        return unserialize( get_option($options_slug, 'a:0:{}') );
    }
    public function update_option ($name, $value) {
        $options_slug = 'sociality_options';
        $options = self::get_options();
        $options[self::sanitize_key($name)] = $value;
        update_option($options_slug, serialize($options));
    }
    public function get_option ($name, $default = null) {
        $options = self::get_options();
        $name = self::sanitize_key($name);
        return isset($options[$name]) ? $options[$name] : $default;
    }
    private function sanitize_key ($key) {
        return preg_replace( '/[^A-Za-z0-9\_]/i', '', str_replace( array( '-', ':' ), '_', $key ) );
    }


    /**
     * Cache
     * $time in seconds
     */
    private function get_caches () {
        $caches_slug = 'cache';
        return $this->get_option($caches_slug, array());
    }
    public function set_cache ($name, $value, $time = 3600) {
        if(!$time || $time <= 0) {
            return;
        }
        $caches_slug = 'cache';
        $caches = self::get_caches();

        $caches[self::sanitize_key($name)] = array(
            'value'   => $value,
            'expired' => time() + ((int) $time ? $time : 0)
        );
        $this->update_option($caches_slug, $caches);
    }
    public function get_cache ($name, $default = null) {
        $caches = self::get_caches();
        $name = self::sanitize_key($name);
        return isset($caches[$name]['value']) ? $caches[$name]['value'] : $default;
    }
    public function clear_cache ($name) {
        $caches_slug = 'cache';
        $caches = self::get_caches();
        $name = self::sanitize_key($name);
        if(isset($caches[$name])) {
            $caches[$name] = null;
            $this->update_option($caches_slug, $caches);
        }
    }
    public function clear_expired_caches () {
        $caches_slug = 'cache';
        $caches = self::get_caches();
        foreach($caches as $k => $cache) {
            if(isset($cache) && isset($cache['expired']) && $cache['expired'] < time()) {
                $caches[$k] = null;
            }
        }
        $this->update_option($caches_slug, $caches);
    }


    /**
     * Get used icons
     */
    public function get_icons_array () {
        return apply_filters('sociality_icons_array', array(
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
        ));
    }
}
endif;

if (!function_exists( 'sociality' )) :
function sociality () {
    return Sociality::instance();
}
endif;
add_action('plugins_loaded', 'sociality' );
