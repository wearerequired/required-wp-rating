# required+ wp rating
**Contributors**: [hubeRsen](https://github.com/hubeRsen), [neverything](https://github.com/neverything)
**Tags:** rating
**Stable tag:** 1.0.0
**License:** GPLv2 or later
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html

WordPress plugin to make ratings (with optional comments) on every post / page.

# Description

# Hooks & Filters
## Sending feedback action
You can add custom code, when a feedback is sent for a ratings. Example code below
```php
add_action( 'rplus_wp_rating_send_feedback', function( $rating_id, $feedback, $post_id ) {

    // send feedback to admin
    wp_mail( 'admin@wordpress', 'New Rating Feedback', $feedback);

}, 10, 3 );
```

# Updates

This plugin supports the [GitHub Updater](https://github.com/afragen/github-updater) plugin, so if you install that, this plugin becomes automatically updateable direct from GitHub. Any submission to WP.org repo will make this redundant.
