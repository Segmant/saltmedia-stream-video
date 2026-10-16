<?php
/**
 * Settings > Stream Video: the Stream customer code, used when an editor
 * pastes a bare video ID (full Stream URLs carry their own code).
 */

defined( 'ABSPATH' ) || exit;

class SM_Stream_Settings {

	const OPTION = 'sm_stream_customer_code';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	public static function customer_code() {
		if ( defined( 'SM_STREAM_CUSTOMER_CODE' ) ) {
			return self::sanitize( SM_STREAM_CUSTOMER_CODE );
		}
		return self::sanitize( get_option( self::OPTION, '' ) );
	}

	public static function sanitize( $value ) {
		$value = strtolower( trim( (string) $value ) );
		// Accept "customer-abc123", "customer-abc123.cloudflarestream.com" or just "abc123".
		if ( preg_match( '/(?:customer-)?([a-z0-9]+)(?:\.cloudflarestream\.com)?$/', $value, $m ) ) {
			return $m[1];
		}
		return '';
	}

	public static function menu() {
		add_options_page(
			__( 'Stream Video', 'saltmedia-stream-video' ),
			__( 'Stream Video', 'saltmedia-stream-video' ),
			'manage_options',
			'sm-stream-video',
			array( __CLASS__, 'page' )
		);
	}

	public static function register() {
		register_setting( 'sm_stream_video', self::OPTION, array(
			'type'              => 'string',
			'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			'default'           => '',
		) );
	}

	public static function page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Stream Video', 'saltmedia-stream-video' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'sm_stream_video' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="sm-stream-customer"><?php esc_html_e( 'Stream customer code', 'saltmedia-stream-video' ); ?></label></th>
						<td>
							<?php if ( defined( 'SM_STREAM_CUSTOMER_CODE' ) ) : ?>
								<code><?php echo esc_html( self::customer_code() ); ?></code>
								<p class="description"><?php esc_html_e( 'Set by SM_STREAM_CUSTOMER_CODE in wp-config.php.', 'saltmedia-stream-video' ); ?></p>
							<?php else : ?>
								<input type="text" class="regular-text" id="sm-stream-customer" name="<?php echo esc_attr( self::OPTION ); ?>" value="<?php echo esc_attr( self::customer_code() ); ?>" placeholder="e.g. srned4fml3cl8zf1">
								<p class="description"><?php esc_html_e( 'The part after "customer-" in your Stream URLs. Only needed when you paste bare video IDs; full Stream links include it.', 'saltmedia-stream-video' ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
