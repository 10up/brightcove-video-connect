<?php
class BC_Utility {

	private function __construct() {}

	/**
	 * Returns a string of the video ID
	 * @param $video_id string containing a video id
	 * @return string containing video id prefixed by ID_
	 */
	public static function sanitize_and_generate_meta_video_id( $video_id ) {
		return "ID_" . BC_Utility::sanitize_id( $video_id);
	}

	public static function get_sanitized_video_id( $post_id ) {
		$meta_value = get_post_meta( $post_id, '_brightcove_video_id', true );
		return str_replace( 'ID_', '', $meta_value );
	}

    public static function get_sanitized_client_secret( $client_secret ) {
        return is_string( $client_secret ) ?  preg_replace( '/[^a-z0-9_-]/i', '', $client_secret ) : '';
    }

    /**
     * Check if the current user can work with Brightcove videos
     * @return boolean current_user_can_brightcove
     */
    public static function current_user_can_brightcove() {
    	if ( is_admin() && ( current_user_can( 'brightcove_manipulate_videos' ) || is_super_admin() ) ) {
    		return true;
    	} else {
    		return false;
    	}
    }

	/**
	 * @param $numeric_string
	 * @return string containing integers only
	 */
	public static function sanitize_id( $numeric_string ) {
		return is_string( $numeric_string) ?  sanitize_text_field( preg_replace( '/\D/', '', $numeric_string ) ) : "";
	}

	/**
	 * @param $date_string
	 * @return string containing integers only
	 */
	public static function sanitize_date( $date_string ) {
		return is_string( $date_string) ?  sanitize_text_field( preg_replace( '/[^0-9-]/', '', $date_string ) ) : "";
	}

	public static function sanitize_subscription_id( $subscription_id ) {
		return is_string( $subscription_id) ?  sanitize_text_field( preg_replace( '/[^0-9a-f-]/', '', $subscription_id ) ) : "";
	}

	/**
	 * Removes a pending ingestion request (anything over 1 hour old) and any
	 * $video_id that has been supplied.
	 *
	 * @param null $video_id
	 * @return bool true
	 */
	public static function remove_pending_uploads( $video_id = null ) {
		$video_id = BC_Utility::sanitize_and_generate_meta_video_id( $video_id );
		$pending_videos = get_option( '_brightcove_pending_videos' );
		$expire_time = time() - 3600;

		if ( ! is_array( $pending_videos ) ) {
			// Possibly had no pending videos therefore nothing to remove,
			// therefore successfully removed nothing.
			return true;
		}

        foreach ( $pending_videos as $stored_video_id => $metadata ) {
			if ( ( $metadata[ 'added' ] < $expire_time ) || ( $stored_video_id === $video_id ) ) {
				unset( $pending_videos[ $stored_video_id ] );
				if ( file_exists( $metadata[ 'filename' ] ) ) {
					unlink( $metadata[ 'filename' ] );
				}
			}
		}

		update_option( '_brightcove_pending_videos', $pending_videos );

		// Return true as we may not have expired any videos.
		return true;

	}

	/**
	 * @param $account_id all or account_id or null for ALL accounts
	 */
	public static function clear_cached_api_requests( $account_id ) {
		global $wpdb;
		if ( is_null( $account_id ) ) {
			$account_id = "";
		} else {
			if ('all' !== $account_id) {
				$account_id = BC_Utility::sanitize_id( $account_id );
			}
		}

		$keys = self::get_requests_transient_key( $account_id );
		if ( is_array( $keys ) ) {
			foreach( $keys as $key ) {
				self::delete_transient_key( $key );
				delete_transient( $key );
			}
		}
	}

	/**
	 * Create a key for the account ID or video ID to prevent third party from sending
	 * spoofed callback requests.
	 * @param $video_id
	 * @return string containing hash
	 */
	public static function get_auth_key_for_id( $video_id ) {
		$hash = hash( 'sha256', BC_Utility::salt() . $video_id );
		return substr( $hash, 0, 8);
	}

	/**
	 * @param $account array containing an account id, client id and client secret
	 * @return string hash for the account
	 */
	public static function get_hash_for_account( $account ) {
		if ( ! $account[ 'account_id' ] || ! $account[ 'client_id' ] || ! $account[ 'client_secret' ] ) {
			return false;
		}

		$account_triplet = array(
			'account_id' => $account[ 'account_id' ],
			'client_id' => $account[ 'client_id' ],
			'client_secret' => $account[ 'client_secret' ],
		);

		$hash = BC_Utility::get_hash_for_object( $account_triplet );
		$hash = substr( $hash, 0, 16);

		return $hash;
	}

