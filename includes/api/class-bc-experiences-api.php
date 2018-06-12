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
		return $this->send_request( esc_url_raw( self::BASE_URL . $this->get_account_id() . '/experiences' ) );
	}
}
