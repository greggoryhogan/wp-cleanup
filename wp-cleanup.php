<?php
/*
Plugin Name:  WP Cleanup
Plugin URI:	  https://mynameisgregg.com/
Description:  Remove the unneccessary bloat WordPress adds. Use with caution, this is very specific to installations by Gregg Hogan
Version:	  1.0.0
Author:		  Gregg Hogan
Author URI:   https://mynameisgregg.com
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  wpcu
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove theme file editing from wp admin
 */	
if(!defined('DISALLOW_FILE_EDIT')) {
    define( 'DISALLOW_FILE_EDIT', true );
}

/**
 * Remove Generator tag and feeds
 */
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'feed_links_extra', 3 );
remove_action('wp_head', 'feed_links', 2 );

/**
 * Remove Windows Live Writer Manifest
 */
remove_action('wp_head', 'wlwmanifest_link');

/**
 * Remove Gutenberg from Backend
 */
add_filter( 'use_block_editor_for_post', '__return_false' );

/**
 * Disable Gutenberg for widgets.
 */ 
add_filter( 'use_widgets_blog_editor', '__return_false' );

/**
 * Remove Gutenberg Frontend Scripts
 */
function wpcu_disable_gutenberg() {
    // Remove CSS on the front end.
    wp_dequeue_style( 'wp-block-library' );
    // Remove Gutenberg theme.
    wp_dequeue_style( 'wp-block-library-theme' );
    // Remove inline global CSS on the front end.
    wp_dequeue_style( 'global-styles' );
}
add_action( 'wp_enqueue_scripts', 'wpcu_disable_gutenberg', 20 );

/**
 * Remove Gutenberg Block Library from Frontend
 */
function wpcu_remove_wp_block_library_css(){
    wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'wp-block-library-theme' );
    wp_dequeue_style( 'wc-blocks-style' ); // Remove WooCommerce block CSS if it exists
} 
add_action( 'wp_enqueue_scripts', 'wpcu_remove_wp_block_library_css', 100 );

/**
 * Remove Gutenberg default css
 */
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );

/**
 * Hide REST from non-registered users
 */
//add_filter( 'rest_authentication_errors', 'wpcu_hide_rest');
function wpcu_hide_rest( $result ) {
    if ( ! empty( $result ) ) {
        return $result;
    }
    if ( ! is_user_logged_in() ) {
        return new WP_Error( 'rest_not_logged_in', 'You are not currently logged in.', array( 'status' => 401 ) );
    }
    return $result;
}

/**
 * Remove WP Customizer from Admin Interface since we don't use it, based on https://github.com/parallelus/customizer-remove-all-parts
 */
add_action( 'admin_init', 'remove_customizer_admin_init', 10 );
function remove_customizer_admin_init() {
    // Drop some customizer actions
    remove_action( 'plugins_loaded', '_wp_customize_include', 10);
    remove_action( 'admin_enqueue_scripts', '_wp_customize_loader_settings', 11);
    // Manually overrid Customizer behaviors
    add_action( 'load-customize.php', function() {
        // If accessed directly
        wp_die( __( 'The Customizer is currently disabled.', 'henry' ) );
    });
}

/**
 * Remove customizer capability from admin roles since we don't use it, based on https://github.com/parallelus/customizer-remove-all-parts
 */
add_action( 'init', 'remove_capability', 10 ); 
function remove_capability() {
    // Remove customize capability
	add_filter( 'map_meta_cap', function($caps = array(), $cap = '', $user_id = 0, $args = array()) {
        if ($cap == 'customize') {
            return array('nope'); // thanks @ScreenfeedFr, http://bit.ly/1KbIdPg
        }
        return $caps;
    }, 10, 4 );
    //remove tags from posts
    unregister_taxonomy_for_object_type('post_tag', 'post');
}

/**
 * Disable the emoji's
 */
function disable_wpcu_emojis() {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' ); 
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' ); 
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    add_filter( 'tiny_mce_plugins', 'disable_wpcu_emojis_tinymce' );
    add_filter( 'wp_resource_hints', 'disable_wpcu_emojis_remove_dns_prefetch', 10, 2 );
}
add_action( 'init', 'disable_wpcu_emojis' );
   
/**
* Filter function used to remove the tinymce emoji plugin.
*/
function disable_wpcu_emojis_tinymce( $plugins ) {
    if ( is_array( $plugins ) ) {
        return array_diff( $plugins, array( 'wpemoji' ) );
    } else {
        return array();
    }
}
   
/**
* Remove emoji CDN hostname from DNS prefetching hints.
*/
function disable_wpcu_emojis_remove_dns_prefetch( $urls, $relation_type ) {
    if ( 'dns-prefetch' == $relation_type ) {
        $emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' );
        $urls = array_diff( $urls, array( $emoji_svg_url ) );
    }
    return $urls;
}
function remove_wpcu_trackbacks_pingbacks() {
    //remove trackbacks
    remove_meta_box('trackbacksdiv', 'post', 'normal');
    //remove slug metabox
    remove_meta_box( 'slugdiv', 'post', 'normal' );
    remove_meta_box( 'slugdiv', 'page', 'normal' );
}
add_action('add_meta_boxes', 'remove_wpcu_trackbacks_pingbacks');