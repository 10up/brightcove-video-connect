<?php

/**
 * Pseudo-namespace for wrapping API functionality for Brightcove callbacks
 */

class BC_Notification_API {

	/**
	 * Wire up any actions or filters that need to be present
	 */
	public static function setup() {
		add_action( 'brightcove_api_request',     array( 'BC_Notification_API', 'flush_cache' ) );

		// @TODO Verify API as errors don't seem to match the documentation
		// add_action( 'brightcove_created_account', array( 'BC_Notification_API', 'create_subscription' ) );
		// add_action( 'brightcove_deleted_account', array( 'BC_Notification_API', 'remove_subscription' ) );
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
	 * If the installed version is less than 1.2, assume we need to add subscription listeners for
	 * all existing accounts.
	 *
	 * @global BC_Accounts $bc_accounts
	 *
	 * @param string $installed_version
	 */
	public static function maybe_backport_subscriptions( $installed_version ) {
		if ( version_compare( $installed_version, '1.2.0', '<' ) ) {
			global $bc_accounts;

			$accounts = $bc_accounts->get_sanitized_all_accounts();
			$hashes = array_keys( $accounts );

			// Walk through each account and create an API change notification subscription
			array_map( array( 'BC_Notification_API', 'create_subscription' ), $hashes );
		}
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
			$subscription_id = $cms_api->create_subscription( $path, array( 'video-change' ) );

			if ( false !== $subscription_id ) {
				add_option( 'bc_sub_' . $account_hash, $subscription_id, '', 'no' );
			}
		}

		// Restore our global, default account
		$bc_accounts->restore_default_account();
	}

	/**
	 * Remove a subscription listener for a specific account.
	 * 
	 * @global BC_Accounts $bc_accounts
	 * 
	 * @param string $account_hash
	 */
	public static function remove_subscription( $account_hash ) {
		global $bc_accounts;

		// Set up the account to which we're pushing data
		$account = $bc_accounts->set_current_account( $account_hash );
		if ( false === $account ) { // Account was invalid, fail
			// Restore our global, default account
			$bc_accounts->restore_default_account();
			return;
		}
		
		// Get the subscription ID so we can delete it
		$subscription_id = get_option( 'bc_sub_' . $account_hash );
		
		if ( false !== $subscription_id ) {
			// We're in a static method, so instantiate the API we need
			$cms_api = new BC_CMS_API();

			// Unsubscribe from the thing
			$cms_api->remove_subscription( $subscription_id );
		}

		// Restore our global, default account
		$bc_accounts->restore_default_account();
	}
}
