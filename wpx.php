<?php
/*
 * Plugin Name: WP Extend Utilities
 * Plugin URI: http://www.dquinn.net/wp-extend/
 * Description: Utility functions for the WPX Theme.
 * Version: 0.0.1
 * Author: Daniel Quinn
 * Author URI: http://www.dquinn.net
 * License: GPL2
 * GitHub Plugin URI: https://github.com/alkah3st/wpx-utility/
 * @package wpx-utility
 * @author Daniel Quinn <daniel@dquinn.net>
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU Public License GPL-2.0+
 * @link http://dquinn.net/wpx/ WP Exten Utilities on DQuinn.net
 * @copyright Copyright (c) 2015, Daniel Quinn
*/

/*
Copyright 2015 Daniel Quinn (email: daniel@dquinn.net)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

namespace WPX\Utility;

// if this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// installation, deactivation, uninstallation hooks
register_activation_hook(__FILE__, '\WPX\Utility\activate');
register_deactivation_hook(__FILE__, '\WPX\Utility\deactivate');

/**
 * Activation
 *
 * @todo Make this work for Multisite.
 * @since 1.0
*/
function activate() {}

/**
 * Deactivation
 * 
 * @todo Make this work for Multisite.
 * @since 1.0
*/
function deactivate() {}

/**
 * Helper Functions
 *
 * @package WordPress
 * @subpackage WPX_Theme
 * @since WPX Theme 0.1.0
 */
namespace WPX\Utility;

/**
 * Partition Array
 * @param Array $list
 * @param int $p
 * @return multitype:multitype:
 * @link http://www.php.net/manual/en/function.array-chunk.php#75022
 */
function partition($array, $segmentCount) {
	$dataCount = count($array);
	if ($dataCount == 0) return false;
	$segmentLimit = ceil($dataCount / $segmentCount);
	$outputArray = array();

	while($dataCount > $segmentLimit) {
	    $outputArray[] = array_splice($array,0,$segmentLimit);
	    $dataCount = count($array);
	}
	if($dataCount > 0) $outputArray[] = $array;

	return $outputArray;
}

/**
* Crossload Image
*
* Given an image URL, uploads the image to the Media Library 
* and then returns the uploaded file's attachment ID.
* 
*/
function sideload_image($image_url) {

	require_once(ABSPATH . 'wp-admin/includes/media.php');
	require_once(ABSPATH . 'wp-admin/includes/file.php');
	require_once(ABSPATH . 'wp-admin/includes/image.php');

	$tmp = download_url( $image_url );

	$file_array = array(
		'name' => basename( $image_url ),
		'tmp_name' => $tmp
	);

	// Check for download errors
	if ( is_wp_error( $tmp ) ) {
		@unlink( $file_array[ 'tmp_name' ] );
		return false;
	}

	$id = media_handle_sideload( $file_array, 0 );

	// Check for handle sideload errors.
	if ( is_wp_error( $id ) ) {
		@unlink( $file_array['tmp_name'] );
		return false;
	}

	return $id;
}

/**
 * Modest Video
 * @param  [type] $oembed ACF oEmbed.
 *
 * Outputs YouTube or Vimeo videos with minimal branding
 * and makes JS APIs active.
 */
function modest_video($oembed) {

	preg_match('/youtube.com/', $oembed, $is_youtube);
	preg_match('/vimeo.com/', $oembed, $is_vimeo);

	$player_id = 'video_'.rand(5, 1500);

	if ($is_vimeo) {
		$params = array(
			'portrait'    => 0,
			'title'        => 0,
			'byline'    => 0,
			'badge' => 0,
			'api'=>1,
			'player_id'=>$player_id
		);
	} elseif ($is_youtube) {
		$params = array(
			'modestbranding'    => 1,
			'rel'        => 0,
			'showinfo'    => 0,
			'wmode'=>'transparent',
			'html5'=>1,
			'enablejsapi'=>1
		);
	}

	if ($is_vimeo || $is_youtube) {
		preg_match('/src="(.+?)"/', $oembed, $matches);
		$src = $matches[1];
		$new_src = add_query_arg($params, $src);
		$oembed = str_replace($src, $new_src, $oembed);
		$attributes = 'frameborder="0"';

		if ($is_vimeo) {
			$oembed = str_replace('></iframe>', ' ' . $attributes . ' width="1600" height="700" class="is-vimeo" id="'.$player_id.'"></iframe>', $oembed);
		} else {
			$oembed = str_replace('></iframe>', ' ' . $attributes . ' width="1600" height="700" class="is-youtube"></iframe>', $oembed);
		}
		
		return $oembed;
	} else {
		return $oembed;
	}
}

