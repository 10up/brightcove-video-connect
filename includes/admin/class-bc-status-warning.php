<?php
/**
 * Check Brightcove System Status
 *
 * Checks the Brightcove System Status and displays a notification when a needed system is down.
 *
 * @since   1.1.0
 *
 * @package Brightcove_Video_Connect
 *
 * @author  Chris Wiegman <chris.wiegman@10up.com>
 */

/**
 * Class BC_Status_Warning
 */
class BC_Status_Warning {

	/**
	 * The JSON endpoint providing service status.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	protected $status_endpoint = 'https://api.status.io/1.0/status/534ec4a0b79718bb73000083';

	/**
	 * BC_Status_Warning constructor.
	 *
	 * Check for failed services and setup appropriate warnings.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {

		$failed_services = $this->_check_for_failed();
		$status_dismissed = isset( $_COOKIE['bc-status-dismissed'] ) ? filter_var( $_COOKIE['bc-status-dismissed'], FILTER_VALIDATE_BOOLEAN ) : false;

		if ( is_array( $failed_services ) && ! empty( $failed_services ) && false === $status_dismissed ) {

			add_action( 'admin_notices', array( $this, 'action_admin_notices' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );
			add_action( 'wp_ajax_bc_status_dismiss', array( $this, 'action_wp_ajax_bc_status_dismiss' ) );

		}
	}

	/**
	 * Checks for failed services
	 *
	 * Checks for failed Brightcove services.
	 *
	 * @since 1.1.0
	 *
	 * @return mixed an array of failed services or false if no services have failed
	 */
	protected function _check_for_failed() {

		if ( function_exists( 'vip_safe_wp_remote_get' ) ) {
			$status_response = vip_safe_wp_remote_get( $this->status_endpoint );
		} else {
			$status_response = wp_remote_get( $this->status_endpoint );
		}
		$statuses        = array();

		if ( ! defined( 'WPCOM_IS_VIP_ENV' ) || ! WPCOM_IS_VIP_ENV ) {
			$failed_services = get_site_transient( 'brightcove_failed_services' );
		} else {
			// while this makes sense to be a site transient, due to the nature of WordPress.com VIP ensure to keep it as regular transient on this environment.
			$failed_services = get_transient( 'brightcove_failed_services' );
		}

		if ( false === $failed_services ) {

			$failed_services = array();
			$timeout         = 300; // Used for transient. 5 min if a problem, 60 if all is well.

			if ( ! is_wp_error( $status_response ) ) {

				$body = json_decode( $status_response['body'], true );

				if ( isset( $body['result'] ) && isset( $body['result']['status'] ) && is_array( $body['result']['status'] ) ) {
					$statuses = $body['result']['status'];
				}
			}

			foreach ( $statuses as $status ) { // Process statuses for each service.

				if ( isset( $status['containers'] ) && is_array( $status['containers'] ) ) {

					foreach ( $status['containers'] as $container ) { // Process each services' locations.

						if ( 'Operational' !== $container['status'] ) { // Are there other valid statuses?

							if ( isset( $failed_services[ $status['name'] ] ) ) {

								$failed_services[ $status['name'] ][] = $container['name'];

							} else {

								$failed_services[ $status['name'] ] = array( $container['name'] );

							}
						}
					}
				}
			}

			if ( empty( $failed_services ) ) {

				$timeout = 3600;

			}

			if ( ! defined( 'WPCOM_IS_VIP_ENV' ) || ! WPCOM_IS_VIP_ENV ) {
				set_site_transient( 'brightcove_failed_services', $failed_services, $timeout );
			} else {
				set_transient( 'brightcove_failed_services', $failed_services, $timeout );
			}
		}

		if ( empty( $failed_services ) ) {
			return false;
		}

		return $failed_services;

	}

	/**
	 * Enqueue admin scripts
	 *
	 * Enqueues a script for better handling warning dismissal for the Brightcove admin warning.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function action_admin_enqueue_scripts() {

		$debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$dir   = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? 'src/' : '';

		wp_enqueue_script( 'bc-status-handler', esc_url( BRIGHTCOVE_URL . 'assets/js/' . $dir . 'bc-status' . $debug . '.js' ), array( 'jquery' ), BRIGHTCOVE_VERSION, true );
		wp_localize_script( 'bc-status-handler', 'bcStatus', array( 'nonce' => wp_create_nonce( 'bc_status_dismiss' ) ) );

	}

	/**
	 * Show Admin notices
	 *
	 * Shows an admin notice to indicate issues with the Brightcove system.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function action_admin_notices() {

		printf(
			'<div class="error brightcove-service-error notice is-dismissible"><p>%s <a href="http://status.brightcove.com/" target="_blank">%s.</a> </p></div>',
			esc_html__( 'One or more Brightcove services are reporting errors. This may effect your Brightcove experience. For more information please visit', 'brightcove' ),
			esc_html__( 'the Brightcove Status page', 'brightcove' )
		);

	}

	/**
	 * Process dismissal
	 *
	 * Checks that the dismissal is correct.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function action_wp_ajax_bc_status_dismiss() {

		check_ajax_referer( 'bc_status_dismiss', 'nonce' );

		wp_send_json_success( array( true ) );

	}
}
