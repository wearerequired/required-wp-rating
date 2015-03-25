# WP Team List #
**Contributors:** wearerequired, hubeRsen, neverything, swissspidy  
**Donate link:** http://required.ch/  
**Tags:** rating, widget  
**Requires at least:** 3.5.1  
**Tested up to:** 4.2  
**Stable tag:** 1.0.0  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

WordPress plugin to make ratings (with optional comments) on every post / page.

## Description ##

Adds rating functionality to WordPress so visitors can upvote/downvote each post with an optional comment.

This simple plugin allows you to disable any Sidebar and Dashboard Widget for the current WordPress site you are on. It provides a simple user interface available to users with `edit_theme_options` capabilities (usually Administrator role) available under Appearance -> Disable Widgets.
After saving the settings, it removes the Sidebar and Dashboard Widgets selected.

**Developer? Get to know the hooks**

You can add custom code when a feedback is sent for a rating. Example code below
```php
add_action( 'rplus_wp_rating_send_feedback', function( $rating_id, $feedback, $post_id ) {
    // send feedback to admin
    wp_mail( 'admin@example.com', 'New Rating Feedback', $feedback);
}, 10, 3 );
```

First of all, this plugin is strucuted on the shoulders of the fantastic [WordPress Plugin Boilerplate](https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/) by [Tom McFarlin](http://profiles.wordpress.org/tommcfarlin/), you might could use this too for your next plugin.

Let's have a look at the filters we provide:

* `rplus_wp_rating/filter/messages/alreadyvoted`: Error message displayed when a user tries to make a second rating on a post/page
* `rplus_wp_rating/filter/messages/error`: Generic error message in case of technical hicups.
* `rplus_wp_rating/filter/messages/missing_rating_id`: Message that is shown when a rating feedback gets send without a proper rating_id (hack attack?)
* `rplus_wp_rating/filter/messages/empty_feedback`: Message that will be shown when no feedback is filled in.
* `rplus_wp_rating/filter/messages/feedback_thx`: Successfull vote and feedback was saved message.

**Contributions**

If you would like to contribute to this plugin, report an isse or anything like that, please note that we develop this plugin on [GitHub](https://github.com/wearerequired/WP-Widget-Disable).

Developed by [required+](http://required.ch/ "Team of experienced web professionals from Switzerland & Germany"), a network of experienced web professionals from Switzerland and Germany. We focus on Interaction Design, Mobile Web, WordPress and some other things.

## Installation ##

Here is how you install WP Ratings.

**Uploading in WordPress Dashboard**

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `required-wp-rating.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard

**Using FTP**

1. Download `required-wp-rating.zip`
2. Extract the `required-wp-rating` directory to your computer
3. Upload the `required-wp-rating` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard

## Screenshots ##

No Screenshots yet.

## Changelog ##

### 1.1.0 ###
* New: Ratings are properly deleted on plugin uninstall.
* New: Composer support.
* Enhancement: Code cleanup! We're doing more with less code now.

### 1.0.0 ###
* Initial release of the working plugin.
* German (de_DE) translations added.

## Upgrade Notice ##

### 1.1.0 ###
**We now properly delete data on plugin unstaill. Also:** Composer support!  

### 1.0.1 ###
I18n improvements

### 1.0.0 ###
Initial Release