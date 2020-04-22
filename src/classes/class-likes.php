<?php
/**
 * Likes Buttons.
 *
 * @package sociality
 */

if ( ! class_exists( 'Sociality_Likes' ) ) :
    /**
     * Sociality_Likes Class
     */
    class Sociality_Likes {
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
         * Sociality_Likes constructor.
         */
        private function __construct() {
            /* We do nothing here! */
        }

        /**
         * Init actions.
         */
        private function init_actions() {
            // add action to show likes.
            add_action( 'sociality_likes', array( $this, 'get_likes_action' ), 10, 2 );
            add_action( 'sociality-likes', array( $this, 'get_likes_action' ), 10, 2 ); // fallback.

            // like/dislike ajax actions.
            add_action( 'wp_ajax_nopriv_sociality-like-action', array( $this, 'ajax_post_like_action' ), 99 );
            add_action( 'wp_ajax_sociality-like-action', array( $this, 'ajax_post_like_action' ), 100 );
        }

        /**
         * Get Likes Buttons in Action.
         *
         * Usage:
         *   // Posts
         *   do_action('sociality_likes', get_the_ID(), 'post');
         *
         *   // Pages
         *   do_action('sociality_likes', get_the_ID(), 'page');
         *
         *   // Comments
         *   do_action('sociality_likes', get_comment_ID(), 'comment');
         *
         *   // WooCommerce Products
         *   do_action('sociality_likes', get_comment_ID(), 'wc_product');
         *
         *   // WooCommerce Reviews
         *   do_action('sociality_likes', get_comment_ID(), 'wc_review');
         *
         *   // bbPress
         *   do_action('sociality_likes', bbp_get_topic_id(), 'bb_topic');
         *   do_action('sociality_likes', bbp_get_reply_id(), 'bb_reply');
         *
         *   // BuddyPress
         *   do_action('sociality_likes', bp_get_activity_comment_id(), 'bp_activity');
         *   do_action('sociality_likes', bp_get_activity_id(), 'bp_activity');
         *
         *   // Custom Post Type Portfolio
         *   do_action('sociality_likes', get_the_ID(), 'portfolio');
         *
         * @param int    $post_id - post ID.
         * @param string $post_type - post type.
         */
        public function get_likes_action( $post_id = false, $post_type = false ) {
            // phpcs:ignore
            echo $this->get_likes( $post_id, $post_type );
        }

        /**
         * Get likes Buttons.
         *
         * @param int    $post_id - post ID.
         * @param string $post_type - post type.
         *
         * @return string
         */
        public function get_likes( $post_id = false, $post_type = false ) {
            $result = '';

            if ( ! $post_id || ! $post_type ) {
                return $result;
            }

            $attributes = array(
                'post_type'   => $post_type,
                'post_id'     => $post_id,
                'like_type'   => $this->get_like_type( $post_type ),
                'likes_count' => (int) $this->get_post_meta( $post_id, $post_type, '_likes_count' ),
                'liked'       => $this->is_user_post_liked( $post_id, $post_type ),
            );

            if ( 'heart' !== $attributes['like_type'] && 'thumbs' !== $attributes['like_type'] ) {
                return $result;
            }

            $result .= '<span
                    data-sociality-like="' . esc_attr( $attributes['like_type'] ) . '"
                    data-post-id="' . esc_attr( $attributes['post_id'] ) . '"
                    data-post-type="' . esc_attr( $attributes['post_type'] ) . '"
                    data-post-likes-count="' . esc_attr( $attributes['likes_count'] ) . '"
                    data-post-liked="' . esc_attr( $attributes['liked'] ) . '"
                    >';
            if ( 'heart' === $attributes['like_type'] ) {
                $result .= $this->get_heart_button( $attributes );
            } else {
                $result .= $this->get_thumbs_button( $attributes );
            }
            $result .= '</span>';

            return $result;
        }

        /**
         * Get Like Button
         *
         * @param array $attributes - attributes for template.
         *
         * @return string
         */
        public function get_heart_button( $attributes = array() ) {
            ob_start();
            sociality()->include_template( 'post-like-heart.php', $attributes );
            return ob_get_clean();
        }

        /**
         * Get Thumbs Buttons
         *
         * @param array $attributes - attributes for template.
         *
         * @return string
         */
        public function get_thumbs_button( $attributes = array() ) {
            ob_start();
            sociality()->include_template( 'post-like-thumbs.php', $attributes );
            return ob_get_clean();
        }

        /**
         * Is Comment.
         *
         * @param string $post_type - post type.
         *
         * @return boolean
         */
        public function is_comment( $post_type ) {
            return 'comment' === $post_type || 'wc_review' === $post_type;
        }

        /**
         * Is BuddyPress Activity.
         *
         * @param string $post_type - post type.
         *
         * @return boolean
         */
        public function is_activity( $post_type ) {
            return 'bp_activity' === $post_type;
        }

        /**
         * Get Like Type.
         *
         * @param string $post_type - post type.
         *
         * @return string
         */
        public function get_like_type( $post_type ) {
            $result = '';

            switch ( $post_type ) {
                case 'page':
                    $result = sociality()->settings()->get_option( 'type_page', 'sociality_likes', 'disabled' );
                    break;
                case 'comment':
                    $result = sociality()->settings()->get_option( 'type_comment', 'sociality_likes', 'heart' );
                    break;
                case 'wc_product':
                    $result = sociality()->settings()->get_option( 'type_wc_product', 'sociality_likes', 'disabled' );
                    break;
                case 'wc_review':
                    $result = sociality()->settings()->get_option( 'type_wc_review', 'sociality_likes', 'thumbs' );
                    break;
                case 'bb_topic':
                case 'bb_reply':
                    $result = sociality()->settings()->get_option( 'type_bb_topic', 'sociality_likes', 'heart' );
                    break;
                case 'bp_activity':
                    $result = sociality()->settings()->get_option( 'type_bp_activity', 'sociality_likes', 'heart' );
                    break;
                case 'portfolio':
                    $result = sociality()->settings()->get_option( 'type_custom_portfolio', 'sociality_likes', 'heart' );
                    break;
                case 'post':
                    $result = sociality()->settings()->get_option( 'type_post', 'sociality_likes', 'heart' );
                    break;
            }

            return $result;
        }

        /**
         * Get Post Meta.
         *
         * @param number $post_id - post ID.
         * @param string $post_type - post type.
         * @param string $name - meta name.
         *
         * @return mixed
         */
        public function get_post_meta( $post_id, $post_type, $name ) {
            $name = "_{$post_type}{$name}";

            if ( $this->is_comment( $post_type ) ) {
                $result = get_comment_meta( $post_id, $name );
            } elseif ( $this->is_activity( $post_type ) && function_exists( 'bp_activity_get_meta' ) ) {
                $result = bp_activity_get_meta( $post_id, $name );
            } else {
                $result = get_post_meta( $post_id, $name );
            }

            if ( is_array( $result ) && count( $result ) ) {
                if ( ! $this->is_activity( $post_type ) ) {
                    $result = $result[0];
                }
            }

            return $result;
        }

        /**
         * Update Post Meta.
         *
         * @param number $post_id - post ID.
         * @param string $post_type - post type.
         * @param string $name - meta name.
         * @param mixed  $value - meta value.
         */
        public function update_post_meta( $post_id, $post_type, $name, $value ) {
            $name = "_{$post_type}{$name}";

            if ( $this->is_comment( $post_type ) ) {
                update_comment_meta( $post_id, $name, $value );
            } elseif ( $this->is_activity( $post_type ) && function_exists( 'bp_activity_update_meta' ) ) {
                bp_activity_update_meta( $post_id, $name, $value );
            }

            update_post_meta( $post_id, $name, $value );
        }

        /**
         * AJAX Post Like.
         */
        public function ajax_post_like_action() {
            // Security.
            $nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : 0;

            if ( ! wp_verify_nonce( $nonce, 'ajax-nonce' ) ) {
                die( 'Nope!' );
            }

            // phpcs:disable
            $post_id   = isset( $_REQUEST['post_id'] ) && is_numeric( $_REQUEST['post_id'] ) ? $_REQUEST['post_id'] : '';
            $post_type = isset( $_REQUEST['post_type'] ) ? $_REQUEST['post_type'] : '';
            $action    = isset( $_REQUEST['like_action'] ) ? $_REQUEST['like_action'] : '';
            // phpcs:enable

            if ( ! $post_id ) {
                exit();
            }

            // Get plugin options.
            $likes_count = (int) $this->get_post_meta( $post_id, $post_type, '_likes_count' );

            $post_liked         = 0;
            $is_user_post_liked = $this->is_user_post_liked( $post_id, $post_type );

            // thumb up.
            if ( 'thumb-up' === $action ) {
                if ( -1 === $is_user_post_liked ) {
                    $post_liked   = 1;
                    $likes_count += 2;
                } elseif ( 1 === $is_user_post_liked ) {
                    $post_liked = 0;
                    $likes_count--;
                } else {
                    $post_liked = 1;
                    $likes_count++;
                }

                // thumb down.
            } elseif ( 'thumb-down' === $action ) {
                if ( -1 === $is_user_post_liked ) {
                    $post_liked = 0;
                    $likes_count++;
                } elseif ( 1 === $is_user_post_liked ) {
                    $post_liked   = -1;
                    $likes_count -= 2;
                } else {
                    $post_liked = -1;
                    $likes_count--;
                }

                // like/dislike.
            } else {
                if ( $is_user_post_liked ) {
                    $post_liked = 0;
                    $likes_count--;
                } else {
                    $post_liked = 1;
                    $likes_count++;
                }
            }

            $this->user_post_like( $post_liked, $post_id, $post_type );
            $this->update_post_meta( $post_id, $post_type, '_likes_count', $likes_count );

            header( 'Content-Type: application/json' );
            echo wp_json_encode(
                array(
                    'success'     => true,
                    'likes_count' => $likes_count,
                    'post_liked'  => $post_liked,
                )
            );

            exit();
        }

        /**
         * Update user like data for current post
         *
         * @param mixed  $value - like value.
         * @param number $post_id - post ID.
         * @param string $post_type - post type.
         */
        public function user_post_like( $value, $post_id, $post_type ) {
            // user is logged in.
            if ( is_user_logged_in() ) {
                $user_id = get_current_user_id();

                // user is anonymous.
            } else {
                // phpcs:ignore
                $user_id = $_SERVER['REMOTE_ADDR'];
            }

            // get all users liked this post.
            $post_meta_users = $this->get_post_meta( $post_id, $post_type, '_user_liked' );

            if ( ! is_array( $post_meta_users ) ) {
                $post_meta_users = array();
            }

            // save new user like value.
            $post_meta_users[ $user_id ] = $value;
            $this->update_post_meta( $post_id, $post_type, '_user_liked', $post_meta_users );
        }

        /**
         * Get user liked value
         *
         * @param number $post_id - post ID.
         * @param string $post_type - post type.
         *
         * @return boolean
         */
        public function is_user_post_liked( $post_id, $post_type ) {
            // user is logged in.
            if ( is_user_logged_in() ) {
                $user_id = get_current_user_id();

                // user is anonymous.
            } else {
                // phpcs:ignore
                $user_id = $_SERVER['REMOTE_ADDR'];
            }

            // get all users liked this post.
            $post_meta_users = $this->get_post_meta( $post_id, $post_type, '_user_liked' );

            return is_array( $post_meta_users ) && isset( $post_meta_users[ $user_id ] ) ? $post_meta_users[ $user_id ] : 0;
        }
    }
endif;