/**
 * Get Attachment
 *
 * Gets an attachment by ID.
 */
function wp_get_attachment( $attachment_id ) {

	$attachment = get_post( $attachment_id );
	return array(
		'alt' => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
		'caption' => $attachment->post_excerpt,
		'description' => $attachment->post_content,
		'href' => get_permalink( $attachment->ID ),
		'src' => $attachment->guid,
		'title' => $attachment->post_title
	);
}

/**
 * Get Post By Slug
 *
 * Gets a post object by its slug.
 * 
 */
function get_post_by_slug($post_name) {
	global $wpdb;
	$post = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = %s", $post_name));
	return $post ? get_post($post) : NULL;
}

/**
 * Friendly Datetime
 *
 * (From the original WP source)
 * 
 */
function get_time_since($older_date, $newer_date = false) {
	// array of time period chunks
	$chunks = array(
	array(60 * 60 * 24 * 365 , 'year'),
	array(60 * 60 * 24 * 30 , 'month'),
	array(60 * 60 * 24 * 7, 'week'),
	array(60 * 60 * 24 , 'day'),
	array(60 * 60 , 'hour'),
	array(60 , 'minute'),
	);
	
	// $newer_date will equal false if we want to know the time elapsed between a date and the current time
	// $newer_date will have a value if we want to work out time elapsed between two known dates
	$newer_date = ($newer_date == false) ? (time()+(60*60*get_option("gmt_offset"))) : $newer_date;
	
	// difference in seconds
	$since = $newer_date - $older_date;
	
	// we only want to output two chunks of time here, eg:
	// x years, xx months
	// x days, xx hours
	// so there's only two bits of calculation below:

	// step one: the first chunk
	for ($i = 0, $j = count($chunks); $i < $j; $i++)
		{
		$seconds = $chunks[$i][0];
		$name = $chunks[$i][1];

		// finding the biggest chunk (if the chunk fits, break)
		if (($count = floor($since / $seconds)) != 0)
			{
			break;
			}
		}

	// set output var
	$output = ($count == 1) ? '1 '.$name : "$count {$name}s";

	// step two: the second chunk
	if ($i + 1 < $j)
		{
		$seconds2 = $chunks[$i + 1][0];
		$name2 = $chunks[$i + 1][1];
		
		if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0)
			{
			// add to output var
			$output .= ($count2 == 1) ? ', 1 '.$name2 : ", $count2 {$name2}s";
			}
		}
	
	return $output;
}

/**
* Get First Term
*
* Gets the primary term from a given taxonomy on a post.
* 
*/
function get_single_term($taxonomy, $post=false) {
	if (!$post) {
		global $post;
	}
	if ($taxonomy) {
		$terms = get_the_terms( $post->ID, $taxonomy );
		if ($terms) {
			$reset_terms = array_values($terms);
			if ($reset_terms) {
				$single_term = array_shift($reset_terms);
				return $single_term;
			} else {
				return false;
			}
		} else {
			return false;
		}
	} else {
		return false;
	}
}

/**
* Get Image
*
* Takes an attachment ID and returns an HTTP path to the image
* after cropping it to the specified crop size.
*
*/
function get_image($image_id=false, $crop_size=false) {
	$image = false;
	if ($image_id) $image = \WPX\Utility\resize($image_id, $crop_size);
	return $image;
}

/**
 * Resize
 *
 * Returns the resized URL of a given attachment ID
 */
function resize($attachment_id, $crop_size="full") {
	$image_object = wp_get_attachment_image_src( $attachment_id, $crop_size );
	return $image_object[0];
}

/**
 * Get Menu by Location
 *
 * Retrieves menu object by location.
 */
