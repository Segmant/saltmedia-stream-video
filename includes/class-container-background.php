<?php
/**
 * Adds "Stream video background" to Elementor containers (Style tab).
 *
 * Elementor's own background video only takes a single file URL, so this adds
 * a separate option. The video layer reuses Elementor's
 * .elementor-background-video-container class, so the container's background
 * overlay, borders and stacking behave exactly as with a native background video.
 */

defined( 'ABSPATH' ) || exit;

require_once SM_STREAM_DIR . 'includes/class-elementor-helpers.php';

use Elementor\Controls_Manager;
use Elementor\Element_Base;

class SM_Stream_Container_Background {

	/** One entry per container currently rendering: layer HTML, or null when the option is off. */
	private static $stack = array();

	public static function init() {
		add_action( 'elementor/element/container/section_background/after_section_end', array( __CLASS__, 'register_controls' ) );
		add_action( 'elementor/frontend/container/before_render', array( __CLASS__, 'before_render' ) );
		add_action( 'elementor/frontend/container/after_render', array( __CLASS__, 'after_render' ) );
	}

	public static function register_controls( Element_Base $element ) {
		$element->start_controls_section( 'sm_stream_bg_section', array(
			'label' => __( 'Stream video background', 'saltmedia-stream-video' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$element->add_control( 'sm_stream_bg', array(
			'label'       => __( 'Stream video background', 'saltmedia-stream-video' ),
			'type'        => Controls_Manager::SWITCHER,
			'description' => __( 'Plays a Cloudflare Stream video (HLS) behind this container. Leave the normal Background type off "Video" when using this.', 'saltmedia-stream-video' ),
		) );

		$on = array( 'sm_stream_bg' => 'yes' );

		$element->add_control( 'sm_stream_bg_source', array_merge(
			SM_Stream_Elementor_Helpers::source_control_args( __( 'Video', 'saltmedia-stream-video' ) ),
			array( 'condition' => $on )
		) );

		$element->add_responsive_control( 'sm_stream_bg_quality', array(
			'label'       => __( 'Max quality', 'saltmedia-stream-video' ),
			'type'        => Controls_Manager::SELECT,
			'options'     => SM_Stream_Elementor_Helpers::quality_options(),
			'default'     => 'auto',
			'device_args' => SM_Stream_Elementor_Helpers::quality_device_args(),
			'description' => __( 'Caps the resolution per screen size, e.g. 360p for small cards. Auto picks the best quality for the size shown.', 'saltmedia-stream-video' ),
			'condition'   => $on,
		) );

		$element->add_control( 'sm_stream_bg_play_on_mobile', array(
			'label'       => __( 'Play on mobile', 'saltmedia-stream-video' ),
			'type'        => Controls_Manager::SWITCHER,
			'description' => __( 'Off: phones show the poster only and download no video.', 'saltmedia-stream-video' ),
			'condition'   => $on,
		) );

		$element->add_control( 'sm_stream_bg_play_once', array(
			'label'     => __( 'Play once', 'saltmedia-stream-video' ),
			'type'      => Controls_Manager::SWITCHER,
			'condition' => $on,
		) );

		$element->add_control( 'sm_stream_bg_poster', array_merge(
			SM_Stream_Elementor_Helpers::poster_control_args(),
			array( 'condition' => $on )
		) );

		$element->add_control( 'sm_stream_bg_poster_image', array_merge(
			SM_Stream_Elementor_Helpers::poster_image_control_args(),
			array( 'condition' => $on + array( 'sm_stream_bg_poster' => 'custom' ) )
		) );

		$element->end_controls_section();
	}

	public static function before_render( Element_Base $element ) {
		$layer = self::layer_html( $element );
		self::$stack[] = $layer;
		if ( null !== $layer ) {
			ob_start();
		}
	}

	public static function after_render( Element_Base $element ) {
		$layer = array_pop( self::$stack );
		if ( null === $layer ) {
			return;
		}

		$html = ob_get_clean();

		// Put the layer straight after the container's opening tag, where Elementor
		// puts its own background video. Attribute values are escaped, so the first
		// ">" closes the opening tag.
		$pos = strpos( $html, '>' );
		if ( false !== $pos && preg_match( '/^\s*<[a-z]/i', $html ) ) {
			$html = substr( $html, 0, $pos + 1 ) . $layer . substr( $html, $pos + 1 );
		}

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor's own escaped output plus our escaped layer.
	}

	private static function layer_html( Element_Base $element ) {
		$settings = $element->get_settings_for_display();
		if ( empty( $settings['sm_stream_bg'] ) || 'yes' !== $settings['sm_stream_bg'] ) {
			return null;
		}

		$source = SM_Stream_Source::parse( isset( $settings['sm_stream_bg_source'] ) ? $settings['sm_stream_bg_source'] : '' );
		if ( ! $source ) {
			return null;
		}

		$quality = SM_Stream_Elementor_Helpers::responsive_values( $settings, 'sm_stream_bg_quality' );
		$poster  = SM_Stream_Elementor_Helpers::poster_url(
			$source,
			isset( $settings['sm_stream_bg_poster'] ) ? $settings['sm_stream_bg_poster'] : 'stream',
			isset( $settings['sm_stream_bg_poster_image'] ) ? $settings['sm_stream_bg_poster_image'] : array(),
			$quality
		);

		$config = array(
			'kind'   => $source['kind'],
			'src'    => $source['src'],
			'q'      => $quality,
			'mobile' => ! empty( $settings['sm_stream_bg_play_on_mobile'] ) && 'yes' === $settings['sm_stream_bg_play_on_mobile'],
			'loop'   => empty( $settings['sm_stream_bg_play_once'] ) || 'yes' !== $settings['sm_stream_bg_play_once'],
			'bg'     => true,
		);

		SM_Stream_Elementor_Helpers::enqueue();

		// Inline sizing so the layer is correct even before the stylesheet arrives.
		return sprintf(
			'<div class="elementor-background-video-container sm-stream-bg" aria-hidden="true" data-sm-stream="%1$s"><video class="sm-stream-bg__video" muted playsinline disableremoteplayback preload="none"%2$s style="position:absolute;inset:0;width:100%%;height:100%%;object-fit:cover"></video></div>',
			esc_attr( wp_json_encode( $config ) ),
			$poster ? ' poster="' . esc_url( $poster ) . '"' : ''
		);
	}
}
