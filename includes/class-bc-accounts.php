<?php

/**
 * Class BC_Accounts contains all account processing operations.
 * All accounts for a site are stored in a site option, with a key of
 * $this->options_key.
 *
 * This structure is an associative array, with keys generated as the first 16 chars
 * of the hash for the triplet (account_id, client_id, client_secret).
 *
 * This explicitly forbids adding the same account with a different name.
 *
 * Site default and user default account simply store the hash of the relevant account in the
 * site option, and user's meta respectively.
 *
 * We ONLY support read-write tokens and delegate permissions via the WordPress permissioning system. For
 * permissions, check the BC_Permissions Class.
 *
 */
class BC_Accounts {

	protected $options_key = '_brightcove_accounts';
	protected $current_account;

	/**
	 * The original or default user account
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $original_account;

	public function __construct() {

		$this->original_account = $this->get_account_details_for_user();
		$this->current_account  = $this->original_account;
	}

    public function get_all_accounts_id() {
        $all_accounts = $this->get_all_accounts();
        $account_ids = array();

        foreach ($all_accounts as $account) {
            $account_ids[] = $account["account_id"];
        }
        return $account_ids;
    }

	public function get_account_id() {

		return $this->current_account ? $this->current_account['account_id'] : false;
	}

	public function get_client_id() {

		return $this->current_account ? $this->current_account['client_id'] : false;
	}

	public function get_client_secret() {

		return $this->current_account ? $this->current_account['client_secret'] : false;
	}

	public function get_account_name() {

		return $this->current_account ? $this->current_account['account_name'] : false;
	}

	public function get_account_hash() {

		return $this->current_account ? $this->current_account['hash'] : false;
	}

	public function get_sync_type( $account_id ) {

		$option_key_sync_type = '_brightcove_sync_type_' . BC_Utility::sanitize_id( $account_id );

		return get_option( $option_key_sync_type, 'full' );
	}

	public function set_sync_type( $account_id, $type, $hours = 0 ) {

		$option_key_sync_type = '_brightcove_sync_type_' . BC_Utility::sanitize_id( $account_id );

		$sync_val = 'full' === $type ? 'full' : (int) $hours;

		if ( is_numeric( $sync_val ) && ( $sync_val < 0 || $sync_val > 200 ) ) {
			return false;
		}

		return update_option( $option_key_sync_type, $sync_val );
	}

	public function add_account( $account_id, $client_id, $client_secret, $account_name = '', $set_default = '', $allow_update = true ) {

		if ( empty( $account_name ) ) {
			$account_name = __( 'New Account', 'brightcove' );
		}

		// Check if WP CLI is working and bail in admin if it is.
		if ( defined( 'WP_CLI' ) && ! WP_CLI ) {

			return false;

		}

		$is_valid_account = $this->is_valid_account( $account_id, $client_id, $client_secret, $account_name );

		if ( is_array( $is_valid_account ) ) {

			foreach ( $is_valid_account as $wp_error ) {

				if ( is_wp_error( $wp_error ) ) {

					BC_Utility::admin_notice_messages(
						array(
							array(
								'message' => $wp_error->get_error_message(),
								'type'    => 'error',
							),
						)
					);

				}
			}

			return false;

		}

		if ( true !== $is_valid_account ) {
			return new WP_Error( 'brightcove-invalid-account', esc_html__( 'Account credentials are invalid. Please ensure you are using all the correct information from Brightcove Video Cloud Studio to secure a token.', 'brightcove' ) );
		}

		$operation = $allow_update ? 'update' : 'add';

		$account_hash = $this->make_account_change(
			array(
				'account_id'    => $account_id,
				'account_name'  => $account_name,
				'client_id'     => $client_id,
				'client_secret' => $client_secret,
				'set_default'   => $set_default,
				'operation'     => $operation,
			)
		);

		if ( false === $account_hash ) {
			return new WP_Error( 'brightcove-account-exists', esc_html__( 'Unable to update this account via WP-CLI.', 'brightcove' ) );
		}

		if ( $account_hash && ! $this->get_account_details_for_site() && 'default' === $set_default ) {
			$this->set_account_details_for_site( $account_hash );
		}

		$this->set_sync_type( $account_id, 'full' );

		$this->set_current_account( $account_hash );
		$all_accounts = $this->get_all_accounts();

		// If this is the first account, make it the default.
		if ( count( $all_accounts ) <= 1 ) {
			update_option( '_brightcove_default_account', $account_hash );
		}

		$this->restore_default_account();

		return true;

	}

	public function delete_account( $hash ) {

		if ( ! BC_Accounts::get_account_by_hash( $hash ) ) {
			return new WP_Error( 'brightcove-account-not-configured', esc_html__( 'The specified Brightcove Account has not been configured in WordPress', 'brightcove' ) );
		}

		$all_accounts = $this->get_all_accounts();

		$account_id = $all_accounts[ $hash ]['account_id'];

		$account_id_in_accounts_list = false;

		foreach ( $all_accounts as $account ) {

			if ( $account_id === $account['account_id'] ) {
				$account_id_in_accounts_list = true;
			}
		}

		if ( $account_id_in_accounts_list ) { //only run deletion for the provided account ID if it is actually an active account.

			BC_Utility::remove_all_media_objects_for_account_id( $account_id );

		}

		unset( $all_accounts[ $hash ] );

		if ( $hash === get_option( '_brightcove_default_account' ) ) {

			if ( ! empty( $all_accounts ) ) {

				$remaining_accounts = $all_accounts;
				// The deleted account was the default. Now set the default to be the first account in the remaining list, if there is a remaining account
				$default_account     = array_shift( $remaining_accounts );
				$new_default_account = $default_account['hash'];

			} else {

				// Set default account to false instead of delete
				$new_default_account = false;

			}

			update_option( '_brightcove_default_account', $new_default_account );

		}

		update_option( $this->options_key, $all_accounts );

		return true;

	}

	/**
	 * @param $hash
	 *
	 * @return false | array keys: 'account_id', 'account_name', 'client_id', 'client_secret'
	 */
	public function get_account_by_hash( $hash ) {

		$all_accounts = $this->get_all_accounts();

		if ( is_array( $all_accounts ) && is_string( $hash ) && $hash !== '' && isset( $all_accounts[ $hash ] ) ) {
			return $all_accounts[ $hash ];
		}

		return false;
	}

