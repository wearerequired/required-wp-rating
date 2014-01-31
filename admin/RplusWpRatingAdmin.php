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
 * required WP Ratings
 * Administrative functions
 *
 * @package required-wp-rating
 * @author  Stefan Pasch <stefan@required.ch>
 */
class RplusWpRatingAdmin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		/*
		 * Call $plugin_slug from public plugin class.
		 */
		$plugin = RplusWpRating::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

        // Add the options for the options page
        add_action( 'admin_init', array( $this, 'add_plugin_admin_options' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

        // add metaboxes with infos about the ratings
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), RplusWpRating::VERSION );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), RplusWpRating::VERSION );
		}

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 */
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'WP Ratings', $this->plugin_slug ),
			__( 'WP Ratings', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {

    	include_once( 'views/admin.php' );

	}

    /**
     * Add options for the options page
     *
     * @since   1.0.0
     */
    public function add_plugin_admin_options() {

        register_setting( $this->plugin_slug . '-options', 'rplus_ratings_options_posttypes_select' );
        register_setting( $this->plugin_slug . '-options', 'rplus_ratings_options_feedback_positive' );
        register_setting( $this->plugin_slug . '-options', 'rplus_ratings_options_feedback_negative' );

        add_settings_section(
            'rplus_ratings_options_posttypes',
            __( 'Display rating controls', 'required-wp-rating' ),
            function() {
                _e( 'Show rating controls below each post_type\'s content. The controls can be styled using css. See the plugin page for more info\'s. When you don\'t select any post type, the controls won\'t be displayed, but you can add them with the shortcode <strong>[rplus-rating]</strong>.', 'required-wp-rating' );
            },
            $this->plugin_slug
        );

        add_settings_field(
            'rplus_ratings_options_posttypes_select',
            __( 'Post Types', 'required-wp-rating' ),
            function() {
                $selected = get_option( 'rplus_ratings_options_posttypes_select' );
                foreach ( get_post_types( array( 'public' => true ) ) as $post_type ) {
                    $post_type_labels = get_post_type_labels( get_post_type_object( $post_type ) );
                    ?>
                    <p>
                    <label for="rplus_ratings_options_posttypes_select_<?php echo $post_type; ?>">
                        <input type="hidden" name="rplus_ratings_options_posttypes_select[<?php echo $post_type; ?>]" value="0">
                        <input name="rplus_ratings_options_posttypes_select[<?php echo $post_type; ?>]" type="checkbox" id="rplus_ratings_options_posttypes_select_<?php echo $post_type; ?>" value="1" <?php echo ( (is_array($selected) && isset( $selected[ $post_type ] ) && $selected[ $post_type ] == '1') ? 'checked' : '' ); ?>>
                        <?php echo $post_type_labels->name; ?>
                    </label>
                    </p>
                    <?php

                }
            },
            $this->plugin_slug,
            'rplus_ratings_options_posttypes'
        );

        add_settings_section(
            'rplus_ratings_options_feedback',
            __( 'Show feedback-boxes', 'required-wp-rating' ),
            function() {
                _e( 'When activated, you\'ll see a textarea when you do a rating where you can add a feedback before sending.', 'required-wp-rating' );
            },
            $this->plugin_slug
        );

        add_settings_field(
            'rplus_ratings_options_feedback_positive',
            __( 'Positive', 'required-wp-rating' ),
            function() {
                ?>
                <label for="rplus_ratings_options_feedback_positive">
                    <input type="hidden" name="rplus_ratings_options_feedback_positive" value="0">
                    <input name="rplus_ratings_options_feedback_positive" type="checkbox" id="rplus_ratings_options_feedback_positive" value="1" <?php checked( '1', get_option('rplus_ratings_options_feedback_positive') ); ?>>
                    <?php _e( 'Yes, show a textarea for collecting feedback for a positive rating.', 'required-wp-rating' ); ?>
                </label>
                <?php
            },
            $this->plugin_slug,
            'rplus_ratings_options_feedback'
        );

        add_settings_field(
            'rplus_ratings_options_feedback_negative',
            __( 'Negative', 'required-wp-rating' ),
            function() {
                ?>
                <label for="rplus_ratings_options_feedback_negative">
                    <input type="hidden" name="rplus_ratings_options_feedback_negative" value="0">
                    <input name="rplus_ratings_options_feedback_negative" type="checkbox" id="rplus_ratings_options_feedback_negative" value="1" <?php checked( '1', get_option('rplus_ratings_options_feedback_negative') ); ?>>
                    <?php _e( 'Yes, show a textarea for collecting feedback for a negative rating.', 'required-wp-rating' ); ?>
                </label>
            <?php
            },
            $this->plugin_slug,
            'rplus_ratings_options_feedback'
        );
    }

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}

    /**
     * Add meta boxes to activated post_types
     */
    public function add_meta_boxes() {

        // get post_type_select option and check for current post_type
        $selected = get_option( 'rplus_ratings_options_posttypes_select' );

        if ( ! is_array( $selected ) ) {
            return;
        }

        foreach ( $selected as $post_type => $active ) {

            // ignore post types that are not activated
            if ( $active != '1' ) continue;

            add_meta_box(
                'required-wp-rating-statistics',
                __( 'Ratings', 'required-wp-rating' ),
                array( $this, 'output_meta_box' ),
                $post_type,
                'normal'
            );

        }

    }

    /**
     * Output meta box content with rating statistics on backend edit forms
     *
     * @param $post
     */
    public function output_meta_box( $post ) {

        $positives = get_post_meta( $post->ID, 'rplus_ratings_positive', true );
        $negatives = get_post_meta( $post->ID, 'rplus_ratings_negative', true );

        printf( __( '<p><strong>Positive: </strong>%d, <strong>Negative: </strong>%d</p>', 'required-wp-rating' ), $positives, $negatives );

        // get all ratings for this post and show infos as a table
        $args = array(
            'post_type' => RplusWpRating::get_instance()->get_post_type(),
            'meta_query' => array(
                array(
                    'key' => 'vote_for_post_id',
                    'value' => $post->ID,
                    'compare' => '='
                )
            )
        );

        $the_query = new WP_Query( $args );

        if ( $the_query->have_posts() ) {
            while ( $the_query->have_posts() ) {
                $the_query->the_post();
                // todo: show infos, incl. custom fields (feedback)

            }
        } else {
            // no posts found
        }
        /* Restore original Post Data */
        wp_reset_postdata();

    }

}