	/**
	 * This salt replaces wp_salt for scenarios where wp_salt changes
	 * It's slightly less secure, but does allow for callbacks on video
	 * notifications to continue
	 */
	public static function salt() {
		$key_name = '_brightcove_salt';
		$salt = get_option( $key_name);
		if ( false !== $salt ) {
			$salt = hash( 'sha256', wp_salt() . mt_rand() . wp_salt( 'secure_auth' ) );
			update_option( $key_name, $salt);

		}
		return $salt;
	}

	/**
	 * Add pending video ID and uploaded filename to the _brightcove_pending_videos option
	 *
	 * @param $video_id
	 * @param string $filename
	 * @return boolean status of update_option
	 */
	public static function add_pending_upload( $video_id, $filename = '' ) {
		$video_id = BC_Utility::sanitize_and_generate_meta_video_id( $video_id );
		BC_Utility::remove_pending_uploads();
		$pending_videos = get_option( '_brightcove_pending_videos', array() );
		$pending_videos[ $video_id ] = array(
			'filename' => $filename,
			'added' => time(),
		);

		return update_option( '_brightcove_pending_videos', $pending_videos );
	}

	/**
	 * Returns a hash for an object. Lets us know if data is stale
	 *
	 * @param $obj
	 * @return string containing hash
	 */
	public static function get_hash_for_object( $object ) {
		BC_Utility::recursive_object_sort( $object);
		return hash( 'sha256', wp_json_encode( $object ) );
	}

	/**
	 * @param $type playlist|video
	 * @param $data sorted playlists|videos associative array
	 * @return bool true if option value has changed, false on failure/no change
	 */
	public static function store_hash( $type, $data, $account_id ) {
		$key = "_brightcove_hash_{$type}_{$account_id}";
		$data_hash = BC_Utility::get_hash_for_object( $data );
		return update_option( $key, $data_hash );
	}

	/**
	 * @param $player_id
	 * @return string option key name in the form of _bc_player_{$player_id}_{$account_id}
	 */
	public static function get_player_key( $player_id ) {

		global $bc_accounts;

		$player_id = BC_Utility::sanitize_player_id( $player_id );
		return "_bc_player_{$player_id}_" . $bc_accounts->get_account_id();
	}

	/**
	 * @param $type playlists|video|players
	 * @param $data sorted playlists|videos|players associative array
	 * @return bool if stored hash matches calculated hash.
	 */
	public static function hash_changed( $type, $data, $account_id ) {
		$key = "_brightcove_hash_{$type}_{$account_id}";
		$data_hash = BC_Utility::get_hash_for_object( $data );
		$existing_hash = get_option( $key );
		return $existing_hash !== $data_hash;

	}

	public static function remove_all_media_objects_for_account_id( $account_id ) {
		// Delete account players
		$player_ids = get_option( '_bc_player_ids_' . BC_Utility::sanitize_id( $account_id ), array() );

		delete_option( '_bc_player_playlist_ids_' . BC_Utility::sanitize_id( $account_id ) );
		delete_option( '_bc_player_ids_' . BC_Utility::sanitize_id( $account_id ) );
		foreach ( $player_ids as $player_id ) {
			delete_option( '_bc_player_' . BC_Utility::sanitize_player_id( $player_id ) . '_' . BC_Utility::sanitize_id( $account_id ) );
		}
		delete_option( '_bc_player_default_' . BC_Utility::sanitize_id( $account_id ) );

		wp_reset_postdata();
	}

	/**
	 * Function to delete players that are stored as an option.
	 * @param $ids_to_keep
	 * @return bool true if all options deleted, false on failure or non-existent player
	 */
	public static function remove_deleted_players( $ids_to_keep ) {

		global $bc_accounts;
		$all_ids_key = '_bc_player_ids_' . $bc_accounts->get_account_id();
		$all_ids = get_option( $all_ids_key );

		$all_ids_playlists_key = '_bc_player_playlist_ids_' . $bc_accounts->get_account_id();
		$all_ids_playlists = get_option( $all_ids_playlists_key );

		$return_state = true;

		if ( is_array( $all_ids ) ) {
			$ids_to_delete = array_diff( $all_ids, $ids_to_keep);

			foreach ( $ids_to_delete as $id ) {
				$key = BC_Utility::get_player_key( $id );
				$success = delete_option( $key );
				if ( ! $success ) {
					$return_state = false;
				}
			}

		}

		if( is_array( $all_ids_playlists ) ) {
			foreach( $all_ids_playlists as $id ) {
				if( in_array( $id, $all_ids_playlists ) ) {
					unset( $all_ids_playlists[ $id ] );
				}
			}
		}

		update_option( $all_ids_key, $ids_to_keep );
		update_option( $all_ids_playlists_key, $all_ids_playlists);
		return $return_state;
	}

