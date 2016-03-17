<?php

/**
 * Pseudo-namespace for wrapping API functionality for Brightcove callbacks
 */

class BC_API {

	/**
	 * Wire up any actions or filters that need to be present
	 */
	public static function setup() {
		add_action( 'brightcove_api_request', array( 'BC_API', 'flush_cache' ) );
	}

	/**
	 * When an API call hits our server, automatically flush cached Brightcove information
	 *
	 * This method is meant to be invoked
	 * - Whenever a Dynamic Ingest request completes
	 * - Whenever a video is updated on the server
	 */
	public static function flush_cache() {
		BC_Utility::delete_cache_item( '*' );
	}

	/**
	 * Fetch an array of URLs to be used as callbacks for the Dynamic Ingest API.
	 *
	 * @return array
	 */
	public static function callback_paths() {
		$callbacks = array();

		$api_url = home_url( 'bc-api' );
		$callbacks[] = esc_url( $api_url );

		/**
		 * Filter the callback URLs passed for Dynamic Ingest requests
		 *
		 * @param array $callbacks
		 */
		$callbacks = apply_filters( 'brightcove_api_callbacks', $callbacks );

		return $callbacks;
	}
}
