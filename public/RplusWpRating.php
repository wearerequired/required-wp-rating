<?php
/**
 * required WP Ratings
 *
 * @package   required-wp-rating
 * @author    Stefan Pasch <stefan@required.ch>
 * @license   GPL-2.0+
 * @link      http://required.ch
 * @copyright 2014 required gmbh
 */

/**
 * @package required-wp-rating
 * @author  Stefan Pasch <stefan@required.ch>
 */
class RplusWpRating {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 * Unique identifier
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 * @var      string
	 */
	protected $plugin_slug = 'required-wp-rating';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 * @var      object
	 */
	protected static $instance = null;

    /**
     * The cookie var where the votes will be saved in
     *
     * @var string
     */
    private $cookie_key = 'rpluswprating_votes';

    /**
     * Custom PostType name
     *
     * @var string
     */
    private $post_type = 'rplus_rating';

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        add_shortcode( 'rplus-rating', array( $this, 'rating_shortcode' ) );

        add_action( 'wp_ajax_rplus_wp_rating_ajax_dorating', array( $this, 'ajax_dorating' ) );
        add_action( 'wp_ajax_nopriv_rplus_wp_rating_ajax_dorating', array( $this, 'ajax_dorating' ) );

        $this->register();

        add_filter( 'the_content', array( $this, 'add_rating_controls_to_content' ) );

	}

    /**
     * Registers the custom post type for saving the ratings and related data
     */
    private function register() {

        /**
         * Custom Post Type Args
         * @var array
         */
        $args = apply_filters( 'rplus_wp_rating/filter/types/rating/args',
            array(
                'labels' =>                 array(),
                'hierarchical' =>           false,
                'public' =>                 false,
                'show_ui' =>                false,
                'show_in_menu' =>           false,
                'show_in_nav_menus' =>      false,
                'publicly_queryable' =>     false,
                'exclude_from_search' =>    true,
                'has_archive' =>            false,
                'query_var' =>              false,
                'can_export' =>             false,
                'capability_type' =>        'post',
                'rewrite' =>                false
            )
        );

        /**
         * Register our Custom Post Type with WordPress
         */
        register_post_type( $this->post_type, $args );

    }

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 * @return    RplusWpRating    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {

	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {

	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );

        wp_localize_script( $this->plugin_slug . '-plugin-script', 'RplusWpRatingAjax', array(
            // URL to wp-admin/admin-ajax.php to process the request
            'ajaxurl' => admin_url( 'admin-ajax.php' ),

            // nonces
            'nonce_vote' => wp_create_nonce( 'rplus-do-rating' )
        ));
	}

    /**
     * Print rating controls
     *
     * @param $atts
     * @return string
     */
    public function rating_shortcode( $atts ) {

        return $this->get_rating_controls();

    }

    /**
     * Get the rating controls.
     * @return string
     */
    public function get_rating_controls() {

        global $post;

        $positives = get_post_meta( get_the_ID(), 'rplus_ratings_positive', true );
        $negatives = get_post_meta( get_the_ID(), 'rplus_ratings_negative', true );

        $output = '';

        $output .= '<div class="rplus-rating-controls">';
        $output .= '<a href="#" class="rplus-rating-dorating rplus-rating-positive" data-type="positive" data-post="'.get_the_ID().'"> Thumb up <span>'.$positives.'</span></a>';
        $output .= '<a href="#" class="rplus-rating-dorating rplus-rating-negative" data-type="negative" data-post="'.get_the_ID().'"> Thumd down <span>'.$negatives.'</span></a>';
        $output .= '</div>';

        return $output;

    }

    /**
     * Add new rating entry
     *
     * @param $post_id
     * @param $type
     * @return int|WP_Error
     */
    private function add_rating( $post_id, $type ) {

        // add new vote (post type entry)
        $rating_id = wp_insert_post( array(
            'post_title' => $type . ': ' . $post_id,
            'post_type' => $this->post_type
        ) );

        update_post_meta( $post_id, 'vote_for_post_id', $post_id );
        update_post_meta( $post_id, 'vote_type', $type );
        update_post_meta( $post_id, 'vote_ip', $_SERVER['REMOTE_ADDR'] );
        update_post_meta( $post_id, 'vote_browser', $_SERVER['USER_AGENT'] );

        return $rating_id;
    }

    /**
     * Ajax vote action
     */
    public function ajax_dorating() {

        // check nonce
        check_ajax_referer( 'rplus-do-rating', '_token' );

        $existing_votes = isset( $_COOKIE[ $this->cookie_key ] ) ? explode( ',', $_COOKIE[ $this->cookie_key ] ) : array();
        $post_id = intval( $_POST['post_id'] );
        $type = in_array( $_POST['type'], array( 'positive', 'negative' ) ) ? $_POST['type'] : false;

        // check if user already voted (cookies)
        if ( in_array( $post_id, $existing_votes ) ) {
            wp_send_json_error( __( 'You\'ve already voted, sorry.', 'required-wp-rating' ) );
        }

        // proceed when we have a correct type
        if ( $type ) {

            $this->add_rating( $post_id, $type );

            // update votes_$type on current post (increment)
            $current_votes = get_post_meta( $post_id, 'rplus_ratings_' . $type, true );
            update_post_meta( $post_id, 'rplus_ratings_' . $type, ( $current_votes + 1 ) );

            // add current post_id to cookie of user
            array_push( $existing_votes, $post_id );
            $cookie_votes = implode( ',',  $existing_votes );

            setcookie(
                $this->cookie_key,
                $cookie_votes,
                time() + YEAR_IN_SECONDS,
                COOKIEPATH,
                COOKIE_DOMAIN
            );

            // send response
            wp_send_json_success( array(
                'positives' => get_post_meta( $post_id, 'rplus_ratings_positive', true ),
                'negatives' => get_post_meta( $post_id, 'rplus_ratings_negative', true ),
                'message'   => __( 'Your vote was saved.', 'required-wp-rating' )
            ) );
        }

        wp_send_json_error( __( 'Technical hiccups. Sorry.', 'required-wp-rating' ) );
        exit;

    }

    /**
     * Add rating controls for activated post_types (activation via plugin options backend)
     *
     * @param $content
     * @return mixed
     */
    public function add_rating_controls_to_content( $content ) {

        global $post;

        // don't do anything when we're not a post
        if ( ! is_object( $post ) || ( get_class( $post ) != 'WP_Post' ) ) {

            return $content;

        }

        $current_post_type = get_post_type( $post->ID );

        // get post_type_select option and check for current post_type
        $selected = get_option( 'rplus_ratings_options_posttypes_select' );

        if ( is_array( $selected ) && isset( $selected[ $current_post_type ] ) && $selected[ $current_post_type ] == '1' ) {

            // append the rating controls to the content
            $content .= $this->get_rating_controls();

        }

        return $content;
    }

}
