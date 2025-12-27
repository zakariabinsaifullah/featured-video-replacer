<?php
/**
 * Plugin Name: Binsaif Featured Video Replacer
 * Description: Allow you to use Featured Video in place of Featured Image.
 * Version: 1.0.0
 * Author: Binsaifullah
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: binsaif-featured-video-replacer
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Include the admin page class.
require __DIR__ . '/classes/class-bfvr-admin-page.php';

// Initialize the admin page.
if ( is_admin() ) {
	new BFVR_Admin_Page();
}

// If no other BFVR_HTML_Tag_Processor class exists, add it.
if ( ! class_exists( 'BFVR_HTML_Tag_Processor' ) ) {
	require __DIR__ . '/classes/class-bfvr-html-tag-processor.php';
}

/**
 * Enqueues the editor script for the block editor.
 *
 * @return void
 */
function bfvr_featured_video_enqueue_editor_assets() {

	if ( ! is_readable( __DIR__ . '/build/editor/editor.asset.php' ) || ! is_readable( __DIR__ . '/build/editor/editor.js' ) ) {
		return;
	}

	$asset_meta = include __DIR__ . '/build/editor/editor.asset.php';

	wp_enqueue_script(
		'bfvr-featured-video-editor-script',
		plugin_dir_url( __FILE__ ) . 'build/editor/editor.js',
		$asset_meta['dependencies'] ?? array(),
		$asset_meta['version'] ?? get_plugin_data( __FILE__ )['Version'],
		true
	);

	// Localize script with the post meta key.
	wp_localize_script(
		'bfvr-featured-video-editor-script',
		'BFVRSettings',
		array(
			'enabledPostTypes' => BFVR_Admin_Page::get_enabled_post_types(),
		)
	);

	// translate script
	wp_set_script_translations(
		'bfvr-featured-video-editor-script',
		'binsaif-featured-video-replacer',
		plugin_dir_path( __FILE__ ) . 'languages'
	);
}
add_action( 'enqueue_block_editor_assets', 'bfvr_featured_video_enqueue_editor_assets' );


/**
 * Register the custom post meta.
 *
 * @return void
 */
function bfvr_featured_video_register_post_meta() {
	$enabled_post_types = BFVR_Admin_Page::get_enabled_post_types();
	
	foreach ( $enabled_post_types as $post_type ) {
		register_post_meta(
			$post_type,
			'_bfvr_featured_video_id',
			array(
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
				'type'              => 'number',
				'single'            => true,
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
add_action( 'init', 'bfvr_featured_video_register_post_meta' );

/**
 * Render the featured video in place of the post featured image.
 *
 * @param string   $block_content The block content.
 * @param array    $block         The block attributes.
 * @param WP_Block $wp_block      The WP_Block instance.
 *
 * @return string The updated block content with the featured video.
 */
function bfvr_featured_video_render_post_featured_image( $block_content, $block, $wp_block ) {

	if ( empty( $wp_block->context['postId'] ) ) {
		return $block_content;
	}

	$featured_video_id = get_post_meta( $wp_block->context['postId'], '_bfvr_featured_video_id', true );

	if ( ! $featured_video_id ) {
		return $block_content;
	}

	$featured_video_url = wp_get_attachment_url( $featured_video_id );
	if ( ! $featured_video_url ) {
		return $block_content;
	}

	$p = new BFVR_HTML_Tag_Processor( $block_content );
	if ( ! $p->next_tag( array( 'class_name' => 'wp-post-image' ) ) ) {
		return $block_content;
	}

	$p->replace_tag(
		sprintf(
			'<video class="attachment-post-thumbnail size-post-thumbnail wp-post-image wp-post-video intrinsic-ignore" autoplay muted loop playsinline src="%s" style="width: 100%%" preload="metadata"><p>%s</p></video>',
			esc_url( $featured_video_url ),
			esc_html__( 'Your browser does not support the video tag.', 'binsaif-featured-video-replacer' )
		)
	);

	return $p->get_updated_html();
}
add_filter( 'render_block_core/post-featured-image', 'bfvr_featured_video_render_post_featured_image', 10, 3 );
