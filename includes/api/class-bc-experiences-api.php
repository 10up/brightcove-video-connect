<?php

/**
 * Interface to the Brightcove Experiences API.
 *
 * @since   1.4.2
 *
 * @package Brightcove_Video_Connect
 */
class BC_Experiences_API extends BC_API {

	/**
	 * Base URL of the Experiences API.
	 *
	 * @since  1.4.2
	 */
	const BASE_URL = 'https://experiences.api.brightcove.com/v1/accounts/';

	/**
	 * Setup processing of Experiences API
	 *
	 * Sets up class variables allowing for processing of Brightcove Experiences API functionality.
	 *
	 * @since 1.4.2
	 *
	 */
	public function __construct() {

		parent::__construct();

	}

	/**
	 * Fetches experiences from Experiences API.
	 *
	 * @since 1.4.2
	 *
	 * @return mixed
	 */
	public function get_experiences() {

		global $bc_accounts;

		$all_accounts_id = $bc_accounts->get_all_accounts_id();
		$experiences     = array();

		foreach ( $all_accounts_id as $account_id ) {
			$bc_accounts->set_current_account_by_id( $account_id );

			$url                 = esc_url_raw( self::BASE_URL . $account_id . '/experiences/' );
			$account_experiences = $this->send_request( $url );

			$experiences[ $account_id ] = $account_experiences;

			if ( is_wp_error( $account_experiences ) ) {
				return [];
			}

		}

		return apply_filters( 'brightcove_get_experiences', $experiences );
	}
}
