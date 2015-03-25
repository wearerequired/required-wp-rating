<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   Plugin_Name
 * @author    Your Name <email@example.com>
 * @license   GPL-2.0+
 * @link      http://example.com
 * @copyright 2014 Your Name or Company Name
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/** @var WP_Post[] $posts */
$posts = get_posts( array( 'post_type' => 'rplus_rating', 'posts_per_page' => - 1 ) );
/** @var WP_Post $post */
foreach ( $posts as $post ) {
	wp_delete_post( $post->ID, true );
}