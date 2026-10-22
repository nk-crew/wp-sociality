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
         * Option set once every stored IP like key is hashed.
         */
        const IP_HASH_MIGRATED_OPTION = 'sociality_ip_hash_migrated';

        /**
         * Option with the last migrated row ID per meta table, -1 when a table is done.
         */
        const IP_HASH_CURSOR_OPTION = 'sociality_ip_hash_migration_cursor';

        /**
         * WP-Cron hook that migrates one batch.
         */
        const IP_HASH_CRON_HOOK = 'sociality_ip_hash_migration';

        /**
         * Meta rows per migration batch.
         */
        const IP_HASH_BATCH_SIZE = 200;

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

            // hash the raw IP keys of stored anonymous likes.
            add_action( 'init', array( $this, 'schedule_ip_hash_migration' ) );
            add_action( self::IP_HASH_CRON_HOOK, array( $this, 'migrate_ip_like_keys_batch' ) );
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
                $result = get_comment_meta( $post_id, $name, false );
            } elseif ( $this->is_activity( $post_type ) && function_exists( 'bp_activity_get_meta' ) ) {
                $result = bp_activity_get_meta( $post_id, $name );
            } else {
                $result = get_post_meta( $post_id, $name, false );
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
            } else {
                update_post_meta( $post_id, $name, $value );
            }
        }

        /**
         * Check that the liked object exists and the current visitor may read it.
         * Anonymous visitors may like only public objects.
         *
         * @param int    $post_id - object ID.
         * @param string $post_type - like type key.
         *
         * @return boolean
         */
        public function is_likeable_object( $post_id, $post_type ) {
            if ( $this->is_comment( $post_type ) ) {
                $comment = get_comment( $post_id );

                return $comment && '1' === (string) $comment->comment_approved && $this->is_post_readable( $comment->comment_post_ID );
            }

            if ( $this->is_activity( $post_type ) ) {
                if ( ! class_exists( 'BP_Activity_Activity' ) ) {
                    return false;
                }

                $activity = new BP_Activity_Activity( $post_id );
                $user_id  = get_current_user_id();

                if ( empty( $activity->id ) || ! empty( $activity->is_spam ) ) {
                    return false;
                }

                // bp_activity_user_can_read() exists since BuddyPress 3.0.
                if ( $user_id && function_exists( 'bp_activity_user_can_read' ) ) {
                    return bp_activity_user_can_read( $activity, $user_id );
                }

                return empty( $activity->hide_sitewide );
            }

            return $this->is_post_readable( $post_id );
        }

        /**
         * Check that a post is public, or readable by the logged-in user.
         *
         * @param int $post_id - post ID.
         *
         * @return boolean
         */
        public function is_post_readable( $post_id ) {
            if ( $this->is_post_public( $post_id ) ) {
                return true;
            }

            $post = get_post( $post_id );

            return $post && is_user_logged_in() && is_post_type_viewable( $post->post_type ) && current_user_can( 'read_post', $post->ID );
        }

        /**
         * Check that a post exists and is publicly viewable.
         *
         * @param int $post_id - post ID.
         *
         * @return boolean
         */
        public function is_post_public( $post_id ) {
            $post = get_post( $post_id );

            if ( ! $post ) {
                return false;
            }

            // is_post_publicly_viewable() exists since WP 5.7.
            if ( function_exists( 'is_post_publicly_viewable' ) ) {
                return is_post_publicly_viewable( $post );
            }

            $status = get_post_status_object( $post->post_status );

            return $status && $status->public && is_post_type_viewable( $post->post_type );
        }

        /**
         * AJAX Post Like.
         */
        public function ajax_post_like_action() {
            // Security. Anonymous visitors get no nonce check: their uid-0 nonce is shared
            // and goes stale in page caches, so the object checks below are their guard.
            if ( is_user_logged_in() ) {
                $nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : 0;

                if ( ! wp_verify_nonce( $nonce, 'ajax-nonce' ) ) {
                    die( 'Nope!' );
                }
            }

            // phpcs:disable WordPress.Security.NonceVerification.Recommended
            $post_id   = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : 0;
            $post_type = isset( $_REQUEST['post_type'] ) ? sanitize_key( $_REQUEST['post_type'] ) : '';
            $action    = isset( $_REQUEST['like_action'] ) ? sanitize_key( $_REQUEST['like_action'] ) : '';
            // phpcs:enable

            if (
                ! $post_id
                || ! in_array( $action, array( 'heart', 'thumb-up', 'thumb-down' ), true )
                || ! in_array( $this->get_like_type( $post_type ), array( 'heart', 'thumbs' ), true )
                || ! $this->is_likeable_object( $post_id, $post_type )
            ) {
                die( 'Nope!' );
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
                    --$likes_count;
                } else {
                    $post_liked = 1;
                    ++$likes_count;
                }

                // thumb down.
            } elseif ( 'thumb-down' === $action ) {
                if ( -1 === $is_user_post_liked ) {
                    $post_liked = 0;
                    ++$likes_count;
                } elseif ( 1 === $is_user_post_liked ) {
                    $post_liked   = -1;
                    $likes_count -= 2;
                } else {
                    $post_liked = -1;
                    --$likes_count;
                }

                // like/dislike.
            } elseif ( $is_user_post_liked ) {
                $post_liked = 0;
                --$likes_count;
            } else {
                $post_liked = 1;
                ++$likes_count;
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
            // get all users liked this post.
            $post_meta_users = $this->get_post_meta( $post_id, $post_type, '_user_liked' );

            if ( ! is_array( $post_meta_users ) ) {
                $post_meta_users = array();
            }

            // save new user like value.
            $post_meta_users[ $this->get_like_user_key() ] = $value;

            // A copy read before a migration batch still holds raw IPs, the visitor's own included,
            // so no write stores one again.
            $post_meta_users = $this->hash_ip_like_keys( $post_meta_users );

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
            // get all users liked this post.
            $post_meta_users = $this->get_post_meta( $post_id, $post_type, '_user_liked' );

            if ( ! is_array( $post_meta_users ) ) {
                return 0;
            }

            $user_key = $this->get_like_user_key();

            if ( isset( $post_meta_users[ $user_key ] ) ) {
                return $post_meta_users[ $user_key ];
            }

            // until the migration finishes, an old anonymous like is still keyed by the raw IP.
            if ( ! is_user_logged_in() && ! get_option( self::IP_HASH_MIGRATED_OPTION ) ) {
                $ip = $this->get_remote_ip();

                if ( isset( $post_meta_users[ $ip ] ) ) {
                    return $post_meta_users[ $ip ];
                }
            }

            return 0;
        }

        /**
         * Key of the current visitor in the `_user_liked` array:
         * the user ID when logged in, a keyed hash of the IP otherwise.
         *
         * @return int|string
         */
        public function get_like_user_key() {
            if ( is_user_logged_in() ) {
                return get_current_user_id();
            }

            return $this->get_ip_like_key( $this->get_remote_ip() );
        }

        /**
         * Hashed like key for an IP. The `ip_` prefix keeps it apart from numeric user IDs.
         *
         * @param string $ip - IP address.
         *
         * @return string
         */
        public function get_ip_like_key( $ip ) {
            return 'ip_' . substr( hash_hmac( 'sha256', $ip, $this->get_ip_hash_secret() ), 0, 32 );
        }

        /**
         * Site secret for IP like keys. Kept apart from the auth salts, so rotating them
         * does not change the keys of stored likes.
         *
         * @return string
         */
        public function get_ip_hash_secret() {
            $secret = get_option( 'sociality_ip_hash_secret' );

            if ( ! $secret ) {
                // A parallel request may create it first, then its value is the one to use.
                add_option( 'sociality_ip_hash_secret', wp_generate_password( 64, true, true ) );
                $secret = get_option( 'sociality_ip_hash_secret' );
            }

            return $secret;
        }

        /**
         * Current visitor IP.
         *
         * @return string
         */
        public function get_remote_ip() {
            return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        }

        /**
         * Replace raw IP keys of a `_user_liked` array with their hashes, keeping the values.
         * A hashed key that already exists is newer and wins over the raw IP.
         *
         * @param array $likes - likes keyed by user ID or IP.
         *
         * @return array
         */
        public function hash_ip_like_keys( $likes ) {
            $result = array();

            foreach ( $likes as $key => $value ) {
                if ( is_string( $key ) && filter_var( $key, FILTER_VALIDATE_IP ) ) {
                    $hashed_key = $this->get_ip_like_key( $key );

                    if ( ! array_key_exists( $hashed_key, $likes ) ) {
                        $result[ $hashed_key ] = $value;
                    }
                } else {
                    $result[ $key ] = $value;
                }
            }

            return $result;
        }

        /**
         * Schedule the next migration batch while stored IP keys remain.
         */
        public function schedule_ip_hash_migration() {
            if ( ! get_option( self::IP_HASH_MIGRATED_OPTION ) && ! wp_next_scheduled( self::IP_HASH_CRON_HOOK ) ) {
                wp_schedule_single_event( time(), self::IP_HASH_CRON_HOOK );
            }
        }

        /**
         * Meta tables that hold `_user_liked` arrays, with the meta keys of every like type.
         *
         * @return array
         */
        public function get_like_meta_tables() {
            global $wpdb;

            $tables = array(
                'post'     => array(
                    'table'       => $wpdb->postmeta,
                    'id_column'   => 'meta_id',
                    'object'      => 'post_id',
                    'cache_group' => 'post_meta',
                    'types'       => array( 'post', 'page', 'wc_product', 'bb_topic', 'bb_reply', 'portfolio' ),
                ),
                'comment'  => array(
                    'table'       => $wpdb->commentmeta,
                    'id_column'   => 'meta_id',
                    'object'      => 'comment_id',
                    'cache_group' => 'comment_meta',
                    'types'       => array( 'comment', 'wc_review' ),
                ),
                'activity' => array(
                    'table'       => ( function_exists( 'bp_core_get_table_prefix' ) ? bp_core_get_table_prefix() : $wpdb->base_prefix ) . 'bp_activity_meta',
                    'id_column'   => 'id',
                    'object'      => 'activity_id',
                    'cache_group' => 'activity_meta',
                    'types'       => array( 'bp_activity' ),
                ),
            );

            // BuddyPress keeps its activity table when deactivated, so look for the table, not the plugin.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $tables['activity']['table'] ) ) ) !== $tables['activity']['table'] ) {
                unset( $tables['activity'] );
            }

            return $tables;
        }

        /**
         * Hash the IP keys of up to IP_HASH_BATCH_SIZE stored `_user_liked` arrays.
         * Resumes from the saved cursor and is safe to run again on migrated rows.
         * Marks the migration done once every table is through.
         *
         * @param int $limit - rows per batch.
         *
         * @return boolean - true when the migration is done.
         */
        public function migrate_ip_like_keys_batch( $limit = self::IP_HASH_BATCH_SIZE ) {
            global $wpdb;

            if ( get_option( self::IP_HASH_MIGRATED_OPTION ) ) {
                return true;
            }

            $cursor = get_option( self::IP_HASH_CURSOR_OPTION, array() );
            $cursor = is_array( $cursor ) ? $cursor : array();

            foreach ( $this->get_like_meta_tables() as $name => $table ) {
                $last_id = isset( $cursor[ $name ] ) ? (int) $cursor[ $name ] : 0;

                if ( $last_id < 0 ) {
                    continue;
                }

                $meta_keys = array_map(
                    function ( $type ) {
                        return "_{$type}_user_liked";
                    },
                    $table['types']
                );

                // phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery
                $rows = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT {$table['id_column']} AS id, {$table['object']} AS object_id, meta_value FROM {$table['table']}
                        WHERE meta_key IN (" . implode( ', ', array_fill( 0, count( $meta_keys ), '%s' ) ) . ")
                        AND {$table['id_column']} > %d ORDER BY {$table['id_column']} ASC LIMIT %d",
                        array_merge( $meta_keys, array( $last_id, $limit ) )
                    )
                );

                $finished = count( $rows ) < $limit;

                foreach ( $rows as $row ) {
                    $likes = maybe_unserialize( $row->meta_value );

                    if ( is_array( $likes ) ) {
                        $hashed = $this->hash_ip_like_keys( $likes );

                        if ( $hashed !== $likes ) {
                            // the old value in WHERE skips a row a like changed meanwhile.
                            $updated = $wpdb->update(
                                $table['table'],
                                array( 'meta_value' => maybe_serialize( $hashed ) ),
                                array(
                                    $table['id_column'] => $row->id,
                                    'meta_value'        => $row->meta_value,
                                )
                            );

                            wp_cache_delete( $row->object_id, $table['cache_group'] );

                            // retry that row in the next batch.
                            if ( ! $updated ) {
                                $finished = false;
                                break;
                            }
                        }
                    }

                    $last_id = (int) $row->id;
                }
                // phpcs:enable

                $cursor[ $name ] = $finished ? -1 : $last_id;
                update_option( self::IP_HASH_CURSOR_OPTION, $cursor, false );

                return false;
            }

            update_option( self::IP_HASH_MIGRATED_OPTION, 1 );
            delete_option( self::IP_HASH_CURSOR_OPTION );

            return true;
        }
    }
endif;