	/**
	 * Sorts arrays, leaves objects as is.
	 * @param $object
	 * @return array|bool
	 */
	public static function recursive_object_sort( $object ) {
		if ( !is_array( $object ) ) {
			return $object;
		}
		foreach ( $object as &$value ) {
			if ( is_array( $value ) ) {
				BC_Utility::recursive_object_sort($value);
			}
		}
		return ksort( $object );
	}

	/**
	 * @param $player_id
	 *
	 * @return string containing sanitized player_id
	 */
	public static function sanitize_player_id( $player_id ) {
		if ( $player_id === 'default' ) {
			return 'default';
		}

		return is_string( $player_id ) ?  preg_replace( '/[^0-9a-f-]/', '', $player_id ) : '';
	}

	public static function sanitize_payload_args_recursive( $args ) {
		foreach ( $args as $index => $value ) {

			if( is_array( $value ) ) {
				$args[ $index ] = BC_Utility::sanitize_payload_args_recursive( $value );
			} else {
				$args[ $index ] = utf8_uri_encode( sanitize_text_field( $value ) );
			}
		}
		return $args;
	}

	public static function sanitize_payload_item( $item ) {
		if( is_array( $item ) ) {
			return BC_Utility::sanitize_payload_args_recursive( $item );
		}

		return utf8_uri_encode( sanitize_text_field( $item ) );
	}

	public static function sort_accounts_alphabetically( $account_a, $account_b ) {
		return strnatcmp( $account_a[ 'account_name' ], $account_b[ 'account_name' ] );
	}

	// Function for storing YYYY-MM for all videos in library
	// If we already have values for a particular $account_id, we add to them.
	public static function set_video_playlist_dates( $type, $media_dates, $account_id) {
		if ( !in_array( $type, array( 'videos', 'playlists' ) ) || ! $account_id || ! is_array( $media_dates ) ) {
			return false;
		}
		$all_dates = BC_Utility::get_video_playlist_dates( $type );
		$key = '_brightcove_dates_' . $type;
		$id = BC_Utility::sanitize_and_generate_meta_video_id( $account_id );
		if ( array_key_exists( $id, $all_dates ) && is_array( $all_dates[ $id ] ) ) {
			// Check number of dates before we add these.
			$date_count = count( $all_dates[ $id ] );
			$all_dates[ $id ] = array_unique( array_merge( $all_dates[ $id ], $media_dates) );

			// If the count hasn't changed then we don't have to set the new dates since they're already reflected.
			if ( $date_count === count( $all_dates[ $id ] ) ) {
				return true;
			}
		} else {
			$all_dates[ $id ] = $media_dates;
		}
		$all_dates_for_all_accounts = array();
		foreach( $all_dates as $all_dates_key => $dates ) {
			$all_dates_for_all_accounts = array_merge( $all_dates_for_all_accounts, $dates);
		}
		$all_dates[ 'all' ] = array_unique( $all_dates_for_all_accounts );

		update_option( $key, $all_dates );
	}

	public static function get_video_playlist_dates( $type, $account_id = false ) {
		if ( !in_array( $type, array( 'videos', 'playlists' ) ) ) {
			return false;
		}

		$key = '_brightcove_dates_' . $type;
		$all_dates = get_option( $key );
		if ( is_array( $all_dates ) ) {
			if ( $account_id ) {
				$id = BC_Utility::sanitize_and_generate_meta_video_id( $account_id );
				if ( isset( $all_dates[ $id ] ) ) {
					return $all_dates[ $id ];
				} else {
					return array(); // No dates empty array
				}
			} else {
				return is_array( $all_dates ) ? $all_dates : array();
			}
		}
		return array();
	}

