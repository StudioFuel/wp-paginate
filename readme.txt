=== WP-Paginate ===
Contributors: emartin24 
Donate link: http://www.ericmmartin.com/donate/
Tags: paginate, pagination, navigation, page, wp-paginate, comments
Requires at least: 2.2.0 (2.7.0 for comments pagination)
Tested up to: 2.8.4
Stable tag: 1.1
	
WP-Paginate is a simple and flexible pagination plugin which provides users with better navigation on your WordPress site.

== Description ==

WP-Paginate is a simple and flexible pagination plugin which provides users with better navigation on your WordPress site.

In addition to increasing the user experience for your visitors, it has also been widely reported that pagination increases the SEO of your site by providing more links to your content.

Starting in version 1.1, WP-Paginate can be used to paginate posts as well as post comments!
	
== Installation ==

*Install and Activate*

1. Unzip the downloaded WP-Paginate zip file
2. Upload the `wp-paginate` folder and its contents into the `wp-content/plugins/` directory of your WordPress installation
3. Activate WP-Paginate from Plugins page

*Implement*

For posts pagination:
1) Open the theme files where you'd like pagination to be used. Usually this is the index.php, archive.php and search.php files.

2) Replace your existing `previous_posts_link()` and `next_posts_link()` code block with the following:

	<?php if(function_exists('wp_paginate')) {
		wp_paginate();
	} ?>

For comments pagination:
1) Open the theme file(s) where you'd like comments pagination to be used. Usually this is the comments.php file.

2) Replace your existing `previous_comments_link()` and `next_comments_link()` code block with the following:

	<?php if(function_exists('wp_paginate_comments')) {
		wp_paginate_comments();
	} ?>

*Configure*

1) Configure the WP-Paginate settings, if necessary, from the WP-Paginate option in the Settings menu

2) The styles can be changed with the following methods:

* Add a `wp-paginate.css` file in your theme's directory and place your custom CSS there
* Add your custom CSS to your theme's `styles.css`
* Modify the `wp-paginate.css` file in the wp-paginate plugin directory

*Note:* The first two options will ensure that WP-Paginate updates will not overwrite your custom styles.  

== Frequently Asked Questions ==

= How can I override the default pagination settings? =

The `wp_paginate()` and `wp_paginate_comments()` functions each takes one optional argument, in query string format, which allows you to override the global settings. The available options are:

* title - The text to display before the pagination links
* nextpage - The text to use for the next page link
* previouspage - The text to use for the previous page link
* before - The HTML or text to add before the pagination links
* after - The HTML or text to add after the pagination links
* empty - Display the markup code even when the page list is empty
* range - The number of page links to show before and after the current page
* anchor - The number of links to always show at beginning and end of pagination
* gap - The minimum number of pages before a gap is replaced with ellipses (...)

You can even control the current page and number of pages with:

* page - The current page. This function will automatically determine the value
* pages - The total number of pages. This function will automatically determine the value

Example (also applies to `wp_paginate_comments()`):

	<?php if(function_exists('wp_paginate')) {
		wp_paginate('range=4&anchor=2&nextpage=Next&previouspage=Previous');
	} ?>

= How can I style the comments pagination differently than the posts pagination? =

There are a number of ways to do this, but basically, you'll want to override the default styles.

For example, you could do the following:

1) Modify the `wp_paginate_comments()` call:

	<?php if(function_exists('wp_paginate_comments')) {
		wp_paginate_comments('before=<div class="wp-paginate-comments">');
	} ?>
	
2) Add CSS to override the default element styles:
	
	.wp-paginate-comments {}
	.wp-paginate-comments ol {}
	.wp-paginate-comments li {}
	.wp-paginate-comments a {}
	.wp-paginate-comments a:hover, .wp-paginate-comments a:active {}
	.wp-paginate-comments .title {}
	.wp-paginate-comments .gap {}
	.wp-paginate-comments .current {}
	.wp-paginate-comments .page {}
	.wp-paginate-comments .prev, .wp-paginate .next {}


== Screenshots ==

1. An example of the WP-Paginate display using the default options and styling
2. The WP-Paginate admin settings page

== Changelog ==

= 1.1 =
* Added `wp_paginate_comments()` function for pagination of post comments

= 1.0.1 =
* Added I18n folder and wp-paginate.pot file
* Fixed some internationalization and spelling errors
* Updated readme.txt and added more details

= 1.0 =
* Initial release