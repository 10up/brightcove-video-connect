<?php

class BC_Errors {

	/**
	 * A holding bucket for errors that need to be displayed in wp-admin
	 *
	 * @var array (
	 *      Each member of this array should be an array with two required members:
	 *
	 * @type string $type    . Either error|updated. Aligns with core CSS class assignment for error messages
	 * @type string $message . The error message to display. NOTE: This method does not i18n messages. They should already be i18n.
	 *
	 * )
	 */
	public static $admin_notices = array();

	/**
	 * When multiple WP_Error objects are present (e.g. Account Permission Provisioning), consolidate into a single WP_Error object with multiple messages.
	 *
	 * @param array $error_array This should be an array of WP_Error objects.
	 *
	 * @return WP_Error
	 */
	public static function consolidate_multiple_wp_errors( $error_array ) {

		foreach ( $error_array as $error ) {

			$wp_errors = false;

			if ( ! is_wp_error( $error ) ) {
				continue;
			}

			if ( ! $wp_errors ) {
				$wp_errors = new WP_Error( $error->get_error_code(), $error->get_error_message() );
			} else {
				$wp_errors->add( $error->get_error_code(), $error->get_error_message() );
			}

			return $wp_errors;
		}
	}

	/**
	 * Method to display update or error messages.
	 * To use, add errors to the BC_Utility::$admin_errors array
	 *
	 * @return bool
	 */
	public static function render_notices() {

		if ( empty( self::$admin_notices ) ) {
			return false;
		}

		$html = '';
		foreach ( self::$admin_notices as $notice ) {
			$html .= sprintf( '<div class="%1$s">', esc_attr( $notice['type'] ) );
			$html .= sprintf( '<p>%s</p>', esc_html( $notice['message'] ) );
			$html .= '</div>';
		}

		echo $html;
	}

}