function get_menu_by_location( $location ) {
	if( empty($location) ) return false;

	$locations = get_nav_menu_locations();
	if( ! isset( $locations[$location] ) ) return false;

	$menu_obj = get_term( $locations[$location], 'nav_menu' );

	return $menu_obj;
}

/**
 * Truncate
 *
 * A very simple function that limits a given block of text to the specified length.
 *
 * @since 1.0
 * @param string $text
 * @param string $limit
 * @param string $break The character to break on (typically a space)
 * @return string
 *
*/
function truncate($text, $limit, $break) {
	$size = strlen(strip_tags($text));
	if ($size > $limit) {
		$text = $text." ";
		$text = substr($text,0,$limit);
		$text = substr($text,0,strrpos($text,' '));
		$text = $text.$break;
	}
	return $text;
}

/**
* Get Excerpt by ID
*
* Sometimes the functions get_excerpt() and the_excerpt() are not helpful, because they both only work in the Loop
* and return different markup (get_excerpt() strips HTML, while the_excerpt() returns content
* wrapped in p tags). This function will let you generate an excerpt by passing a post object:
*
* If there is a manually entered post_excerpt, it will return the content of the post_excerpt raw. Any markup
* entered into the Excerpt meta box will be returned as well, and you can use apply_filters('the_content', $output);
* on the output to render the content as you would the_excerpt().
*
* If there is no manual excerpt, the function will get the post_content, apply the_content filter, 
* escape and filter out all HTML, then truncate the excerpt to a specified length. 
*
* @since 1.0
* @param object $object
* @param int $length
*
*/
function get_excerpt_by_id($object, $length = 55) {
	if ($object->post_excerpt) {
		return $object->post_excerpt;
	} else {
		$output = $object->post_content;
		$output = apply_filters('the_content', $output);
		$output = str_replace('\]\]\>', ']]&gt;', $output);
		$output = strip_tags($output);
		$excerpt_length = 55;
		$words = explode(' ', $output, $length + 1);
		if (count($words)> $length) {
			array_pop($words);
			array_push($words, '');
			$output = implode(' ', $words);
		}
		return $output.'...';
	}
}

/**
* Get Page by Template
*
* Returns an object of the first page with the given
* template.
*/
function get_page_by_template($template) {
	$pages = get_posts(array(
		"post_type" => "page",
		"meta_key" => "_wp_page_template",
		"posts_per_page"=>1,
		"meta_value" => $template
	));
	if ($pages) {
		foreach($pages as $page){
			$object = $page;
		}
		if ($object) return $object;
	} else {
		return false;
	}
}

/**
 * Get Ancestor ID
 *
 * Retrieve the ID of the ancestor of the given object.
 *
 * @since 1.0
 * @param object $object The post we're checking ancestors for.
 * @return string
 *
*/
function get_ancestor_id($object = null) {
	if (!$object) {
		global $post;
		$object = $post;
	}
	$ancestor = get_post_ancestors( $object );
	if (empty($ancestor)) {
		$ancestor = array($object->ID);
	}
	$ancestor = end($ancestor);
	return $ancestor;
}

/**
 * Extend Custom Walker
 *
 * Adds the slug to each menu item.
 * Needs to be called via new \WPX\Utility\custom_nav_walker().
 * 
 */
class custom_nav_walker extends \Walker_Nav_Menu {
	// add main/sub classes to li's and links
	function start_el( &$output, $item, $depth = 0, $args = array(), $id=0) {

		global $wp_query;

		$indent = ( $depth > 0 ? str_repeat( "\t", $depth ) : '' ); // code indent
		// depth dependent classes
		$depth_classes = array(
			( $depth == 0 ? 'main-menu-item' : 'sub-menu-item' ),
			( $depth >=2 ? 'sub-sub-menu-item' : '' ),
			( $depth % 2 ? 'menu-item-odd' : 'menu-item-even' ),
			'menu-item-depth-' . $depth
		);
		$depth_class_names = esc_attr( implode( ' ', $depth_classes ) );

		// passed classes
		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$class_names = esc_attr( implode( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item ) ) );

		// build html
		$output .= $indent . '<li id="nav-menu-item-'. $item->ID . '" class="menu-'. sanitize_title($item->title) .' ' . $depth_class_names . ' ' . $class_names . '">';

