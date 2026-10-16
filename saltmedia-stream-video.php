<?php
/**
 * Plugin Name:       Salt Media Stream Video
 * Description:       Cloudflare Stream HLS video for Elementor: a "Stream video background" option on containers and a "Stream Video" widget. Paste any Stream link or ID (or use an ACF dynamic tag); videos play adaptively via HLS with quality caps, posters and lazy start.
 * Version:           0.1.1
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Salt Media
 * License:           GPL-2.0-or-later
 * Text Domain:       saltmedia-stream-video
 *
 * Bundles hls.js (Apache-2.0), see assets/vendor/LICENSE-hls.js.txt.
 */

defined( 'ABSPATH' ) || exit;

define( 'SM_STREAM_VERSION', '0.1.1' );
define( 'SM_STREAM_FILE', __FILE__ );
define( 'SM_STREAM_DIR', plugin_dir_path( __FILE__ ) );
define( 'SM_STREAM_URL', plugin_dir_url( __FILE__ ) );
define( 'SM_STREAM_HLS_VERSION', '1.7.3' );

require_once SM_STREAM_DIR . 'includes/class-stream-source.php';
require_once SM_STREAM_DIR . 'includes/class-settings.php';

SM_Stream_Settings::init();

add_action( 'init', function () {
	wp_register_script(
		'sm-stream-video',
		SM_STREAM_URL . 'assets/js/stream-video.js',
		array(),
		SM_STREAM_VERSION,
		array( 'in_footer' => true, 'strategy' => 'defer' )
	);
	wp_add_inline_script(
		'sm-stream-video',
		'window.smStreamVideo=' . wp_json_encode(
			array( 'hls' => SM_STREAM_URL . 'assets/vendor/hls.light.min.js?ver=' . SM_STREAM_HLS_VERSION )
		) . ';',
		'before'
	);
	wp_register_style( 'sm-stream-video', SM_STREAM_URL . 'assets/css/stream-video.css', array(), SM_STREAM_VERSION );
} );

// Everything else needs Elementor.
add_action( 'elementor/init', function () {
	require_once SM_STREAM_DIR . 'includes/class-container-background.php';
	SM_Stream_Container_Background::init();

	add_action( 'elementor/widgets/register', function ( $widgets_manager ) {
		require_once SM_STREAM_DIR . 'includes/class-widget-stream-video.php';
		$widgets_manager->register( new SM_Stream_Video_Widget() );
	} );
} );
