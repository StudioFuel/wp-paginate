=== WP-Paginate ===
Contributors: emartin24 
Donate link: http://www.ericmmartin.com/donate/
Tags: paginate, pagination, navigation, page, wp-paginate
Requires at least: 2.2.0
Tested up to: 2.8.4
Stable tag: 1.0.1
	
WP-Paginate is a simple and flexible pagination plugin which provides users with better navigation for your WordPress site.

== Description ==

WP-Paginate is a simple and flexible pagination plugin which provides users with better navigation for your WordPress site.

In addition to increasing the usability for your visitors, it has also been widely reported that pagination increases the SEO of your site by providing more links to your content.
	
== Installation ==

*Install and Activate*

1. Unzip the downloaded WP-Paginate zip file
2. Upload the `wp-paginate` folder and its contents into the `wp-content/plugins/` directory of your WordPress installation
3. Activate WP-Paginate from Plugins page

*Implement*

1) Open the theme files where you'd like pagination to be used. Usually this is the index.php, archive.php and search.php

2) Replace your existing `previous_posts_link()` and `next_posts_link()` code block with the following:

	<?php if(function_exists("wp_paginate")) {wp_paginate();} ?>

*Configure*

1) Configure the WP-Paginate settings, if necessary, from the WP-Paginate option in the Settings menu

2) The styles can be changed with the following methods:

* Add a `wp-paginate.css` file in your theme's directory and place your custom CSS there
* Add your custom CSS to your theme's `styles.css`
* Modify the `wp-paginate.css` file in the wp-paginate plugin directory

*Note:* The first two options will ensure that WP-Paginate updates will not overwrite your custom styles.  

== Frequently Asked Questions ==

=How can I override the default pagination settings?=

The `wp_paginate()` function takes one optional argument, in query string format, which allows you to override the global settings. The available options are:

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

Example:

	<?php if(function_exists("wp_paginate")) {
		wp_paginate("range=4&anchor=2&nextpage=Next&previouspage=Previous");
	} ?>
 

== Screenshots ==

1. An example of the WP-Paginate display using the default options and styling
2. The WP-Paginate admin settings page

== Changelog ==

= 1.0.1 =
* Added I18n folder and wp-paginate.pot file
* Fixed some internationalization and spelling errors
* Updated readme.txt and added more details

= 1.0 =
* Initial release