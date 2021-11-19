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

	/**
	 * Fetches experiences by account id from Experiences API.
	 *
	 * @since 2.4.0
	 *
	 * @return mixed
	 */
	public function get_experiences_by_account_id( $account_id ) {
		$account_experiences = array();

		if ( ! is_numeric( $account_id ) ) {
			return [];
		}

		$url                 = esc_url_raw( self::BASE_URL . $account_id . '/experiences/' );
		$account_experiences = $this->send_request( $url );

		if ( is_wp_error( $account_experiences ) ) {
			return [];
		}

		return apply_filters( 'brightcove_get_experiences_by_account_id', $account_experiences, $account_id );
	}

	/**
	 * Fetch a In-Page experience
	 *
	 * @param string $in_page_expirence_id ID of In-Page experience
	 * @return array
	 */
	public function get_in_page_experience( $in_page_expirence_id ) {
		global $bc_accounts;

		$account_id = $bc_accounts->get_account_id();

		$url = esc_url_raw( self::BASE_URL . $account_id . '/experiences/' . $in_page_expirence_id );

		$in_page_experience = $this->send_request( $url );

		if ( is_wp_error( $in_page_experience ) ) {
			return [];
		}

		return apply_filters( 'brightcove_get_in_page_experience', $in_page_experience );
	}
}
