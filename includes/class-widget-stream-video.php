<?php
/**
 * "Stream Video" widget: a Cloudflare Stream HLS video with optional controls,
 * sound, autoplay and loop. Nothing downloads until the video is visible
 * (autoplay) or played (click to play).
 */

defined( 'ABSPATH' ) || exit;

require_once SM_STREAM_DIR . 'includes/class-elementor-helpers.php';

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

class SM_Stream_Video_Widget extends Widget_Base {

	public function get_name() {
		return 'sm_stream_video';
	}

	public function get_title() {
		return __( 'Stream Video', 'saltmedia-stream-video' );
	}

	public function get_icon() {
		return 'eicon-youtube';
	}

	public function get_categories() {
		return array( 'basic' );
	}

	public function get_keywords() {
		return array( 'video', 'stream', 'cloudflare', 'hls' );
	}

	public function get_script_depends() {
		return array( 'sm-stream-video' );
	}

	public function get_style_depends() {
		return array( 'sm-stream-video' );
	}

	protected function is_dynamic_content(): bool {
		// Output depends on the current post (ACF fields in loops), so never cache it.
		return true;
	}

	protected function register_controls() {
		$this->start_controls_section( 'section_video', array(
			'label' => __( 'Video', 'saltmedia-stream-video' ),
		) );

		$this->add_control( 'source', SM_Stream_Elementor_Helpers::source_control_args( __( 'Video', 'saltmedia-stream-video' ) ) );

		$this->add_control( 'autoplay', array(
			'label'       => __( 'Autoplay', 'saltmedia-stream-video' ),
			'type'        => Controls_Manager::SWITCHER,
			'default'     => 'yes',
			'description' => __( 'Autoplaying videos are always muted (browser rule) and start when scrolled into view.', 'saltmedia-stream-video' ),
		) );

		$this->add_control( 'play_on_mobile', array(
			'label'     => __( 'Autoplay on mobile', 'saltmedia-stream-video' ),
			'type'      => Controls_Manager::SWITCHER,
			'default'   => 'yes',
			'condition' => array( 'autoplay' => 'yes' ),
		) );

		$this->add_control( 'mute', array(
			'label'     => __( 'Mute', 'saltmedia-stream-video' ),
			'type'      => Controls_Manager::SWITCHER,
			'condition' => array( 'autoplay!' => 'yes' ),
		) );

		$this->add_control( 'loop', array(
			'label'   => __( 'Loop', 'saltmedia-stream-video' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => 'yes',
		) );

		$this->add_control( 'controls', array(
			'label' => __( 'Player controls', 'saltmedia-stream-video' ),
			'type'  => Controls_Manager::SWITCHER,
		) );

		$this->add_responsive_control( 'quality', array(
			'label'       => __( 'Max quality', 'saltmedia-stream-video' ),
			'type'        => Controls_Manager::SELECT,
			'options'     => SM_Stream_Elementor_Helpers::quality_options(),
			'default'     => 'auto',
			'device_args' => SM_Stream_Elementor_Helpers::quality_device_args(),
		) );

		$this->add_control( 'poster', SM_Stream_Elementor_Helpers::poster_control_args() );

		$this->add_control( 'poster_image', array_merge(
			SM_Stream_Elementor_Helpers::poster_image_control_args(),
			array( 'condition' => array( 'poster' => 'custom' ) )
		) );

		$this->end_controls_section();

		$this->start_controls_section( 'section_style', array(
			'label' => __( 'Video', 'saltmedia-stream-video' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'aspect_ratio', array(
			'label'     => __( 'Aspect ratio', 'saltmedia-stream-video' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '16 / 9',
			'options'   => array(
				'16 / 9' => '16:9',
				'21 / 9' => '21:9',
				'4 / 3'  => '4:3',
				'3 / 2'  => '3:2',
				'1 / 1'  => '1:1',
				'4 / 5'  => '4:5',
				'3 / 4'  => '3:4',
				'9 / 16' => '9:16',
			),
			'selectors' => array( '{{WRAPPER}} .sm-stream-video' => 'aspect-ratio: {{VALUE}};' ),
		) );

		$this->add_control( 'fit', array(
			'label'     => __( 'Fit', 'saltmedia-stream-video' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'cover',
			'options'   => array(
				'cover'   => __( 'Fill (crop)', 'saltmedia-stream-video' ),
				'contain' => __( 'Fit (letterbox)', 'saltmedia-stream-video' ),
			),
			'selectors' => array( '{{WRAPPER}} .sm-stream-video__video' => 'object-fit: {{VALUE}};' ),
		) );

		$this->add_control( 'background', array(
			'label'     => __( 'Background colour', 'saltmedia-stream-video' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .sm-stream-video' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'radius', array(
			'label'      => __( 'Border radius', 'saltmedia-stream-video' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%', 'em', 'rem' ),
			'selectors'  => array( '{{WRAPPER}} .sm-stream-video' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$source   = SM_Stream_Source::parse( isset( $settings['source'] ) ? $settings['source'] : '' );

		if ( ! $source ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="sm-stream-video sm-stream-video--empty">' . esc_html__( 'Add a Stream link or video ID.', 'saltmedia-stream-video' ) . '</div>';
			}
			return;
		}

		$quality  = SM_Stream_Elementor_Helpers::responsive_values( $settings, 'quality' );
		$poster   = SM_Stream_Elementor_Helpers::poster_url( $source, $settings['poster'], isset( $settings['poster_image'] ) ? $settings['poster_image'] : array(), $quality );
		$autoplay = 'yes' === $settings['autoplay'];
		$muted    = $autoplay || 'yes' === $settings['mute'];

		$config = array(
			'kind'     => $source['kind'],
			'src'      => $source['src'],
			'q'        => $quality,
			'autoplay' => $autoplay,
			'mobile'   => ! $autoplay || 'yes' === $settings['play_on_mobile'],
			'loop'     => 'yes' === $settings['loop'],
			'bg'       => false,
		);

		$attrs = array( 'playsinline', 'preload="none"' );
		if ( $muted ) {
			$attrs[] = 'muted';
		}
		if ( 'yes' === $settings['controls'] ) {
			$attrs[] = 'controls';
		}
		if ( $config['loop'] ) {
			$attrs[] = 'loop';
		}
		if ( $poster ) {
			$attrs[] = 'poster="' . esc_url( $poster ) . '"';
		}

		printf(
			'<div class="sm-stream-video" data-sm-stream="%1$s"><video class="sm-stream-video__video" %2$s></video></div>',
			esc_attr( wp_json_encode( $config ) ),
			implode( ' ', $attrs ) // Built from fixed strings and esc_url() above.
		);
	}
}
