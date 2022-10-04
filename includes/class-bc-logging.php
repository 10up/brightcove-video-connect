<?php
/**
 * BC_Logging class file.
 *
 * @package Brightcove Video Connect
 */

/**
 * BC_Logging class.
 */
class BC_Logging {

	/**
	 * In order to use, `WP_DEBUG` must be enabled. Supports logging to the syslog, a custom file or the API. There is no API reporting at this time (@see `BC_Logging::api_send()`).
	 * In the event a custom file is used, the $file param must be included as a path to the file. If it doesn't exist, is invalid or
	 * is not writable, the method will write to the syslog instead.
	 *
	 * @param string $message Cannot be binary as `error_log()` is not binary-safe.
	 * @param string $mode syslog|file. Default: syslog
	 * @param bool   $file Full path to custom log file
	 *
	 * @return bool|WP_Error
	 */
	public static function log( $message, $mode = 'syslog', $file = false ) {

		// Sanity. Add a newline to the end as appending a messages to a file does not do it itself.
		$message = $message . "\n";

		if ( ! defined( 'WP_DEBUG' ) ) {
			return false;
		}

		if ( ! ctype_print( $message ) ) {
			return new WP_Error( 'log-invalid-contents', esc_html__( 'Binary content is not supported by the Logging mechanism.', 'brightcove' ) );
		}

		switch ( $mode ) {

			case 'file':
				if ( ! $file ) {
					self::determine_error_logging( $message );

					return new WP_Error( 'log-destination-file-not-set', esc_html__( 'You must provide a file path and name to use <pre>file</pre> mode. Writing to the syslog instead.', 'brightcove' ) );
				}

				if ( ! is_file( $file ) ) {
					self::determine_error_logging( $message );
					// Translators: %s is the file.
					return new WP_Error( 'log-destination-file-is-invalid', sprintf( __( 'The file specified, <pre>%s</pre> does not exist. Writing to the syslog instead.', 'brightcove' ), $file ) );
				}

				if ( ! is_writable( $file ) ) {
					self::determine_error_logging( $message );

					// Translators: %s is the file.
					return new WP_Error( 'log-destination-file-unwritable', sprintf( esc_html__( 'The file specified, <pre>%s</pre> is not writable byt the web server. Writing to the syslog instead.', 'brightcove' ), $file ) );
				}

				error_log( $message, 3, $file ); // phpcs:ignore
				break;
			case 'syslog':
			default:
				self::determine_error_logging( $message );
				break;
		}
		return true;
	}

	/**
	 * Determine how do we log the errors
	 *
	 * @param string $message The error message
	 */
	public static function determine_error_logging( $message ) {
		if ( ( defined( 'WPCOM_IS_VIP_ENV' ) && WPCOM_IS_VIP_ENV ) && function_exists( 'newrelic_notice_error' ) ) {
			newrelic_notice_error( $message );
		} else {
			error_log( $message ); // phpcs:ignore
		}
	}
}
