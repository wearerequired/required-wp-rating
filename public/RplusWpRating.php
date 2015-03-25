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
	 * @since 1.0.0
	 * @var      string
	 */
	protected $plugin_slug = 'required-wp-rating';

	/**
	 * Instance of this class.
	 *
	 * @since 1.0.0
	 * @var RplusWpRating
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
	 * Polylang in use?
	 *
	 * @var bool
	 */
	private $polylang = false;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
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

		if ( function_exists( 'pll__' ) ) {
			$this->polylang = true;
		}

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
				'labels'              => array(),
				'hierarchical'        => false,
				'public'              => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_nav_menus'   => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'has_archive'         => false,
				'query_var'           => false,
				'can_export'          => false,
				'capability_type'     => 'post',
				'rewrite'             => false
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
	 * @since 1.0.0
	 * @return string
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 *
	 * @return RplusWpRating A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since 1.0.0
	 */
	public function load_plugin_textdomain() {
		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );
	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );

		wp_localize_script( $this->plugin_slug . '-plugin-script', 'RplusWpRatingAjax', array(
			// URL to wp-admin/admin-ajax.php to process the request
			'ajaxurl'    => admin_url( 'admin-ajax.php' ),
			// nonces
			'nonce_vote' => wp_create_nonce( 'rplus-do-rating' )
		) );
	}

	/**
	 * Print rating controls.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public function rating_shortcode( $atts ) {

		return $this->get_rating_controls();

	}

	/**
	 * Get the rating controls.
	 * @return string HTML output for the rating controls.
	 */
	public function get_rating_controls() {
		$positives = get_post_meta( get_the_ID(), 'rplus_ratings_positive', true );
		$negatives = get_post_meta( get_the_ID(), 'rplus_ratings_negative', true );

		$btn_label_positive = str_replace( '{count}', '<span class="rating-count-positive">' . $positives . '</span>', get_option( 'rplus_ratings_options_btn_label_positive' ) );
		$btn_label_negative = str_replace( '{count}', '<span class="rating-count-negative">' . $negatives . '</span>', get_option( 'rplus_ratings_options_btn_label_negative' ) );

		$title = get_option( 'rplus_ratings_options_title' );
		ob_start();
		?>

		<div class="rplus-rating-controls">

			<?php if ( ! empty( $title ) ) : ?>
				<h3>
					<?php echo $this->polylang ? pll__( $title ) : $title; ?>
				</h3>
			<?php endif; ?>
			<p class="button-group">
				<a href="#" class="button thumbs-up rplus-rating-dorating" data-type="positive" data-post="<?php the_ID(); ?>">
					<span class="icon-thumbs-up"></span>
					<?php echo $this->polylang ? pll__( get_option( 'rplus_ratings_options_btn_label_positive' ) ) : $btn_label_positive; ?>
				</a>
				<a href="#" class="button thumbs-down rplus-rating-dorating" data-type="negative" data-post="<?php the_ID(); ?>">
					<span class="icon-thumbs-down"></span>
					<?php echo $this->polylang ? pll__( get_option( 'rplus_ratings_options_btn_label_negative' ) ) : $btn_label_negative; ?>
				</a>
			</p>

		</div>

		<?php
		$output = ob_get_contents();
		ob_clean();

		return $output;
	}

	/**
	 * Get form for adding feedbacks to rating
	 *
	 * @param int    $rating_id ID of the rating.
	 * @param string $type      Rating type. Can be either 'positive' or 'negative'.
	 *
	 * @return string
	 */
	private function get_rating_feedbackform( $rating_id, $type ) {
		$description = get_option( 'rplus_ratings_options_feedback_' . $type . '_descr' );

		$reply       = get_option( 'rplus_ratings_options_feedback_reply' );
		$reply_descr = get_option( 'rplus_ratings_options_feedback_reply_descr' );

		ob_start(); ?>

		<div class="feedback-form <?php echo $type; ?>">
			<?php if ( ! empty( $description ) ) : ?>
				<p>
					<?php echo $this->polylang ? pll__( $description ) : $description; ?>
				</p>
			<?php endif; ?>
			<form name="rplusfeedback" data-type="<?php echo $type; ?>" data-rating_id="<?php echo $rating_id; ?>">
				<div class="form-row">
					<textarea class="feedback" name="feedback-<?php echo $type; ?>"></textarea>
				</div>
				<?php if ( 1 == $reply ) : ?>
					<div class="feedback-reply">
						<?php if ( ! empty( $reply_descr ) ) : ?>
							<p>
								<?php echo $this->polylang ? pll__( $reply_descr ) : $reply_descr; ?>
								<input type="text" name="reply" class="reply" placeholder="<?php echo $this->polylang ? pll__( 'Email address' ) : __( 'Email address', 'required-wp-rating' ); ?>" size="15">
							</p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<input type="submit" class="button rplus-rating-dofeedback" value="<?php echo $this->polylang ? pll__( 'Send feedback' ) : __( 'Send feedback', 'required-wp-rating' ); ?>">
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
	 * @param int    $post_id ID of the rated post.
	 * @param string $type    Rating type. Either 'positive' or 'negative'
	 *
	 * @return int|WP_Error
	 */
	private function add_rating( $post_id, $type ) {
		// add new vote (post type entry)
		$rating_id = wp_insert_post( array(
			'post_title'  => $type . ': ' . $post_id,
			'post_type'   => $this->post_type,
			'post_status' => 'private'
		) );

		update_post_meta( $rating_id, 'vote_for_post_id', $post_id );
		update_post_meta( $rating_id, 'vote_type', $type );
		update_post_meta( $rating_id, 'vote_ip', $_SERVER['REMOTE_ADDR'] );
		update_post_meta( $rating_id, 'vote_browser', $_SERVER['HTTP_USER_AGENT'] );

		return $rating_id;
	}

	/**
	 * Ajax vote action
	 */
	public function ajax_dorating() {
		// check nonce
		check_ajax_referer( 'rplus-do-rating', '_token' );

		$existing_votes = isset( $_COOKIE[ $this->cookie_key ] ) ? explode( ',', $_COOKIE[ $this->cookie_key ] ) : array();
		$post_id        = intval( $_POST['post_id'] );
		$type           = in_array( $_POST['type'], array( 'positive', 'negative' ) ) ? $_POST['type'] : false;

		// check if user already voted (cookies)
		if ( in_array( $post_id, $existing_votes ) ) {
			$msg = $this->polylang ? pll__( 'You\'ve already made your rating for this page.' ) : __( 'You\'ve already made your rating for this page.', 'required-wp-rating' );
			wp_send_json_error( apply_filters( 'rplus_wp_rating/filter/messages/alreadyvoted', $msg ) );
		}

		// proceed when we have a correct type
		if ( $type ) {

			$rating_id = $this->add_rating( $post_id, $type );

			// update votes_$type on current post (increment)
			$current_votes = get_post_meta( $post_id, 'rplus_ratings_' . $type, true );
			update_post_meta( $post_id, 'rplus_ratings_' . $type, ( $current_votes + 1 ) );

			// add current post_id to cookie of user
			array_push( $existing_votes, $post_id );
			$cookie_votes = implode( ',', $existing_votes );

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
				'message'   => $this->polylang ? pll__( 'Your vote was saved.' ) : __( 'Your vote was saved.', 'required-wp-rating' ),
				'rating_id' => $rating_id,
				'token'     => wp_create_nonce( 'rplus-do-feedback-' . $rating_id )
			);

			$show_feedback = get_option( 'rplus_ratings_options_feedback_' . $type );
			if ( ! empty( $show_feedback ) ) {
				$response['feedback']     = true;
				$response['feedbackform'] = $this->get_rating_feedbackform( $rating_id, $type );
			}

			// send response
			wp_send_json_success( $response );
		}

		wp_send_json_error( apply_filters( 'rplus_wp_rating/filter/messages/error', __( 'Technical hiccups. Sorry.', 'required-wp-rating' ) ) );
		exit;
	}

	/**
	 * Ajax action to add feedback to ratings
	 */
	public function ajax_dofeedback() {
		// check for valid rating_id
		if ( ! isset( $_POST['rating_id'] ) || ! is_numeric( $_POST['rating_id'] ) ) {
			wp_send_json_error( apply_filters( 'rplus_wp_rating/filter/messages/missing_rating_id', __( 'This is not the feedback you are looking for.', 'required-wp-rating' ) ) );
			exit;
		}

		$rating_id = $_POST['rating_id'];
		$post_id   = $_POST['post_id'];

		// check nonce
		check_ajax_referer( 'rplus-do-feedback-' . $rating_id, '_token' );

		// check for valid feedback text
		if ( ! isset( $_POST['feedback'] ) || empty( $_POST['feedback'] ) ) {
			wp_send_json_error( apply_filters( 'rplus_wp_rating/filter/messages/empty_feedback', __( 'Please fill in a feedback for your rating.', 'required-wp-rating' ) ) );
			exit;
		}

		// add feedback to rating
		update_post_meta( $rating_id, 'vote_feedback', sanitize_text_field( $_POST['feedback'] ) );
		update_post_meta( $rating_id, 'vote_reply_email', sanitize_text_field( $_POST['reply'] ) );

		do_action( 'rplus_wp_rating_send_feedback', $rating_id, $_POST['feedback'], $post_id, $_POST['reply'] );

		$msg = $this->polylang ? pll__( 'Thank you for the feedback.' ) : __( 'Thank you for the feedback.', 'required-wp-rating' );

		wp_send_json_success( apply_filters( 'rplus_wp_rating/filter/messages/feedback_thx', $msg ) );
		exit;
	}

	/**
	 * Add rating controls for activated post_types (activation via plugin options backend)
	 *
	 * @param string $content The post content we're filtering.
	 *
	 * @return string
	 */
	public function add_rating_controls_to_content( $content ) {
		global $post;

		// don't do anything when we're not a post
		if ( ! is_a( $post, 'WP_Post' ) ) {
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