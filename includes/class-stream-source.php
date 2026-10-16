<?php
/**
 * Turns whatever the editor pasted (a Stream ID, any Stream URL, an .m3u8 URL
 * or a plain file URL) into something the front end can play.
 */

defined( 'ABSPATH' ) || exit;

class SM_Stream_Source {

	/**
	 * @return array|null {
	 *     kind:     'hls' or 'file'
	 *     src:      URL to play
	 *     uid:      Stream video ID, when known
	 *     customer: Stream customer code, when known
	 * }
	 */
	public static function parse( $input ) {
		$input = is_string( $input ) ? trim( $input ) : '';
		if ( '' === $input ) {
			return null;
		}

		// A bare Stream video ID.
		if ( preg_match( '/^[a-f0-9]{32}$/i', $input ) ) {
			return self::from_uid( strtolower( $input ), self::default_customer() );
		}

		$parts = wp_parse_url( $input );
		if ( empty( $parts['host'] ) ) {
			return null;
		}
		$host = strtolower( $parts['host'] );
		$path = isset( $parts['path'] ) ? $parts['path'] : '';

		// customer-<code>.cloudflarestream.com, videodelivery.net and its iframe./watch. subdomains.
		$customer = null;
		if ( preg_match( '/^customer-([a-z0-9]+)\.cloudflarestream\.com$/', $host, $m ) ) {
			$customer = $m[1];
		}
		$is_stream_host = $customer
			|| 'cloudflarestream.com' === $host
			|| preg_match( '/(^|\.)videodelivery\.net$/', $host );

		if ( $is_stream_host && preg_match( '#/([a-f0-9]{32})(/|$)#i', $path, $m ) ) {
			return self::from_uid( strtolower( $m[1] ), $customer ?: self::default_customer() );
		}

		$url = esc_url_raw( $input );
		if ( '' === $url ) {
			return null;
		}

		if ( preg_match( '/\.m3u8$/i', $path ) ) {
			return array( 'kind' => 'hls', 'src' => $url, 'uid' => null, 'customer' => null );
		}

		// Anything else (e.g. an MP4 in the media library) still plays, as a plain file.
		return array( 'kind' => 'file', 'src' => $url, 'uid' => null, 'customer' => null );
	}

	public static function from_uid( $uid, $customer ) {
		return array(
			'kind'     => 'hls',
			'src'      => self::base( $uid, $customer ) . '/manifest/video.m3u8',
			'uid'      => $uid,
			'customer' => $customer,
		);
	}

	/**
	 * Stream's thumbnail for the video, sized to $height pixels tall.
	 * Uses the thumbnail time set in the Stream dashboard.
	 */
	public static function poster_url( array $source, $height = 720 ) {
		if ( empty( $source['uid'] ) ) {
			return '';
		}
		return self::base( $source['uid'], $source['customer'] ) . '/thumbnails/thumbnail.jpg?height=' . absint( $height );
	}

	private static function base( $uid, $customer ) {
		return $customer
			? 'https://customer-' . $customer . '.cloudflarestream.com/' . $uid
			: 'https://videodelivery.net/' . $uid;
	}

	private static function default_customer() {
		$code = SM_Stream_Settings::customer_code();
		return '' !== $code ? $code : null;
	}
}
