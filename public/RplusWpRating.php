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

        add_action( 'wp_ajax_rplus_wp_rating_ajax_dofeedback', array( $this, 'ajax_dofeedback' ) );
        add_action( 'wp_ajax_nopriv_rplus_wp_rating_ajax_dofeedback', array( $this, 'ajax_dofeedback' ) );

        $this->register();

        add_filter( 'the_content', array( $this, 'add_rating_controls_to_content' ) );

	}

    /**
     * Get post type name of custom post type
     *
     * @return string
     */
    public function get_post_type() {
        return $this->post_type;
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

        $button_positive = '<button class="rplus-rating-dorating rplus-rating-positive" data-type="positive" data-post="'.get_the_ID().'">Thumb up <span>'.$positives.'</span></button>';
        $button_negative = '<button class="rplus-rating-dorating rplus-rating-negative" data-type="negative" data-post="'.get_the_ID().'">Thumb down <span>'.$negatives.'</span></button>';

        $btn_label_positive = str_replace( '{count}', '<span class="rating-count-positive">' . $positives . '</span>', get_option( 'rplus_ratings_options_btn_label_positive' ) );
        $btn_label_negative = str_replace( '{count}', '<span class="rating-count-negative">' . $negatives . '</span>', get_option( 'rplus_ratings_options_btn_label_negative' ) );

        $title = get_option( 'rplus_ratings_options_title' );
        ob_start();
        ?>

        <div class="rplus-rating-controls">

            <?php if ( ! empty( $title ) ) : ?>
                <h3><?php echo $title; ?></h3>
            <?php endif; ?>
            <p class="button-group">
                <a href="#" class="button thumbs-up rplus-rating-dorating" data-type="positive" data-post="<?php the_ID(); ?>"><span class="icon-thumbs-up"></span> <?php echo $btn_label_positive; ?></a>
                <a href="#" class="button thumbs-down rplus-rating-dorating" data-type="negative" data-post="<?php the_ID(); ?>"><span class="icon-thumbs-down"></span> <?php echo $btn_label_negative; ?></a>
            </p>

            <!-- div class="rplus-rating-positive-container">
                <?php if ( $form_positive ) : ?>
                    <button class="rplus-rating-toggle-form" data-type="positive" data-post="<?php the_ID(); ?>">
                        Thumb up
                        <span><?php echo $positives; ?></span>
                    </button>
                    <div class="rplus-rating-positive-form" style="display: none;">
                        <?php if ( isset( $textarea_positive ) && $textarea_positive == '1' ) : ?>
                            <textarea name="rplus_rating_feedback_positive"></textarea>
                        <?php endif; ?>

                        <button class="rplus-rating-dorating rplus-rating-positive" data-form="true" data-type="positive" data-post="<?php the_ID(); ?>"><?php _e( 'Send feedback', 'required-wp-rating' ); ?></button>
                    </div>
                <?php else : ?>
                    <?php echo $button_positive; ?>
                <?php endif; ?>
            </div>

            <div class="rplus-rating-negative-container">
                <?php if ( $form_negative ) : ?>
                    <button class="rplus-rating-toggle-form" data-type="negative">
                        Thumb down
                        <span><?php echo $negatives; ?></span>
                    </button>
                    <div class="rplus-rating-negative-form" style="display: none;">
                        <?php if ( isset( $textarea_negative ) && $textarea_negative == '1' ) : ?>
                            <textarea name="rplus_rating_feedback_negative"></textarea>
                        <?php endif; ?>

                        <button class="rplus-rating-dorating rplus-rating-negative" data-form="true" data-type="negative" data-post="<?php the_ID(); ?>"><?php _e( 'Send feedback', 'required-wp-rating' ); ?></button>
                    </div>
                <?php else : ?>
                    <?php echo $button_negative; ?>
                <?php endif; ?>
            </div -->
        </div>

        <?php
        $output = ob_get_contents();
        ob_clean();

        return $output;

    }

    /**
     * Get form for adding feedbacks to rating
     *
     * @param $rating_id
     * @param $type
     * @return string
     */
    private function get_rating_feedbackform( $rating_id, $type ) {

        $description = get_option( 'rplus_ratings_options_feedback_positive_descr' );

        ob_start(); ?>

        <div class="feedback-form <?php echo $type; ?>">
            <?php if ( ! empty( $description ) ) : ?>
                <p>
                    <?php echo $description; ?>
                    Das freut uns sehr! Wir helfen gerne und hoffen immer, dass unsere Erklärungen Ihnen weiterhelfen.<br>
                    Möchten Sie uns noch etwas mitteilen?
                </p>
            <?php endif; ?>
            <form name="rplusfeedback" data-type="<?php echo $type; ?>" data-rating_id="<?php echo $rating_id; ?>">
                <div class="form-row">
                    <textarea class="feedback" name="feedback-<?php echo $type; ?>"></textarea>
                </div>
                <input type="submit" class="button rplus-rating-dofeedback" value="Abschicken">
            </form>
        </div>

        <?php
        $output = ob_get_contents();
        ob_clean();

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

        update_post_meta( $rating_id, 'vote_for_post_id', $post_id );
        update_post_meta( $rating_id, 'vote_type', $type );
        update_post_meta( $rating_id, 'vote_ip', $_SERVER['REMOTE_ADDR'] );
        update_post_meta( $rating_id, 'vote_browser', $_SERVER['USER_AGENT'] );

        // check for feedback textarea
        /*
        $feedback = get_option( 'rplus_ratings_options_feedback_' . $type );
        if ( $feedback == '1' && isset( $_POST['feedback'] ) ) {
            update_post_meta( $rating_id, 'vote_feedback', $_POST['feedback'] );
        }
        */

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
//            wp_send_json_error( __( 'You\'ve already made your rating for this page.', 'required-wp-rating' ) );
        }

        // proceed when we have a correct type
        if ( $type ) {

            $rating_id = $this->add_rating( $post_id, $type );

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

            $response = array(
                'positives' => get_post_meta( $post_id, 'rplus_ratings_positive', true ),
                'negatives' => get_post_meta( $post_id, 'rplus_ratings_negative', true ),
                'message'   => __( 'Your vote was saved.', 'required-wp-rating' ),
                'rating_id' => $rating_id,
                'token' => wp_create_nonce( 'rplus-do-feedback-'.$rating_id )
            );

            $show_feedback = get_option( 'rplus_ratings_options_feedback_' . $type );
            if ( ! empty( $show_feedback ) ) {
                $response['feedback'] = true;
                $response['feedbackform'] = $this->get_rating_feedbackform( $rating_id, $type );
            }

            // send response
            wp_send_json_success( $response );
        }

        wp_send_json_error( __( 'Technical hiccups. Sorry.', 'required-wp-rating' ) );
        exit;

    }

    /**
     * Ajax action to add feedback to ratings
     */
    public function ajax_dofeedback() {

        // check for valid rating_id
        if ( ! isset( $_POST['post_id'] ) || ! is_numeric( $_POST['post_id'] ) ) {
            wp_send_json_error( __( 'This is not the feedback you are looking for.', 'required-wp-rating' ) );
            exit;
        }

        $rating_id = $_POST['rating_id'];
        $post_id = $_POST['post_id'];

        // check nonce
        check_ajax_referer( 'rplus-do-feedback-'.$rating_id, '_token' );

        // check for valid feedback text
        if ( ! isset( $_POST['feedback'] ) || empty( $_POST['feedback'] ) ) {
            wp_send_json_error( __( 'Please fill in a feedback for your rating.', 'required-wp-rating' ) );
            exit;
        }

        // add feedback to rating
        update_post_meta( $rating_id, 'vote_feedback', $_POST['feedback'] );

        do_action( 'rplus_wp_rating_send_feedback', $rating_id, $_POST['feedback'], $post_id );

        wp_send_json_success( __( 'Thank you for the feedback.', 'required-wp-rating' ) );
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

        // don't add controls when we're not on a single or page site
        if ( ! is_single() && ! is_page() ) {
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