	/**
	 * @param bool $user_id (default is current ID)
	 *
	 * @return $account if exists or false if no accounts or permission denied.
	 */
	public function get_account_details_for_user( $user_id = false ) {

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		} else if ( ! current_user_can( 'brightcove_get_user_default_account' ) ) {
			return false; // Permissions violation.
		}

		$hash = BC_Utility::get_user_meta( $user_id, '_brightcove_default_account_' . get_current_blog_id(), true );

		if ( '' !== $hash ) {
			$account = $this->get_account_by_hash( $hash );
			// Stored hash may have already been deleted, so we check that account exists.
			if ( $account ) {
				return $account;
			}
		}
		// No default account for user, revert to site default.
		$account = $this->get_account_details_for_site();
		if ( $account ) {
			return $account;
		}

		// If no site default exists, just return the first account they have stored.
		$accounts = $this->get_all_accounts();

		return current( $accounts );
	}

	public function set_account_details_for_user( $hash, $user_id = false ) {

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		} else if ( ! current_user_can( 'brightcove_set_user_default_account' ) ) {
			return false; // Permissions violation.
		}

		$account = $this->get_account_by_hash( $hash );

		if ( $account ) {
			BC_Utility::update_user_meta( $user_id, '_brightcove_default_account_' . get_current_blog_id(), $hash );
			$this->current_account = $account;
		}

	}

	/**
	 * Override default account
	 *
	 * Overides a default account.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hash The account hash
	 *
	 * @return bool|array The set account on save or false on failure
	 */
	public function set_current_account( $hash ) {

		$account = $this->get_account_by_hash( $hash );

		if ( $account ) {

			$this->current_account = $account;

			return $this->current_account;

		}

		return false;

	}

	public function set_current_account_by_id( $account_id ) {

		$accounts = $this->get_all_accounts();
		foreach ( $accounts as $account ) {
			if ( $account['account_id'] === $account_id ) {
				return $this->set_current_account( $account['hash'] );
			}
		}

		return false;

	}

	/**
	 * Restore default account
	 *
	 * Restores a default account that has been overwritten.
	 *
	 * @since 1.0.0
	 *
	 * @return bool|array The set account on save or false on failure
	 */
	public function restore_default_account() {

		$account = $this->original_account;

		if ( $account ) {

			$this->current_account = $account;

			return $this->current_account;

		}

		return false;

	}

	public function set_account_details_for_site( $hash ) {

		if ( ! current_user_can( 'brightcove_set_site_default_account' ) ) {
			return false; // Permissions violation.
		}

		if ( $this->get_account_by_hash( $hash ) ) {
			update_option( '_brightcove_default_account', $hash );
		}
	}

	public function get_account_details_for_site() {

		$account = $this->get_account_by_hash( get_option( '_brightcove_default_account' ) );
		if ( $account ) {
			return $account;
		}

		return false;

	}

	protected function get_all_accounts() {

		return get_option( $this->options_key, array() );
	}

	public function get_sanitized_all_accounts() {

		$accounts = $this->get_all_accounts();

		foreach ( $accounts as $hash => $account ) {
			unset( $accounts[ $hash ]['client_secret'] );
			unset( $accounts[ $hash ]['hash'] );
		}

		uasort( $accounts, array( 'BC_Utility', 'sort_accounts_alphabetically' ) );

		return $accounts;
	}

	public function make_account_change( $account ) {

		$attributes = array( 'account_id', 'account_name', 'client_id', 'client_secret', 'set_default', 'operation' );

		foreach ( $attributes as $key ) {
			if ( ! is_string( $account[ $key ] ) && $account['operation'] !== 'update' ) {
				return false;
			}
		}

		if ( isset( $account['account_hash'] ) && $account['account_hash'] !== '' ) {
			$hash = $account['account_hash'];
		} else {
			$hash = BC_Utility::get_hash_for_account( $account );
		}

		$account['hash'] = $hash;

		$existing_accounts = $this->get_all_accounts();

		$operation = $account['operation'];

		if ( $operation === 'delete' ) {
			if ( isset( $existing_accounts[ $hash ] ) ) {
				unset( $existing_accounts[ $hash ] );

				/**
				 * Fired after an account is deleted from the WordPress admin
				 *
				 * @param string $hash Account hash according to Brightcove
				 */
				do_action( 'brightcove_deleted_account', $hash );
			}
		} else {
			if ( isset( $existing_accounts[ $hash ] ) && 'add' === $operation ) {
				return false; // Trying to overwrite an existing account with add operation
			} else {
				if ( isset( $account['set_default'] ) && 'default' === $account['set_default'] ) {
					// Only one account can be default so if this one is default, the rest can't... unset
					foreach ( $existing_accounts as $existing_account_hash => $existing_account ) {
						if ( isset( $existing_account['set_default'] ) ) {
							unset( $existing_accounts[ $existing_account_hash ]['set_default'] );
						}
					}

					update_option( '_brightcove_default_account', $hash );
				}
				$operation = $account['operation'];
				unset( $account['operation'] ); // Remove the operation from stored value
				if ( $operation !== 'update' ) {
					$existing_accounts[ $hash ] = $account; // Add / update the account.

					/**
					 * Fired after an account is created in the WordPress admin
					 *
					 * @param string $hash Account hash according to Brightcove
					 */
					do_action( 'brightcove_created_account', $hash );
				}
			}
		}
		update_option( $this->options_key, $existing_accounts );

		return $hash;
	}

	/**
	 * Check permission level for an account.
	 *
	 * @return array List of permission issues.
	 */
	protected function check_permissions_level() {

		$permission_issues = array();
		$video_id          = false;

		// Start enumerating permissions that we'll need to ensure the account is good.
		$cms_api = new BC_CMS_API();

		// Create a video
		$video_creation = $cms_api->video_add( __( 'Brightcove WordPress plugin test video', 'brightcove' ) );
		if ( ! $video_creation || is_wp_error( $video_creation ) ) {
			$permission_issues[] = esc_html__( 'create videos', 'brightcove' );
		} else {
			$video_id = $video_creation['id'];

			// Update a video
			$renamed_title = __( 'Brightcove WordPress plugin test video renamed', 'brightcove' );
			$video_renamed = $cms_api->video_update( $video_id, array( 'name' => $renamed_title ) );
			if ( ! $video_renamed || $renamed_title !== $video_renamed['name'] ) {
				$permission_issues[] = esc_html__( 'modify videos', 'brightcove' );
			}
		}

		$playlist = $cms_api->playlist_add( __( 'Brightcove WordPress plugin test playlist', 'brightcove' ) );
		if ( ! $playlist || ! is_array( $playlist ) || ! isset( $playlist['id'] ) ) {
			$permission_issues[] = esc_html__( 'create playlists', 'brightcove' );
		} else {
			// For use through other Playlist test API calls.
			$playlist_id = $playlist['id'];
			$update_data = array( 'video_ids' => array( $video_id ), 'type' => 'EXPLICIT' );

			$updated_playlist = $cms_api->playlist_update( $playlist_id, $update_data );

			if ( ! $updated_playlist || ! is_array( $updated_playlist ) || ! isset( $updated_playlist['id'] ) ) {
				$permission_issues[] = esc_html__( 'modify playlists', 'brightcove' );
			}

			// Delete a playlist
			if ( ! $cms_api->playlist_delete( $playlist_id ) ) {
				$permission_issues[] = esc_html__( 'delete playlists', 'brightcove' );
			}
		}

		// Delete a video
		if ( ! $cms_api->video_delete( $video_id ) ) {
			$permission_issues[] = esc_html__( 'delete videos', 'brightcove' );
		}

		$player_api = new BC_Player_Management_API( $this );

		// Fetch all players
		$players = $player_api->player_list();
		if ( is_wp_error( $players ) || ! is_array( $players['items'] ) ) {
			$permission_issues[] = esc_html__( 'fetch players', 'brightcove' );
		}

		return $permission_issues;
	}

	protected function is_valid_account( $account_id, $client_id, $client_secret, $account_name, $check_access = true ) {

		// Save current account as $old_account.
		$old_account = $this->current_account;

		$new_account = array(
			'account_id'    => $account_id,
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
			'account_name'  => $account_name,
		);

		$new_account['hash'] = BC_Utility::get_hash_for_account( $new_account );

		// Set new account as $account.
		$this->current_account = $new_account;

		$oauth = new BC_Oauth_API();

		// Obtain session token with oAuth.
		$valid_credentials = $oauth->is_valid_account_credentials();

		$errors = array();

		if ( ! $valid_credentials ) {
			$errors[] = new WP_Error( 'account-invalid-credentials', esc_html__( 'Invalid account credentials', 'brightcove' ) );
		} else if ( $check_access ) {
			$permission_issues = $this->check_permissions_level();
			if ( count ( $permission_issues ) > 0 ) {
				$errors[] = new WP_Error(
					'account-permission-issue',
					esc_html__( "Supplied account doesn't have the following permissions: ", 'brightcove' ) . 
						implode( ', ', $permission_issues ) . '. ' .
			            esc_html__( 'Please use an account that has these permissions.' , 'brightcove' )
				);
			}
		}

		// Restore current account transient (if exists).
		$this->current_account = $old_account;

		return ( ! empty ( $errors ) ) ? $errors : true;
	}

}