		// link attributes
		$attributes  = ! empty( $item->attr_title ) ? ' title="'  . esc_attr( $item->attr_title ) .'"' : '';
		$attributes .= ! empty( $item->target )     ? ' target="' . esc_attr( $item->target     ) .'"' : '';
		$attributes .= ! empty( $item->xfn )        ? ' rel="'    . esc_attr( $item->xfn        ) .'"' : '';
		$attributes .= ! empty( $item->url )        ? ' href="'   . esc_attr( $item->url        ) .'"' : '';
		$attributes .= ' class="menu-link ' . ( $depth > 0 ? 'sub-menu-link' : 'main-menu-link' ) . '"';

		$item_output = sprintf( '%1$s<a%2$s>%3$s%4$s%5$s</a>%6$s',
			$args->before,
			$attributes,
			$args->link_before,
			apply_filters( 'the_title', $item->title, $item->ID ),
			$args->link_after,
			$args->after
		);

		// build html
		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args, $id );
	}
}

/**
 * Exclude Trackbacks
 *
 * Does not count trackbacks toward comment totals. 
 * (This needs to be called as an action from the theme.)
 * 
 */
function exclude_trackbacks( $count ) {
	global $id;
	$comments = get_approved_comments($id);
	$comment_count = 0;
	foreach($comments as $comment){
		if($comment->comment_type == ""){
			$comment_count++;
		}
	}
	return $comment_count;
}

/**
 * Filter Video Shortcode
 *
 * Wraps oEmbeds in FitVids container.
 * 
 */
function responsive_videos($html, $url, $attr) {
	
	// exclude tweets from this
	$check_url = parse_url($url);

	if($check_url['host'] == 'twitter.com') {
		return $html;
	} else {
		return '<div class="flex-video">'.$html.'</div>';
	}
	
}
add_filter('embed_oembed_html', '\WPX\Utility\responsive_videos', 10, 3);

/**
 * WP List Pages Walker
 *
 * Adds slug to list pages function.
 * Needs to be called via new \WPX\Utility\list_pages_walker().
 * 
 */
class list_pages_walker extends \Walker_page {

	function start_el(&$output, $page, $depth = 0, $args = array(), $current_page = 0) {
		if ( $depth ) {
			$indent = str_repeat("\t", $depth);
		} else {
		$indent = '';
	}

	extract($args, EXTR_SKIP);
	$css_class = array('page_item', 'page-item-'.$page->ID);
	if ( !empty($current_page) ) {
		$_current_page = get_page( $current_page );
		get_post_ancestors($_current_page);
		if ( isset($_current_page->ancestors) && in_array($page->ID, (array) $_current_page->ancestors) )
			$css_class[] = 'current_page_ancestor';
		if ( $page->ID == $current_page )
			$css_class[] = 'current_page_item';
		elseif ( $_current_page && $page->ID == $_current_page->post_parent )
			$css_class[] = 'current_page_parent';
		} elseif ( $page->ID == get_option('page_for_posts') ) {
		$css_class[] = 'current_page_parent';
	}

	$css_class = implode( ' ', apply_filters( 'page_css_class', $css_class, $page, $depth, $args, $current_page ) );

	$output .= $indent . '<li class="' . $css_class . ' '.$page->post_name.'"><a href="' . get_permalink($page->ID) . '">' . $link_before . apply_filters( 'the_title', $page->post_title ) . $link_after .'</a>';

	if ( !empty($show_date) ) {
		if ( 'modified' == $show_date )
			$time = $page->post_modified;
		else
			$time = $page->post_date;

		$output .= " " . mysql2date($date_format, $time);
		}
	}
}

/**
 * Multisite Template Inheritor
 *
 * Checks if there are child templates for the current subsite,
 * then returns that template instead of the currently loaded one.
 * 
*/
function template_lookup($template) {

	if (!is_multisite()) return $template;

	// then get the root path for the subsite
	// this corresponds to the directory
	$subsite_path = \WPX\Utility\get_subsite_path();

	// construct subsite path for template
	if (strpos($template, '/templates/') !== false) {
		// this is a template in the /templates/ folder
		$subsite_template = WPX_THEME_PATH.'/subsites/'.$subsite_path.'/templates/'.basename($template);

	} else {
		// this is a root directory template
		$subsite_template = WPX_THEME_PATH.'/subsites/'.$subsite_path.'/'.basename($template);

	}

	// check if file exists
	if ( file_exists($subsite_template) ) {

		return $subsite_template;
	
	// or return default
	} else {

		return $template;

	}


}
add_action( 'template_include', '\WPX\Utility\template_lookup', 99);

