<?php

/**
 * Pseudo-namespace for wrapping API functionality for Brightcove callbacks
 */

class BC_Notification_API {

	/**
	 * Wire up any actions or filters that need to be present
	 */
	public static function setup() {
		add_action( 'brightcove_api_request', array( 'BC_Notification_API', 'flush_cache' ) );
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

	/**
	 * Add a subscription listener for a specific account
	 *
	 * @global BC_Accounts $bc_accounts
	 *
	 * @param string $account_hash
	 */
	public static function create_subscription( $account_hash ) {
		global $bc_accounts;

		// Set up the account to which we're pushing data
		$account = $bc_accounts->set_current_account( $account_hash );
		if ( false === $account ) { // Account was invalid, fail
			// Restore our global, default account
			$bc_accounts->restore_default_account();
			return;
		}

		// We're in a static method, so instantiate the API we need
		$cms_api = new BC_CMS_API();

		foreach( self::callback_paths() as $path ) {
			// Subscribe to allthethings
			$cms_api->create_subscription( $path, array( 'video-change' ) );
		}

		// Restore our global, default account
		$bc_accounts->restore_default_account();
	}
}
