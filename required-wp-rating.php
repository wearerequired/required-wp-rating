<?php
/**
 * required+ WordPress Ratings
 *
 * A plugin to make ratings on all types of wordpress posts and pages. Includes a widget and a Shortcode.
 *
 * @package   required-wp-rating
 * @author    Stefan Pasch <stefan@required.ch>
 * @license   GPL-2.0+
 * @link      http://required.ch
 * @copyright 2014 required gmbh
 *
 * @wordpress-plugin
 * Plugin Name:       required+ WP Ratings
 * Plugin URI:        https://github.com/wearerequired/required-wp-rating
 * Description:       Make ratings on all types of wordpress posts and pages. Includes a widget and a Shortcode.
 * Version:           1.1.0
 * Author:            required+
 * Author URI:        http://required.ch
 * Text Domain:       rpluswpratings
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/wearerequired/required-wp-rating
 * GitHub Branch:     master
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once( plugin_dir_path( __FILE__ ) . 'public/RplusWpRating.php' );

/**
 * Initialize the plugins base class
 */
add_action( 'plugins_loaded', array( 'RplusWpRating', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/RplusWpRatingAdmin.php' );
	add_action( 'plugins_loaded', array( 'RplusWpRatingAdmin', 'get_instance' ) );

}