	public static function get_video_playlist_dates_for_display( $type ) {
		$all_dates = BC_Utility::get_video_playlist_dates( $type);
		foreach ( $all_dates as $id => $dates_for_id ) {
			$new_id = $id === 'all' ? 'all' : BC_Utility::get_sanitized_video_id($id); // Strip ID_
			$labelled_dates = array();
			foreach ( $dates_for_id as $yyyy_mm ) {
				$date_object = new DateTime($yyyy_mm . '-01');
				$labelled_dates[] = array(
					'code' => $yyyy_mm,
					'value' => $date_object->format('F Y')
				);
			}
			unset( $all_dates[ $id ] ); // Has to proceed for $id === 'all'
			$all_dates[ $new_id ] = $labelled_dates;
		}
		return $all_dates;
	}

	public static function get_all_brightcove_mimetypes() {
		return array(
			'ogx'   => 'application/ogg',
			'ogv'   => 'video/ogg',
			'oga'   => 'audio/ogg',
			'ogg'   => 'audio/ogg',
			'wav'   => 'audio/wav',
			'mp4'   => 'video/mp4',
			'm4v'   => 'video/mp4',
			'f4b'   => 'audio/mp4',
			'f4a'   => 'audio/mp4',
			'm4a'   => 'audio/mp4',
			'mp3'   => 'audio/mp3',
			'm4r'   => 'audio/aac',
			'aac'   => 'audio/aac',
			'f4v'   => 'video/x-f4v',
			'vp8'   => 'video/webm',
			'vp6'   => 'video/x-vp6',
			'3gpp'  => 'video/3gpp',
			'3gpp2' => 'video/3gpp2',
			'ts'    => 'video/MP2T',
			'hls'   => 'application/x-mpegurl',
			'mss'   => 'application/vnd.ms-sstr+xml',
			'flv'   => 'video/x-flv',
			'wmv'   => 'video/x-ms-wmv',

		);
	}

	/**
	 * Used for removing all removed objects from a Brightcove sync
	 * @param $id post id
	 * @param $account_id account id that the video/playlist ID is associated with
	 * @return mixed
	 */
	public static function remove_object( $id, $account_id ) {
		if ( $account_id !== get_post_meta( $id, '_brightcove_account_id', true ) ) {
			// We've switched accounts, don't delete any of the posts, set them to private.
			$update = array(
				'ID'          => $id,
				'post_status' => 'private'
			);

			return wp_update_post( $update );
		} else {
			return wp_delete_post( $id, true );
		}
	}

	/**
	 * Used for deleting a post as initiated by a user action to delete a video/playlist.
	 * @param $id post_id
	 * @return mixed
	 */
	public static function delete_object( $id ) {
		return wp_delete_post( $id, true );
	}

	public static function admin_notice_messages( $notices ) {

		global $allowed_tags;

		if ( empty( $notices ) ) {
			return false;
		}

		$html = '';
		foreach ( $notices as $notice ) {
			$html .= sprintf( '<div class="%1$s brightcove-settings-%1$s notice is-dismissible">', esc_attr( $notice['type'] ) );
			$html .= sprintf( '<p>%s</p>', wp_kses( $notice['message'], $allowed_tags ) );
			$html .= '</div>';
		}

		echo $html;
	}

	public static function bc_plugin_action_links( $links ) {
		$bc_settings_page = array(
			'<a href="' . esc_url( admin_url( 'admin.php?page=brightcove-sources' ) ) . '">Settings</a>'
			);

		return array_merge( $links, $bc_settings_page );
	}

	/**
	 * Wrapper utility method for using WordPress.com get_user_attribute() when available. Falls back to get_user_meta()
	 *
	 * @param $user_id
	 * @param $meta_key
	 * @param bool|true $single
	 *
	 * @return mixed
	 */
	public static function get_user_meta( $user_id, $meta_key, $single = true ) {
		if( function_exists( 'get_user_attribute' ) ) {
			$meta_value = get_user_attribute( $user_id, $meta_key );
		} else {
			$meta_value = get_user_meta( $user_id, $meta_key, $single );
		}

		return $meta_value;
	}

	/**
	 * Wrapper utility to for using WordPress.com update_user_attribute() when available. Falls back to update_user_meta()
	 *
	 * @param $user_id
	 * @param $meta_key
	 * @param $meta_value
	 *
	 * @return mixed
	 */
	public static function update_user_meta( $user_id, $meta_key, $meta_value ) {
		if( function_exists( 'update_user_attribute' ) ) {
			$result = update_user_attribute( $user_id, $meta_key, $meta_value );
		} else {
			$result = update_user_meta( $user_id, $meta_key, $meta_value );
		}

		return $result;
	}

