<?php
/**
 * Bits shared by the container background and the widget.
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Modules\DynamicTags\Module as TagsModule;
use Elementor\Plugin;

class SM_Stream_Elementor_Helpers {

	public static function quality_options() {
		return array(
			'auto' => __( 'Auto (fits the player size)', 'saltmedia-stream-video' ),
			'240'  => __( 'Up to 240p', 'saltmedia-stream-video' ),
			'360'  => __( 'Up to 360p', 'saltmedia-stream-video' ),
			'480'  => __( 'Up to 480p', 'saltmedia-stream-video' ),
			'720'  => __( 'Up to 720p', 'saltmedia-stream-video' ),
			'1080' => __( 'Up to 1080p', 'saltmedia-stream-video' ),
			'1440' => __( 'Up to 1440p', 'saltmedia-stream-video' ),
			'2160' => __( 'Up to 4K', 'saltmedia-stream-video' ),
		);
	}

	/** Non-desktop devices get an explicit "inherit" choice, which is their default. */
	public static function quality_device_args() {
		$inherit = array( '' => __( 'Same as larger screen', 'saltmedia-stream-video' ) ) + self::quality_options();
		$args    = array();
		foreach ( self::devices() as $device ) {
			if ( 'desktop' !== $device ) {
				$args[ $device ] = array( 'options' => $inherit, 'default' => '' );
			}
		}
		return $args;
	}

	/** Active Elementor devices, e.g. widescreen, desktop, laptop, tablet, mobile. */
	public static function devices() {
		return Plugin::$instance->breakpoints->get_active_devices_list( array( 'reverse' => true ) );
	}

	/** Collects a responsive control's value for every device, keyed by device. */
	public static function responsive_values( array $settings, $control ) {
		$values = array();
		foreach ( self::devices() as $device ) {
			$key              = 'desktop' === $device ? $control : $control . '_' . $device;
			$values[ $device ] = isset( $settings[ $key ] ) ? (string) $settings[ $key ] : '';
		}
		return $values;
	}

	public static function source_control_args( $label ) {
		return array(
			'label'       => $label,
			'type'        => Controls_Manager::TEXT,
			'label_block' => true,
			'placeholder' => __( 'Stream link or video ID', 'saltmedia-stream-video' ),
			'description' => __( 'Paste a Cloudflare Stream link (watch, iframe or manifest), a video ID, or an .m3u8 URL. Use the dynamic tag icon to pull it from an ACF field.', 'saltmedia-stream-video' ),
			'dynamic'     => array(
				'active'     => true,
				'categories' => array( TagsModule::TEXT_CATEGORY, TagsModule::URL_CATEGORY ),
			),
			'ai'          => array( 'active' => false ),
		);
	}

	public static function poster_control_args() {
		return array(
			'label'   => __( 'Poster', 'saltmedia-stream-video' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'stream',
			'options' => array(
				'stream' => __( 'Stream thumbnail', 'saltmedia-stream-video' ),
				'custom' => __( 'Custom image', 'saltmedia-stream-video' ),
				'none'   => __( 'None', 'saltmedia-stream-video' ),
			),
		);
	}

	public static function poster_image_control_args() {
		return array(
			'label'   => __( 'Poster image', 'saltmedia-stream-video' ),
			'type'    => Controls_Manager::MEDIA,
			'dynamic' => array( 'active' => true ),
		);
	}

	/**
	 * Poster URL for the chosen poster mode. Stream thumbnails are sized to the
	 * largest quality cap in use (720 when "auto"), so the poster isn't oversized.
	 */
	public static function poster_url( array $source, $mode, $image, array $quality ) {
		if ( 'none' === $mode ) {
			return '';
		}
		if ( 'custom' === $mode ) {
			return ! empty( $image['url'] ) ? $image['url'] : '';
		}
		$caps   = array_filter( array_map( 'intval', $quality ) );
		$height = $caps ? min( 1080, max( $caps ) ) : 720;
		return SM_Stream_Source::poster_url( $source, $height );
	}

	public static function enqueue() {
		wp_enqueue_script( 'sm-stream-video' );
		wp_enqueue_style( 'sm-stream-video' );
	}
}
