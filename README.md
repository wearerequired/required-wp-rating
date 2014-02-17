# required+ wp rating
**Contributors**: [hubeRsen](https://github.com/hubeRsen), [neverything](https://github.com/neverything)

**Tags:** rating

**Stable tag:** 1.0.0

**License:** GPLv2 or later

**License URI:** http://www.gnu.org/licenses/gpl-2.0.html

WordPress plugin to make ratings (with optional comments) on every post / page.

# Description

# Customisation: Hooks & Filters
## Sending feedback action
You can add custom code, when a feedback is sent for a ratings. Example code below
```php
add_action( 'rplus_wp_rating_send_feedback', function( $rating_id, $feedback, $post_id ) {

    // send feedback to admin
    wp_mail( 'admin@wordpress', 'New Rating Feedback', $feedback);

}, 10, 3 );
```
### Filters
With these filters you can change some default text's that are used in this plugin

- rplus_wp_rating/filter/messages/alreadyvoted: Error message displayed when a user tries to make a second rating on a post/page
- rplus_wp_rating/filter/messages/error: Generic error message in case of technical hicups.
- rplus_wp_rating/filter/messages/missing_rating_id: Message that is shown when a rating feedback gets send without a proper rating_id (hack attack?)
- rplus_wp_rating/filter/messages/empty_feedback: Message that will be shown when no feedback is filled in.
- rplus_wp_rating/filter/messages/feedback_thx: Successfull vote and feedback was saved message.

# Updates

This plugin supports the [GitHub Updater](https://github.com/afragen/github-updater) plugin, so if you install that, this plugin becomes automatically updateable direct from GitHub. Any submission to WP.org repo will make this redundant.

# required+
[required+](http://required.ch) is a network of experienced web professionals from Switzerland and Germany. We focus on Interaction Design, Mobile Web, WordPress and some other things.