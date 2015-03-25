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
	 * @since 1.0.0
	 * @var RplusWpRatingAdmin
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 *
	 */
	private function __construct() {
		/*
		 * Call $plugin_slug from public plugin class.
		 */
		$plugin            = RplusWpRating::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Load admin style sheet and JavaScript.
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

		$this->change_admin_columns();

		// Filter posts for custom column sorting
		add_filter( 'pre_get_posts', array( $this, 'pre_get_posts' ) );

		add_filter( 'get_meta_sql', array( $this, 'change_columns_order_sql' ) );
	}

	/**
	 * Add filters to selected post types to changes admin columns and content
	 */
	private function change_admin_columns() {

		// get post_type_select option and check for current post_type
		$selected = get_option( 'rplus_ratings_options_posttypes_select' );

		if ( ! is_array( $selected ) ) {
			return;
		}

		foreach ( $selected as $post_type => $active ) {

			// ignore post types that are not activated
			if ( $active != '1' ) {
				continue;
			}

			// in case for attachments, the correct filter name should be media
			$post_type = str_replace( 'attachment', 'media', $post_type );

			// modify admin list columns
			add_filter( "manage_edit-{$post_type}_columns", array( $this, 'admin_edit_columns' ) );

			// fill custom columns
			add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'admin_manage_columns' ), 10, 2 );

			add_filter( "manage_edit-{$post_type}_sortable_columns", array( $this, 'admin_sortable_columns' ) );

		}

	}

	/**
	 * WP-Admin Columns displayed for selected post types
	 *
	 * @param  array $columns Array of default columns
	 *
	 * @return array
	 */
	public function admin_edit_columns( $columns ) {

		$columns['rplusrating_positive'] = __( 'Positive Ratings', 'required-wp-rating' );
		$columns['rplusrating_negative'] = __( 'Negative Ratings', 'required-wp-rating' );

		return $columns;

	}

	/**
	 * WP-Admin Columns content displayed for selected post types
	 *
	 * @param  string $column  Name of the column defined in $this->admin_edit_columns();
	 * @param  int    $post_id WP_Post ID
	 *
	 * @return string
	 */
	public function admin_manage_columns( $column, $post_id ) {

		switch ( $column ) {

			// Display positive rating infos
			case 'rplusrating_positive':
				$positives = get_post_meta( $post_id, 'rplus_ratings_positive', true );
				printf( __( '%d', 'required-wp-rating' ), $positives );
				break;
			// Display negative rating infos
			case 'rplusrating_negative':
				$negatives = get_post_meta( $post_id, 'rplus_ratings_negative', true );
				printf( __( '%d', 'required-wp-rating' ), $negatives );
				break;

			// Don't show anything by default
			default:
				break;
		}

	}

	/**
	 * Filter the sortable columns.
	 *
	 * @param array $columns The columns that can be filtered.
	 *
	 * @return array
	 */
	public function admin_sortable_columns( $columns ) {
		$columns['rplusrating_negative'] = 'rplusrating_negative';
		$columns['rplusrating_positive'] = 'rplusrating_positive';

		return $columns;
	}

	/**
	 * Modify the query for the custom sorting.
	 *
	 * @param WP_Query $query
	 */
	public function pre_get_posts( $query ) {
		if ( ! is_admin() ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'rplusrating_negative' === $orderby ) {
			$query->set( 'meta_key', 'rplus_ratings_negative' );
			$query->set( 'orderby', 'meta_value_num' );
		} else if ( 'rplusrating_positive' === $orderby ) {
			$query->set( 'meta_key', 'rplus_ratings_positive' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	/**
	 * Filter the SQL clauses for the column sorting to include posts
	 * without any ratings.
	 *
	 * @param array $clauses The SQL clauses
	 *
	 * @return array
	 */
	public function change_columns_order_sql( $clauses ) {
		global $wp_query;

		if ( in_array( $wp_query->get( 'meta_key' ), array(
				'rplus_ratings_positive',
				'rplus_ratings_negative'
			) ) && 'meta_value_num' === $wp_query->get( 'orderby' )
		) {
			// Left Join so empty values will be returned as well
			$clauses['join']  = str_replace( 'INNER JOIN', 'LEFT JOIN', $clauses['join'] ) . $clauses['where'];
			$clauses['where'] = '';
		}

		return $clauses;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return RplusWpRatingAdmin
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_style( $this->plugin_slug . '-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), RplusWpRating::VERSION );
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), RplusWpRating::VERSION );
		}

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
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
		register_setting( $this->plugin_slug . '-options', 'rplus_ratings_options_title' );
		register_setting( $this->plugin_slug . '-options', 'rplus_ratings_options_btn_label_positive' );
		register_setting( $this->plugin_slug . '-options', 'rplus_ratings_options_btn_label_negative' );
		register_setting( $this->plugin_slug . '-options', 'rplus_ratings_options_posttypes_select' );
		register_setting( $this->plugin_slug . '-options', 'rplus_ratings_options_feedback_positive' );
		register_setting( $this->plugin_slug . '-options', 'rplus_ratings_options_feedback_negative' );
		register_setting( $this->plugin_slug . '-options', 'rplus_ratings_options_feedback_positive_descr' );
		register_setting( $this->plugin_slug . '-options', 'rplus_ratings_options_feedback_negative_descr' );
		register_setting( $this->plugin_slug . '-options', 'rplus_ratings_options_feedback_reply' );
		register_setting( $this->plugin_slug . '-options', 'rplus_ratings_options_feedback_reply_descr' );

		/**
		 * Check for Polylang, when exist, make the strings translatable here
		 */
		if ( function_exists( 'pll_register_string' ) ) {
			pll_register_string( 'rplus_ratings_title', get_option( 'rplus_ratings_options_title' ), 'required-wp-rating' );
			pll_register_string( 'rplus_ratings_btn_positive', get_option( 'rplus_ratings_options_btn_label_positive' ), 'required-wp-rating' );
			pll_register_string( 'rplus_ratings_btn_negative', get_option( 'rplus_ratings_options_btn_label_negative' ), 'required-wp-rating' );
			pll_register_string( 'rplus_ratings_feedback_positive_descr', get_option( 'rplus_ratings_options_feedback_positive_descr' ), 'required-wp-rating', true );
			pll_register_string( 'rplus_ratings_feedback_negative_descr', get_option( 'rplus_ratings_options_feedback_negative_descr' ), 'required-wp-rating', true );
			pll_register_string( 'rplus_ratings_feedback_btn_send', 'Send feedback', 'required-wp-rating' );
			pll_register_string( 'rplus_ratings_feedback_success', 'Your vote was saved.', 'required-wp-rating' );
			pll_register_string( 'rplus_ratings_feedback_alreadydone', 'You\'ve already made your rating for this page.', 'required-wp-rating' );
			pll_register_string( 'rplus_ratings_feedback_thx', 'Thank you for the feedback.', 'required-wp-rating' );
			pll_register_string( 'rplus_ratings_feedback_reply_descr', get_option( 'rplus_ratings_options_feedback_reply_descr' ), 'required-wp-rating' );
		}

		add_settings_section(
			'rplus_ratings_options_titles',
			__( 'Titles & Labels', 'required-wp-rating' ),
			function () {
				_e( 'Set default titles and labels.', 'required-wp-rating' );
			},
			$this->plugin_slug
		);

		add_settings_field(
			'rplus_ratings_options_title',
			__( 'Title', 'required-wp-rating' ),
			function () {
				?>
				<input name="rplus_ratings_options_title" class="regular-text" type="text" id="rplus_ratings_options_title" value="<?php echo get_option( 'rplus_ratings_options_title' ); ?>">
				<p class="description"><?php _e( 'Optional title to display above the rating controls.', 'required-wp-rating' ); ?></p>
			<?php
			},
			$this->plugin_slug,
			'rplus_ratings_options_titles'
		);

		add_settings_field(
			'rplus_ratings_options_btn_label_positive',
			__( 'Positive Button Label', 'required-wp-rating' ),
			function () {
				?>
				<input name="rplus_ratings_options_btn_label_positive" class="regular-text" type="text" id="rplus_ratings_options_btn_label_positive" value="<?php echo get_option( 'rplus_ratings_options_btn_label_positive' ); ?>">
				<p class="description"><?php _e( 'Button label for positive ratings. You can use <i>{count}</i> as a placeholder for already made ratings.', 'required-wp-rating' ); ?></p>
			<?php
			},
			$this->plugin_slug,
			'rplus_ratings_options_titles'
		);

		add_settings_field(
			'rplus_ratings_options_btn_label_negative',
			__( 'Negative Button Label', 'required-wp-rating' ),
			function () {
				?>
				<input name="rplus_ratings_options_btn_label_negative" class="regular-text" type="text" id="rplus_ratings_options_btn_label_negative" value="<?php echo get_option( 'rplus_ratings_options_btn_label_negative' ); ?>">
				<p class="description"><?php _e( 'Button label for negative ratings. You can use <i>{count}</i> as a placeholder for already made ratings.', 'required-wp-rating' ); ?></p>
			<?php
			},
			$this->plugin_slug,
			'rplus_ratings_options_titles'
		);

		add_settings_section(
			'rplus_ratings_options_posttypes',
			__( 'Display rating controls', 'required-wp-rating' ),
			function () {
				_e( 'Show rating controls below each post_type\'s content. The controls can be styled using css. See the plugin page for more info\'s. When you don\'t select any post type, the controls won\'t be displayed, but you can add them with the shortcode <strong>[rplus-rating]</strong>.', 'required-wp-rating' );
			},
			$this->plugin_slug
		);

		add_settings_field(
			'rplus_ratings_options_posttypes_select',
			__( 'Post Types', 'required-wp-rating' ),
			function () {
				$selected = get_option( 'rplus_ratings_options_posttypes_select' );
				foreach ( get_post_types( array( 'public' => true ) ) as $post_type ) {
					$post_type_labels = get_post_type_labels( get_post_type_object( $post_type ) );
					?>
					<p>
						<label for="rplus_ratings_options_posttypes_select_<?php echo $post_type; ?>">
							<input type="hidden" name="rplus_ratings_options_posttypes_select[<?php echo $post_type; ?>]" value="0">
							<input name="rplus_ratings_options_posttypes_select[<?php echo $post_type; ?>]" type="checkbox" id="rplus_ratings_options_posttypes_select_<?php echo $post_type; ?>" value="1" <?php echo( ( is_array( $selected ) && isset( $selected[ $post_type ] ) && $selected[ $post_type ] == '1' ) ? 'checked' : '' ); ?>>
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
			function () {
				_e( 'When activated, you\'ll see a textarea when you do a rating where you can add a feedback before sending.', 'required-wp-rating' );
			},
			$this->plugin_slug
		);

		add_settings_field(
			'rplus_ratings_options_feedback_positive',
			__( 'Positive', 'required-wp-rating' ),
			function () {
				?>
				<label for="rplus_ratings_options_feedback_positive">
					<input type="hidden" name="rplus_ratings_options_feedback_positive" value="0">
					<input name="rplus_ratings_options_feedback_positive" type="checkbox" id="rplus_ratings_options_feedback_positive" value="1" <?php checked( '1', get_option( 'rplus_ratings_options_feedback_positive' ) ); ?>>
					<?php _e( 'Yes, show a textarea for collecting feedback for a positive rating.', 'required-wp-rating' ); ?>
				</label>
			<?php
			},
			$this->plugin_slug,
			'rplus_ratings_options_feedback'
		);

		add_settings_field(
			'rplus_ratings_options_feedback_positive_descr',
			'',
			function () {
				?>
				<textarea name="rplus_ratings_options_feedback_positive_descr" id="rplus_ratings_options_feedback_positive_descr" rows="10" cols="50" class="regular-text"><?php echo get_option( 'rplus_ratings_options_feedback_positive_descr' ); ?></textarea>
				<p class="description"><?php _e( 'Optional description for positive feedbacks, will be displayed above the feedback form.', 'required-wp-rating' ); ?></p>
			<?php
			},
			$this->plugin_slug,
			'rplus_ratings_options_feedback'
		);

		add_settings_field(
			'rplus_ratings_options_feedback_negative',
			__( 'Negative', 'required-wp-rating' ),
			function () {
				?>
				<label for="rplus_ratings_options_feedback_negative">
					<input type="hidden" name="rplus_ratings_options_feedback_negative" value="0">
					<input name="rplus_ratings_options_feedback_negative" type="checkbox" id="rplus_ratings_options_feedback_negative" value="1" <?php checked( '1', get_option( 'rplus_ratings_options_feedback_negative' ) ); ?>>
					<?php _e( 'Yes, show a textarea for collecting feedback for a negative rating.', 'required-wp-rating' ); ?>
				</label>
			<?php
			},
			$this->plugin_slug,
			'rplus_ratings_options_feedback'
		);

		add_settings_field(
			'rplus_ratings_options_feedback_negative_descr',
			'',
			function () {
				?>
				<textarea name="rplus_ratings_options_feedback_negative_descr" id="rplus_ratings_options_feedback_negative_descr" rows="10" cols="50" class="regular-text"><?php echo get_option( 'rplus_ratings_options_feedback_negative_descr' ); ?></textarea>
				<p class="description"><?php _e( 'Optional description for negative feedbacks, will be displayed above the feedback form.', 'required-wp-rating' ); ?></p>
			<?php
			},
			$this->plugin_slug,
			'rplus_ratings_options_feedback'
		);

		add_settings_field(
			'rplus_ratings_options_feedback_reply',
			__( 'Ask for Reply', 'required-wp-rating' ),
			function () {
				?>
				<label for="rplus_ratings_options_feedback_reply">
					<input type="hidden" name="rplus_ratings_options_feedback_reply" value="0">
					<input name="rplus_ratings_options_feedback_reply" type="checkbox" id="rplus_ratings_options_feedback_reply" value="1" <?php checked( '1', get_option( 'rplus_ratings_options_feedback_reply' ) ); ?>>
					<?php _e( 'Yes, show a input field to let the user enter his email address, asking for feedback.', 'required-wp-rating' ); ?>
				</label>
			<?php
			},
			$this->plugin_slug,
			'rplus_ratings_options_feedback'
		);

		add_settings_field(
			'rplus_ratings_options_feedback_reply_descr',
			'',
			function () {
				?>
				<textarea name="rplus_ratings_options_feedback_reply_descr" id="rplus_ratings_options_feedback_reply_descr" rows="2" cols="50" class="regular-text"><?php echo get_option( 'rplus_ratings_options_feedback_reply_descr' ); ?></textarea>
				<p class="description"><?php _e( 'Optional decription for the email input field.', 'required-wp-rating' ); ?></p>
			<?php
			},
			$this->plugin_slug,
			'rplus_ratings_options_feedback'
		);
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since 1.0.0
	 *
	 * @param array $links Plugin action links.
	 *
	 * @return array
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
			if ( $active != '1' ) {
				continue;
			}

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
	 * @param WP_Post $post Post object.
	 */
	public function output_meta_box( $post ) {
		$positives = get_post_meta( $post->ID, 'rplus_ratings_positive', true );
		$negatives = get_post_meta( $post->ID, 'rplus_ratings_negative', true );

		echo "<p>";
		printf( __( '<strong>Positive: </strong>%d, <strong>Negative: </strong>%d.', 'required-wp-rating' ), $positives, $negatives );
		echo ' <a href="#" onclick="document.getElementById(\'rplus-wp-rating-details-container-' . $post->ID . '\').style.display=\'block\'; return false;">' . __( 'Show Details', 'required-wp-rating' ) . '</a>';
		echo "</p>";

		echo '<div id="rplus-wp-rating-details-container-' . $post->ID . '" style="display: none;"><hr>';

		// get all ratings for this post and show infos as a table
		$args = array(
			'post_type'  => RplusWpRating::get_instance()->get_post_type(),
			'meta_query' => array(
				array(
					'key'     => 'vote_for_post_id',
					'value'   => $post->ID,
					'compare' => '='
				)
			)
		);

		$the_query = new WP_Query( $args );

		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$the_query->the_post();

				echo '<p>';
				printf( __( '<strong>%s</strong> on <strong>%s</strong> at <strong>%s</strong>', 'required-wp-rating' ), ucfirst( get_post_meta( get_the_ID(), 'vote_type', true ) ), get_the_date(), get_the_time() );

				$feedback = get_post_meta( get_the_ID(), 'vote_feedback', true );
				if ( ! empty( $feedback ) && $feedback != 'null' ) {
					echo '<br>';
					_e( '<strong>Feedback:</strong> ', 'required-wp-rating' );
					echo $feedback;
				}

				$reply = get_post_meta( get_the_ID(), 'vote_reply_email', true );
				if ( ! empty( $reply ) && $reply != 'null' ) {
					echo '<br>';
					_e( '<strong>Reply to:</strong> ', 'required-wp-rating' );
					echo $reply;
				}
				echo "</p><hr>";

			}
		} else {
			// no posts found
		}

		echo '</div>';

		/* Restore original Post Data */
		wp_reset_postdata();
	}
}