/**
 * Get Subsite Part
 *
 * Handles pathing for locating subsite templates.
 *  
 */
function get_subsite_part($path) {

	if (!is_multisite()) return false;

	// gets the slug of the subsite we are in
	$subsite_path = \WPX\Utility\get_subsite_path();

	// construct subsite path for template
	if (strpos($path, '/templates/') !== false) {
		
		// this is a template in the /templates/ folder
		$subsite_template = WPX_THEME_PATH.'/subsites/'.$subsite_path.'/templates/'.basename($path);

	} else if (strpos($path, '/parts/') !== false) {

		// this is a template in the /templates/ folder
		$subsite_template = WPX_THEME_PATH.'/subsites/'.$subsite_path.'/parts/'.basename($path);

	} else {

		// this is a root directory template
		$subsite_template = WPX_THEME_PATH.'/subsites/'.$subsite_path.'/'.basename($path);

	}

	// check if this file exists and return it
	if ( file_exists($subsite_template) ) {

		require($subsite_template);

	// otherwise return the standard file
	} else {

		require($path);

	}

}

/**
 * Get Subsite Path
 *
 * Gets the slug/path of the current subsite.
 * 
 */
function get_subsite_path() {

	if (!is_multisite()) return false;

	// first, determine which subsite we are on
	$current_subsite = get_blog_details();

	// get the subsite's slug
	$subsite_path = $current_subsite->path;

	// the first blog is always rescue
	if ($subsite_path == '/') {
		$subsite_path = "default";
	} else {
		// otherwise strip slashes and return blog slug
		$subsite_path = str_replace("/","", $subsite_path);
	}

	return $subsite_path;

}

/**
 * Get Mixed Terms
 *
 * Returns an array of terms from multiple taxonomies when given a post ID.
 * 
 */
function get_mixed_terms($id, $taxonomies) {

	$term_set = array();

	foreach($taxonomies as $taxonomy) {
		$terms = get_the_terms( $id, $taxonomy );
		if ($terms) {
			$term_set = array_merge($terms, $term_set);
		}
	}

	if ($term_set) :

		usort($term_set, function($a, $b) {
			return strcmp($a->name, $b->name);
		});

		return $term_set;
		
	else :

		return false;

	endif;
}

/**
 * Get Mixed Term List
 *
 * Returns links to terms from multiple taxonomies, separated by a comma.
 * 
 */
function get_mixed_term_list($id, $taxonomies) {

	$term_set = \WPX\Utility\get_mixed_terms($id, $taxonomies);

	if ($term_set) :

		$list = '';

		foreach($term_set as $i=>$term) :

			if ($i < count($term_set)-1) :

				$list .= '<a href="'.get_term_link( $term, $term->taxonomy ).'">'.$term->name.'</a>, ';

			else :

				$list .= '<a href="'.get_term_link( $term, $term->taxonomy ).'">'.$term->name.'</a>';

			endif;

		endforeach;

		return $list;

	else :

		return false;

	endif;

}

/**
 * Zip Merge Array
 *
 * Merges two arrays, alternating keys.
 * 
 */
function array_zip_merge() {
	$output = array();
	// The loop incrementer takes each array out of the loop as it gets emptied by array_shift().
	for ($args = func_get_args(); count($args); $args = array_filter($args)) {
		// &$arg allows array_shift() to change the original.
		foreach ($args as &$arg) {
			$output[] = array_shift($arg);
		}
	}
	return $output;
}

/**
* Expiration Check
*
* Determines whether a date is expired or not.
*
*/
function is_expired($expiration_utc) {
	$now = new \DateTime('now');
	$now = $now->format('U');
	$expiration = $expiration_utc;
	if ($now > $expiration) {
		return true;
	} else { 
		return false;
	}
}