	/**
	 * Wrapper utility for using WordPress.com delete_user_attribute() when available. Falls back to delete_user_meta()
	 *
	 * @param $user_id
	 * @param $meta_key
	 * @param $meta_value
	 *
	 * @return mixed
	 */
	public static function delete_user_meta( $user_id, $meta_key, $meta_value ) {
		if( function_exists( 'delete_user_attribute' ) ) {
			$result = delete_user_attribute( $user_id, $meta_key, $meta_value );
		} else {
			$result = delete_user_meta( $user_id, $meta_key, $meta_value );
		}

		return $result;
	}

	public static function activate() {
		update_option( '_brightcove_plugin_activated', true, 'no' );
		flush_rewrite_rules();
	}

	public static function deactivate() {
		require_once( BRIGHTCOVE_PATH . 'includes/classes/class-bc-accounts.php' );

		$bc_accounts = new BC_Accounts();

		$accounts = $bc_accounts->get_sanitized_all_accounts();

		foreach ( $accounts as $account => $account_data ) {

			$bc_accounts->set_current_account( $account );

			$account_hash = $bc_accounts->get_account_hash();

			delete_transient( 'brightcove_oauth_access_token_' . $account_hash );
			self::delete_transient_key( 'brightcove_oauth_access_token_' . $account_hash );

			$bc_accounts->restore_default_account();

		}

		delete_transient( 'brightcove_sync_playlists' );
		delete_transient( 'brightcove_sync_videos' );
		self::delete_transient_key( 'brighcove_sync_playlists' );
		self::delete_transient_key( 'brightcove_sync_videos' );
		delete_option( '_brightcove_plugin_activated' );
	}

	public static function uninstall_plugin() {

		if( ( defined( 'WPCOM_IS_VIP_ENV' ) && WPCOM_IS_VIP_ENV ) && ( ! defined( 'WP_CLI' ) || ! WP_CLI ) ) {
			return false;
		}

		global $wpdb;

		//Delete static options
		delete_option( '_brightcove_pending_videos' );
		delete_option( '_brightcove_salt' );
		delete_option( '_brightcove_accounts' );
		delete_option( '_brightcove_default_account' );

		//Delete synced video data
		$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_brightcove%';" );
		$wpdb->query( "DELETE FROM $wpdb->posts WHERE post_type = 'brightcove-playlist';" );
		$wpdb->query( "DELETE FROM $wpdb->posts WHERE post_type = 'brightcove-video';" );

		//Delete variable options
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_brightcove%';" );
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_bc_player%';" );
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_notifications_subscribed_%';" );
	}

	public static function get_transient_keys() {
		$transient_keys = get_option( '_bc_transient_keys' );
		if ( ! is_array( $transient_keys ) ) {
			update_option( '_bc_transient_keys', array() );
			return array();
		}
		return $transient_keys;
	}

	/**
	 * Takes a dynamically generated transient key and adds to list. We need to store these to unset them on cache purge
	 * @param $key
	 *
	 * @return bool
	 */
	public static function add_transient_key( $key ) {

		$transient_keys = self::get_transient_keys();
		$transient_keys = ( ! $transient_keys ) ? array() : (array) $transient_keys;

		if( in_array( $key, $transient_keys ) ) {
			return true;
		}

		$transient_keys[] = sanitize_key( $key );

		if( update_option( '_bc_transient_keys', $transient_keys ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Takes a dynamically generated transient key and removes from the stored list.
	 * @param $key
	 *
	 * @return bool
	 */
	public static function delete_transient_key( $key ) {
		$transient_keys = self::get_transient_keys();
		if( ! $transient_keys || ! is_array( $transient_keys ) ) {
			return false;
		}

		if( ! array_search( $key, $transient_keys ) ) {
			return false;
		}

		unset( $transient_keys[ $key ] );

		update_option( '_bc_transient_keys', $transient_keys );
	}

	public static function get_requests_transient_key( $account_id ) {

		$keys = array();

		foreach( self::get_transient_keys() as $key ) {
			$regex = '#(_transient__brightcove_req_' . $account_id . '[a-zA-Z0-9-]+)#';
			preg_match( $regex, $key, $matches );
			if( empty( $matches ) ) {
				continue;
			}

			$keys[] = $matches[0];
		}

		return ( empty( $keys ) ) ? false : $keys;
	}
}
