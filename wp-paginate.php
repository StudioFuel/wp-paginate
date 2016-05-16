<?php
/*
Plugin Name: WP-Paginate
Plugin URI: http://www.studiofuel.com/wp-paginate/
Description: A simple and flexible pagination plugin for WordPress posts and comments.
Version: 2.0
Author: Noah Cinquini, EX additions by Matthew Sigley
Author URI: http://www.studiofuel.com, https://github.com/msigley
*/

/*  Copyright 2014 Studio Fuel (http://www.studiofuel.com)

    Plugin originally created by Eric Martin (http://www.ericmmartin.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

/**
 * Set the wp-content and plugin urls/paths
 */
if (!defined('WP_CONTENT_URL'))
	define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
if (!defined('WP_CONTENT_DIR'))
	define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
if (!defined('WP_PLUGIN_URL') )
	define('WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins');
if (!defined('WP_PLUGIN_DIR') )
	define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');

if (!class_exists('WPPaginate')) {
	class WPPaginate {
		/**
		 * @var string The plugin version
		 */
		var $version = '1.3.1';

		/**
		 * @var string The options string name for this plugin
		 */
		var $optionsName = 'wp_paginate_options';

		/**
		 * @var string $localizationDomain Domain used for localization
		 */
		var $localizationDomain = 'wp-paginate';

		/**
		 * @var string $pluginurl The url to this plugin
		 */
		var $pluginurl = '';
		/**
		 * @var string $pluginpath The path to this plugin
		 */
		var $pluginpath = '';

		/**
		 * @var array $options Stores the options for this plugin
		 */
		var $options = array();

		var $type = 'posts';

		/**
		 * Constructor
		 */
		function __construct() {
			$name = dirname(plugin_basename(__FILE__));

			//Language Setup
			load_plugin_textdomain($this->localizationDomain, false, "$name/I18n/");

			//"Constants" setup
			$this->pluginurl = plugins_url($name) . "/";
			$this->pluginpath = WP_PLUGIN_DIR . "/$name/";

			//Initialize the options
			$this->get_options();

			//Actions
			add_action('admin_menu', array(&$this, 'admin_menu_link'));

			if ($this->options['css'])
				add_action('wp_print_styles', array(&$this, 'wp_paginate_css'));
		}

		/**
		 * Pagination based on options/args
		 */
		function paginate($args = false) {
			if ($this->type === 'comments' && !get_option('page_comments'))
				return;

			$r = wp_parse_args($args, $this->options);
			extract($r, EXTR_SKIP);

			if (!isset($page) && !isset($pages)) {
				global $wp_query;

				if ($this->type === 'posts') {
					$page = get_query_var('paged');
					$posts_per_page = intval(get_query_var('posts_per_page'));
					$pages = intval(ceil($wp_query->found_posts / $posts_per_page));
				}
				else {
					$page = get_query_var('cpage');
					$comments_per_page = get_option('comments_per_page');
					$pages = get_comment_pages_count();
				}
				$page = !empty($page) ? intval($page) : 1;
			}

			$prevlink = ($this->type === 'posts')
				? esc_url(get_pagenum_link($page - 1))
				: get_comments_pagenum_link($page - 1);
			$nextlink = ($this->type === 'posts')
				? esc_url(get_pagenum_link($page + 1))
				: get_comments_pagenum_link($page + 1);

			$output = stripslashes($before);
			if ($pages > 1) {
				$output .= sprintf('<ol class="wp-paginate%s">', ($this->type === 'posts') ? '' : ' wp-paginate-comments');
				if (strlen(stripslashes($title)) > 0) {
					$output .= sprintf('<li><span class="title">%s</span></li>', stripslashes($title));
				}
				$ellipsis = "<li><span class='gap'>...</span></li>";

				if ($page > 1 && !empty($previouspage)) {
					$output .= sprintf('<li><a href="%s" class="prev">%s</a></li>', $prevlink, stripslashes($previouspage));
				}

				$min_links = $range * 2 + 1;
				$block_min = min($page - $range, $pages - $min_links);
				$block_high = max($page + $range, $min_links);
				$left_gap = (($block_min - $anchor - $gap) > 0) ? true : false;
				$right_gap = (($block_high + $anchor + $gap) < $pages) ? true : false;

				if ($left_gap && !$right_gap) {
					$output .= sprintf('%s%s%s',
						$this->paginate_loop(1, $anchor),
						$ellipsis,
						$this->paginate_loop($block_min, $pages, $page)
					);
				}
				else if ($left_gap && $right_gap) {
					$output .= sprintf('%s%s%s%s%s',
						$this->paginate_loop(1, $anchor),
						$ellipsis,
						$this->paginate_loop($block_min, $block_high, $page),
						$ellipsis,
						$this->paginate_loop(($pages - $anchor + 1), $pages)
					);
				}
				else if ($right_gap && !$left_gap) {
					$output .= sprintf('%s%s%s',
						$this->paginate_loop(1, $block_high, $page),
						$ellipsis,
						$this->paginate_loop(($pages - $anchor + 1), $pages)
					);
				}
				else {
					$output .= $this->paginate_loop(1, $pages, $page);
				}

				if ($page < $pages && !empty($nextpage)) {
					$output .= sprintf('<li><a href="%s" class="next">%s</a></li>', $nextlink, stripslashes($nextpage));
				}
				$output .= "</ol>";
			}
			$output .= stripslashes($after);

			if ($pages > 1 || $empty) {
				echo $output;
			}
		}

		/**
		* Provides markup for a list of linked page numbers
		*
		* @param array           $options The options
		* - **after**: `</div>` Markup to add after the pagination
		* - **before**: `<div>` Markup to add before the pagination
		* - **jumpback**: `(number of page being jumped to)` The label for the jump backward link
		* - **jumpfwd**: `(number of page being jumped to)` The label for the jump forward link
		* - **jumps**: `(half number of pages shown)` Number of pages to jump.
		* - **label**: `Pages:` The label for the pagination list
		* - **next**: `next` The label for the next button
		* - **previous**: `previous` The label for the previous button
		* - **first**: `1` The label for the first page button.
		* - **last**: `(number of the last page)` The label for the last page button.
		* - **show**: `1000` The maximum number of pages to show
		* - **class**: `paging` The class applied to the ul
		* - **classactive**: `active` The class applied to the currently active list item
		* - **classdisabled**: `disabled` The class applied to any disabled list items
		* - **classprevious**: `previous` The class applied to the previous list item
		* - **classnext**: `next` The class applied to the next list items
		* - **page**: `(paged query var from $wp_query)` The current page
		* - **pages**: `(value calculated from $wp_query)` The total number of pages
		* @return string The pagination markup
		**/
		function paginate_ex ( $options ) {
			global $wp_query;
			
			$defaults = array(
				'after' => '</div>',
				'before' => '<div>',
				'jumpback' => '',
				'jumpfwd' => '',
				'jumps' => false,
				'label' => __('Pages:','wp_paginate'),
				'next' => __('next','wp_paginate'),
				'previous' => __('previous','wp_paginate'),
				'first' => '1',
				'last' => '',
				'show' => 1000,
				'class' => sprintf('wp-paginate-ex%s', ($this->type === 'posts') ? '' : ' wp-paginate-ex-comments'),
				'classactive' => 'active',
				'classdisabled' => 'disabled',
				'classprevious' => 'previous',
				'classnext' => 'next'
			);
			$options = array_merge($defaults, $options);
			extract($options);
			
			if (!isset($page) && !isset($pages)) {
				global $wp_query;

				if ($this->type === 'posts') {
					$page = get_query_var('paged');
					$posts_per_page = intval(get_query_var('posts_per_page'));
					$pages = intval(ceil($wp_query->found_posts / $posts_per_page));
				}
				else {
					$page = get_query_var('cpage');
					$comments_per_page = get_option('comments_per_page');
					$pages = get_comment_pages_count();
				}
				$page = !empty($page) ? intval($page) : 1;
			}
			
			if( empty($last) ) $last = $pages;
			
			$_ = array();
			if ( $pages > 1 ) {
				if ( $pages > $show ) $visible_pages = $show;
				else $visible_pages = $pages;
				if( empty($jumps) )
					$jumps = ceil( $visible_pages / 2 );
				$_[] = $before . $label;
				$_[] = '<ul class="' . esc_attr($class) . '">';
				if ( $page <= floor( $show / 2) ) {
					$i = 1;
				} else {
					$i = $page - floor( $show / 2 );
					$visible_pages = $page + floor( $show / 2 );
					if ( $visible_pages > $pages ) $visible_pages = $pages;
					if ( $i > 1 ) {
						$link = $this->paginate_link(1);
						$_[] = '<li class="first"><span><a href="' . $link . '">' . $first . '</a></span></li>';
						$pagenum = ( $page - $jumps );
						if ( $pagenum > 1 ) { //Only show jump back if different than first
						$link = $this->paginate_link($pagenum);
							if( empty($jumpback) )
								$jumpback = $pagenum;
							$_[] = '<li class="jumpback"><span><a href="' . $link . '">' . $jumpback . '</a></span></li>';
						}
					}
				}
				// Add previous button
				if ( ! empty($previous) && $page > 1 ) {
					$prev = $page-1;
					$link = $this->paginate_link($prev);
					$_[] = '<li class="' . esc_attr($classprevious) . '"><span><a href="' . $link . '" rel="prev">' . $previous . '</a></span></li>';
				} else $_[] = '<li class="' . esc_attr($classprevious) . ' ' . esc_attr($classdisabled) . '"><span>' . $previous . '</span></li>';
				// end previous button
				while ( $i <= $visible_pages ) {
					$link = $this->paginate_link($i);
					if ( $i == $page ) $_[] = '<li class="' . esc_attr($classactive) . '"><span>' . $i . '</span></li>';
					else $_[] = '<li><span><a href="' . $link . '">' . $i . '</a></span></li>';
					$i++;
				}
				// Add next button
				if ( ! empty($next) && $page < $pages) {
					$pagenum = $page + 1;
					$link = $this->paginate_link($pagenum);
					$_[] = '<li class="' . esc_attr($classnext) . '"><span><a href="' . $link . '" rel="next">' . $next . '</a></span></li>';
				} else $_[] = '<li class="' . esc_attr($classnext) . ' ' . esc_attr($classdisabled) . '"><span>' . $next . '</span></li>';
				// end next button
				if ( $pages > $visible_pages  ) {
					$pagenum = ( $page + $jumps );
					if ( $pagenum < $pages ) { //Only show jump forward if different than last
						$link = $this->paginate_link($pagenum);
						if( empty($jumpfwd) )
								$jumpfwd = $pagenum;
						$_[] = '<li class="jumpfwd"><span><a href="' . $link . '">' . $jumpfwd . '</a></span></li>';
					}
					$link = $this->paginate_link($pages);
					$_[] = '<li class="last"><span><a href="' . $link . '">' . $last . '</a></span></li>';
				}
				$_[] = '</ul>';
				$_[] = $after;
			}
			
			echo join('', $_);
		}

		/**
		 * Helper function for pagination which builds the page links.
		 */
		function paginate_loop($start, $max, $page = 0) {
			$output = "";
			for ($i = $start; $i <= $max; $i++) {
				$p = $this->paginate_link($i);
				$output .= ($page == intval($i))
					? "<li><span class='page current'>$i</span></li>"
					: "<li><a href='$p' title='$i' class='page'>$i</a></li>";
			}
			return $output;
		}
		
		function paginate_link($pagenum) {
			return ($this->type === 'posts') ? esc_url(get_pagenum_link($pagenum)) : get_comments_pagenum_link($pagenum);
		}

		function wp_paginate_css() {
			$name = "wp-paginate.css";

			if (false !== @file_exists(STYLESHEETPATH . "/$name")) {
				$css = get_stylesheet_directory_uri() . "/$name";
			}
			else {
				$css = $this->pluginurl . $name;
			}
			wp_enqueue_style('wp-paginate', $css, false, $this->version, 'screen');

			if (function_exists('is_rtl') && is_rtl()) {
				$name = "wp-paginate-rtl.css";
				if (false !== @file_exists(STYLESHEETPATH . "/$name")) {
					$css = get_stylesheet_directory_uri() . "/$name";
				}
				else {
					$css = $this->pluginurl . $name;
				}
				wp_enqueue_style('wp-paginate-rtl', $css, false, $this->version, 'screen');
			}
		}

		/**
		 * Retrieves the plugin options from the database.
		 * @return array
		 */
		function get_options() {
			if (!$options = get_option($this->optionsName)) {
				$options = array(
					'title' => 'Pages:',
					'nextpage' => '&raquo;',
					'previouspage' => '&laquo;',
					'css' => true,
					'before' => '<div class="navigation">',
					'after' => '</div>',
					'empty' => true,
					'range' => 3,
					'anchor' => 1,
					'gap' => 3
				);
				update_option($this->optionsName, $options);
			}
			$this->options = $options;
		}
		/**
		 * Saves the admin options to the database.
		 */
		function save_admin_options(){
			return update_option($this->optionsName, $this->options);
		}

		/**
		 * @desc Adds the options subpanel
		 */
		function admin_menu_link() {
			add_options_page('WP-Paginate', 'WP-Paginate', 'manage_options', basename(__FILE__), array(&$this, 'admin_options_page'));
			add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(&$this, 'filter_plugin_actions'), 10, 2 );
		}

		/**
		 * @desc Adds the Settings link to the plugin activate/deactivate page
		 */
		function filter_plugin_actions($links, $file) {
			$settings_link = '<a href="options-general.php?page=' . basename(__FILE__) . '">' . __('Settings', $this->localizationDomain) . '</a>';
			array_unshift($links, $settings_link); // before other links

			return $links;
		}

		/**
		 * Adds settings/options page
		 */
		function admin_options_page() {
			if (isset($_POST['wp_paginate_save'])) {
				if (wp_verify_nonce($_POST['_wpnonce'], 'wp-paginate-update-options')) {
					$this->options['title'] = $_POST['title'];
					$this->options['previouspage'] = $_POST['previouspage'];
					$this->options['nextpage'] = $_POST['nextpage'];
					$this->options['before'] = $_POST['before'];
					$this->options['after'] = $_POST['after'];
					$this->options['empty'] = (isset($_POST['empty']) && $_POST['empty'] === 'on') ? true : false;
					$this->options['css'] = (isset($_POST['css']) && $_POST['css'] === 'on') ? true : false;
					$this->options['range'] = intval($_POST['range']);
					$this->options['anchor'] = intval($_POST['anchor']);
					$this->options['gap'] = intval($_POST['gap']);

					$this->save_admin_options();

					echo '<div class="updated"><p>' . __('Success! Your changes were successfully saved!', $this->localizationDomain) . '</p></div>';
				}
				else {
					echo '<div class="error"><p>' . __('Whoops! There was a problem with the data you posted. Please try again.', $this->localizationDomain) . '</p></div>';
				}
			}
?>

<div class="wrap">
<div class="icon32" id="icon-options-general"><br/></div>
<h2>WP-Paginate</h2>
<form method="post" id="wp_paginate_options">
<?php wp_nonce_field('wp-paginate-update-options'); ?>
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e('Pagination Label:', $this->localizationDomain); ?></th>
			<td><input name="title" type="text" id="title" size="40" value="<?php echo stripslashes(htmlspecialchars($this->options['title'])); ?>"/>
			<span class="description"><?php _e('The text/HTML to display before the list of pages.', $this->localizationDomain); ?></span></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Previous Page:', $this->localizationDomain); ?></th>
			<td><input name="previouspage" type="text" id="previouspage" size="40" value="<?php echo stripslashes(htmlspecialchars($this->options['previouspage'])); ?>"/>
			<span class="description"><?php _e('The text/HTML to display for the previous page link.', $this->localizationDomain); ?></span></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Next Page:', $this->localizationDomain); ?></th>
			<td><input name="nextpage" type="text" id="nextpage" size="40" value="<?php echo stripslashes(htmlspecialchars($this->options['nextpage'])); ?>"/>
			<span class="description"><?php _e('The text/HTML to display for the next page link.', $this->localizationDomain); ?></span></td>
		</tr>
	</table>
	<p>&nbsp;</p>
	<h3><?php _e('Advanced Settings', $this->localizationDomain); ?></h3>
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e('Before Markup:', $this->localizationDomain); ?></th>
			<td><input name="before" type="text" id="before" size="40" value="<?php echo stripslashes(htmlspecialchars($this->options['before'])); ?>"/>
			<span class="description"><?php _e('The HTML markup to display before the pagination code.', $this->localizationDomain); ?></span></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('After Markup:', $this->localizationDomain); ?></th>
			<td><input name="after" type="text" id="after" size="40" value="<?php echo stripslashes(htmlspecialchars($this->options['after'])); ?>"/>
			<span class="description"><?php _e('The HTML markup to display after the pagination code.', $this->localizationDomain); ?></span></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Markup Display:', $this->localizationDomain); ?></th>
			<td><label for="empty">
				<input type="checkbox" id="empty" name="empty" <?php echo ($this->options['empty'] === true) ? "checked='checked'" : ""; ?>/> <?php _e('Show Before Markup and After Markup, even if the page list is empty?', $this->localizationDomain); ?></label></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('WP-Paginate CSS File:', $this->localizationDomain); ?></th>
			<td><label for="css">
				<input type="checkbox" id="css" name="css" <?php echo ($this->options['css'] === true) ? "checked='checked'" : ""; ?>/> <?php printf(__('Include the default stylesheet wp-paginate.css? WP-Paginate will first look for <code>wp-paginate.css</code> in your theme directory (<code>themes/%s</code>).', $this->localizationDomain), get_template()); ?></label></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Page Range:', $this->localizationDomain); ?></th>
			<td>
				<select name="range" id="range">
				<?php for ($i=1; $i<=10; $i++) : ?>
					<option value="<?php echo $i; ?>" <?php echo ($i == $this->options['range']) ? "selected='selected'" : ""; ?>><?php echo $i; ?></option>
				<?php endfor; ?>
				</select>
				<span class="description"><?php _e('The number of page links to show before and after the current page. Recommended value: 3', $this->localizationDomain); ?></span></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Page Anchors:', $this->localizationDomain); ?></th>
			<td>
				<select name="anchor" id="anchor">
				<?php for ($i=0; $i<=10; $i++) : ?>
					<option value="<?php echo $i; ?>" <?php echo ($i == $this->options['anchor']) ? "selected='selected'" : ""; ?>><?php echo $i; ?></option>
				<?php endfor; ?>
				</select>
				<span class="description"><?php _e('The number of links to always show at beginning and end of pagination. Recommended value: 1', $this->localizationDomain); ?></span></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Page Gap:', $this->localizationDomain); ?></th>
			<td>
				<select name="gap" id="gap">
				<?php for ($i=1; $i<=10; $i++) : ?>
					<option value="<?php echo $i; ?>" <?php echo ($i == $this->options['gap']) ? "selected='selected'" : ""; ?>><?php echo $i; ?></option>
				<?php endfor; ?>
				</select>
				<span class="description"><?php _e('The minimum number of pages in a gap before an ellipsis (...) is added. Recommended value: 3', $this->localizationDomain); ?></span></td>
		</tr>
	</table>
	<p class="submit">
		<input type="submit" value="Save Changes" name="wp_paginate_save" class="button-primary" />
	</p>
</form>
<h2><?php _e('Need Support?', $this->localizationDomain); ?></h2>
<p><?php printf(__('For questions, issues or feature requests, please post them in the %s and make sure to tag the post with wp-paginate.', $this->localizationDomain), '<a href="https://wordpress.org/support/plugin/wp-paginate">WordPress Forum</a>'); ?></p>
<h2><?php _e('Want To Contribute?', $this->localizationDomain); ?></h2>
<p><?php _e('If you would like to contribute, the following is a list of ways you can help:', $this->localizationDomain); ?></p>
<ul>
	<li>&raquo; <?php _e('Translate WP-Paginate into your language', $this->localizationDomain); ?></li>
	<li>&raquo; <?php _e('Blog about or link to WP-Paginate so others can find out about it', $this->localizationDomain); ?></li>
	<li>&raquo; <?php _e('Report issues, provide feedback, request features, etc.', $this->localizationDomain); ?></li>
	<li>&raquo; <a href="https://wordpress.org/support/view/plugin-reviews/wp-paginate"><?php _e('Review WP-Paginate on the WordPress Plugins Page', $this->localizationDomain); ?></a></li>
</ul>
<h2><?php _e('Other Links', $this->localizationDomain); ?></h2>
<ul>
	<li>&raquo; <a href='https://github.com/studiofuel/wp-paginate'>WP-Paginate</a> on GitHub</li>
	<li>&raquo; <a href="http://www.studiofuel.com/simplemodal-contact-form-smcf/">SimpleModal Contact Form (SMCF) - WordPress Plugin</a></li>
	<li>&raquo; <a href="http://www.studiofuel.com/simplemodal-login/">SimpleModal Login - WordPress Plugin</a></li>
</ul>
</div>

<?php
		}
	}
}

//instantiate the class
if (class_exists('WPPaginate')) {
	$wp_paginate = new WPPaginate();
}

/**
 * Pagination function to use for posts
 */
function wp_paginate($args = false) {
	global $wp_paginate;
	$wp_paginate->type = 'posts';
	return $wp_paginate->paginate($args);
}

/**
 * Pagination function to use for post comments
 */
function wp_paginate_comments($args = false) {
	global $wp_paginate;
	$wp_paginate->type = 'comments';
	return $wp_paginate->paginate($args);
}

/**
 * Improved pagination function to use for posts
 */
function wp_paginate_ex($args = array()) {
	global $wp_paginate;
	$wp_paginate->type = 'posts';
	return $wp_paginate->paginate_ex($args);
}

/**
 * Improved pagination function to use for post comments
 */
function wp_paginate_comments_ex($args = array()) {
	global $wp_paginate;
	$wp_paginate->type = 'comments';
	return $wp_paginate->paginate_ex($args);
}

/*
 * The format of this plugin is based on the following plugin template:
 * http://pressography.com/plugins/wordpress-plugin-template/
 */