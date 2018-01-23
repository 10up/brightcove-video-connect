<?php

class BC_Utility {

	/**
	 * Returns a string of the video ID
	 *
	 * @param $video_id string containing a video id
	 *
	 * @return string containing video id prefixed by ID_
	 */
	public static function sanitize_and_generate_meta_video_id( $video_id ) {

		return "ID_" . BC_Utility::sanitize_id( $video_id );
	}

	public static function get_sanitized_video_id( $post_id ) {

		$meta_value = get_post_meta( $post_id, '_brightcove_video_id', true );

		return str_replace( 'ID_', '', $meta_value );
	}

	public static function get_sanitized_client_secret( $client_secret ) {

		return is_string( $client_secret ) ? preg_replace( '/[^a-z0-9_-]/i', '', $client_secret ) : '';
	}

	/**
	 * Check if the current user can work with Brightcove videos
	 *
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
	 *
	 * @return string containing integers only
	 */
	public static function sanitize_id( $numeric_string ) {

		return is_string( $numeric_string ) ? sanitize_text_field( preg_replace( '/\D/', '', $numeric_string ) ) : "";
	}

	/**
	 * @param $date_string
	 *
	 * @return string containing integers only
	 */
	public static function sanitize_date( $date_string ) {

		return is_string( $date_string ) ? sanitize_text_field( preg_replace( '/[^0-9-]/', '', $date_string ) ) : "";
	}

	/**
	 * Removes a pending ingestion request (anything over 1 hour old) and any
	 * $video_id that has been supplied.
	 *
	 * @param null $video_id
	 *
	 * @return bool true
	 */
	public static function remove_pending_uploads( $video_id = null ) {

		$video_id       = BC_Utility::sanitize_and_generate_meta_video_id( $video_id );
		$pending_videos = get_option( '_brightcove_pending_videos' );
		$expire_time    = time() - 3600;

		if ( ! is_array( $pending_videos ) ) {
			// Possibly had no pending videos therefore nothing to remove,
			// therefore successfully removed nothing.
			return true;
		}

		foreach ( $pending_videos as $stored_video_id => $metadata ) {
			if ( ( $metadata['added'] < $expire_time ) || ( $stored_video_id === $video_id ) ) {
				unset( $pending_videos[ $stored_video_id ] );
				if ( file_exists( $metadata['filename'] ) ) {
					unlink( $metadata['filename'] );
				}
			}
		}

		update_option( '_brightcove_pending_videos', $pending_videos );

		// Return true as we may not have expired any videos.
		return true;

	}

	/**
	 * @param $account array containing an account id, client id and client secret
	 *
	 * @return string hash for the account
	 */
	public static function get_hash_for_account( $account ) {

		if ( ! $account['account_id'] || ! $account['client_id'] || ! $account['client_secret'] ) {
			return false;
		}

		$account_triplet = array(
			'account_id'    => $account['account_id'],
			'client_id'     => $account['client_id'],
			'client_secret' => $account['client_secret'],
		);

		$hash = BC_Utility::get_hash_for_object( $account_triplet );
		$hash = substr( $hash, 0, 16 );

		return $hash;
	}

	/**
	 * Add pending video ID and uploaded filename to the _brightcove_pending_videos option
	 *
	 * @param        $video_id
	 * @param string $filename
	 *
	 * @return boolean status of update_option
	 */
	public static function add_pending_upload( $video_id, $filename = '' ) {

		$video_id = BC_Utility::sanitize_and_generate_meta_video_id( $video_id );
		BC_Utility::remove_pending_uploads();
		$pending_videos              = get_option( '_brightcove_pending_videos', array() );
		$pending_videos[ $video_id ] = array(
			'filename' => $filename,
			'added'    => time(),
		);

		return update_option( '_brightcove_pending_videos', $pending_videos );
	}

	/**
	 * Returns a hash for an object. Lets us know if data is stale
	 *
	 * @param $obj
	 *
	 * @return string containing hash
	 */
	public static function get_hash_for_object( $object ) {

		BC_Utility::recursive_object_sort( $object );

		return hash( 'sha256', wp_json_encode( $object ) );
	}

	/**
	 * @param $type playlist|video
	 * @param $data sorted playlists|videos associative array
	 *
	 * @return bool true if option value has changed, false on failure/no change
	 */
	public static function store_hash( $type, $data, $account_id ) {

		$key       = "_brightcove_hash_{$type}_{$account_id}";
		$data_hash = BC_Utility::get_hash_for_object( $data );

		return update_option( $key, $data_hash );
	}

	/**
	 * @param $player_id
	 *
	 * @return string option key name in the form of _bc_player_{$player_id}_{$account_id}
	 */
	public static function get_player_key( $player_id ) {

		global $bc_accounts;

		$player_id = BC_Utility::sanitize_player_id( $player_id );

		return "_bc_player_{$player_id}_" . $bc_accounts->get_account_id();
	}

	/**
	 * @param string $type playlists|video|players
	 * @param array  $data sorted playlists|videos|players associative array
	 *
	 * @return bool if stored hash matches calculated hash.
	 */
	public static function hash_changed( $type, $data, $account_id ) {

		$key           = "_brightcove_hash_{$type}_{$account_id}";
		$data_hash     = BC_Utility::get_hash_for_object( $data );
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
	 *
	 * @param $ids_to_keep
	 *
	 * @return bool true if all options deleted, false on failure or non-existent player
	 */
	public static function remove_deleted_players( $ids_to_keep ) {

		global $bc_accounts;
		$all_ids_key = '_bc_player_ids_' . $bc_accounts->get_account_id();
		$all_ids     = get_option( $all_ids_key );

		$all_ids_playlists_key = '_bc_player_playlist_ids_' . $bc_accounts->get_account_id();
		$all_ids_playlists     = get_option( $all_ids_playlists_key );

		$return_state = true;

		if ( is_array( $all_ids ) ) {
			$ids_to_delete = array_diff( $all_ids, $ids_to_keep );

			foreach ( $ids_to_delete as $id ) {
				$key     = BC_Utility::get_player_key( $id );
				$success = delete_option( $key );
				if ( ! $success ) {
					$return_state = false;
				}
			}

		}

		if ( is_array( $all_ids_playlists ) ) {
			foreach ( $all_ids_playlists as $id ) {
				if ( in_array( $id, $all_ids_playlists ) ) {
					unset( $all_ids_playlists[ $id ] );
				}
			}
		}

		update_option( $all_ids_key, $ids_to_keep );
		update_option( $all_ids_playlists_key, $all_ids_playlists );

		return $return_state;
	}

	/**
	 * Sorts arrays, leaves objects as is.
	 *
	 * @param $object
	 *
	 * @return array|bool
	 */
	public static function recursive_object_sort( $object ) {

		if ( ! is_array( $object ) ) {
			return $object;
		}
		foreach ( $object as &$value ) {
			if ( is_array( $value ) ) {
				BC_Utility::recursive_object_sort( $value );
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

		return is_string( $player_id ) ? preg_replace( '/[^0-9a-zA-Z-]/', '', $player_id ) : '';
	}

	public static function sanitize_payload_args_recursive( $args ) {

		foreach ( $args as $index => $value ) {

			if ( is_array( $value ) ) {
				$args[ $index ] = BC_Utility::sanitize_payload_args_recursive( $value );
			} else {
				$args[ $index ] = utf8_uri_encode( sanitize_text_field( $value ) );
			}
		}

		return $args;
	}

	public static function sanitize_payload_item( $item ) {

		if ( is_array( $item ) ) {
			return BC_Utility::sanitize_payload_args_recursive( $item );
		}

		return utf8_uri_encode( sanitize_text_field( $item ) );
	}

	public static function sort_accounts_alphabetically( $account_a, $account_b ) {

		return strnatcmp( $account_a['account_name'], $account_b['account_name'] );
	}

	// Function for storing YYYY-MM for all videos in library
	// If we already have values for a particular $account_id, we add to them.
	public static function set_video_playlist_dates( $type, $media_dates, $account_id ) {

		if ( ! in_array( $type, array( 'videos', 'playlists' ) ) || ! $account_id || ! is_array( $media_dates ) ) {
			return false;
		}
		$all_dates = BC_Utility::get_video_playlist_dates( $type );
		$key       = '_brightcove_dates_' . $type;
		$id        = BC_Utility::sanitize_and_generate_meta_video_id( $account_id );
		if ( array_key_exists( $id, $all_dates ) && is_array( $all_dates[ $id ] ) ) {
			// Check number of dates before we add these.
			$date_count       = count( $all_dates[ $id ] );
			$all_dates[ $id ] = array_unique( array_merge( $all_dates[ $id ], $media_dates ) );

			// If the count hasn't changed then we don't have to set the new dates since they're already reflected.
			if ( $date_count === count( $all_dates[ $id ] ) ) {
				return true;
			}
		} else {
			$all_dates[ $id ] = $media_dates;
		}
		$all_dates_for_all_accounts = array();
		foreach ( $all_dates as $all_dates_key => $dates ) {
			$all_dates_for_all_accounts = array_merge( $all_dates_for_all_accounts, $dates );
		}
		$all_dates['all'] = array_unique( $all_dates_for_all_accounts );

		update_option( $key, $all_dates );
	}

	public static function get_video_playlist_dates( $type, $account_id = false ) {

		if ( ! in_array( $type, array( 'videos', 'playlists' ) ) ) {
			return false;
		}

		$key       = '_brightcove_dates_' . $type;
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

		$all_dates = BC_Utility::get_video_playlist_dates( $type );
		foreach ( $all_dates as $id => $dates_for_id ) {
			$new_id         = $id === 'all' ? 'all' : BC_Utility::get_sanitized_video_id( $id ); // Strip ID_
			$labelled_dates = array();
			foreach ( $dates_for_id as $yyyy_mm ) {
				$date_object      = new DateTime( $yyyy_mm . '-01' );
				$labelled_dates[] = array(
					'code'  => $yyyy_mm,
					'value' => $date_object->format( 'F Y' ),
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
			'avi'   => 'video/avi',
			'mov'   => 'video/quicktime',
		);
	}

	/**
	 * Used for removing all removed objects from a Brightcove sync
	 *
	 * @param $id         post id
	 * @param $account_id account id that the video/playlist ID is associated with
	 *
	 * @return mixed
	 */
	public static function remove_object( $id, $account_id ) {

		if ( $account_id !== get_post_meta( $id, '_brightcove_account_id', true ) ) {
			// We've switched accounts, don't delete any of the posts, set them to private.
			$update = array(
				'ID'          => $id,
				'post_status' => 'private',
			);

			return wp_update_post( $update );
		} else {
			return wp_delete_post( $id, true );
		}
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
			'<a href="' . esc_url( admin_url( 'admin.php?page=brightcove-sources' ) ) . '">' . esc_html__( 'Settings', 'brightcove') . '</a>',
		);

		return array_merge( $links, $bc_settings_page );
	}

	/**
	 * Wrapper utility method for using WordPress.com get_user_attribute() when available. Falls back to get_user_meta()
	 *
	 * @param           $user_id
	 * @param           $meta_key
	 * @param bool|true $single
	 *
	 * @return mixed
	 */
	public static function get_user_meta( $user_id, $meta_key, $single = true ) {

		if ( defined( 'WPCOM' ) && IS_WPCOM ) {
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

		if ( function_exists( 'update_user_attribute' ) ) {
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

		if ( function_exists( 'delete_user_attribute' ) ) {
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

		require_once( BRIGHTCOVE_PATH . 'includes/class-bc-accounts.php' );

		$bc_accounts = new BC_Accounts();

		$accounts = $bc_accounts->get_sanitized_all_accounts();

		foreach ( $accounts as $account => $account_data ) {

			$bc_accounts->set_current_account( $account );

			$account_hash = $bc_accounts->get_account_hash();

			self::delete_cache_item( 'brightcove_oauth_access_token_' . $account_hash );

			$bc_accounts->restore_default_account();

		}

		self::delete_cache_item( 'brightcove_sync_playlists' );
		self::delete_cache_item( 'brightcove_sync_videos' );
		delete_option( '_brightcove_plugin_activated' );
	}

	public static function uninstall_plugin() {

		if ( ( defined( 'WPCOM_IS_VIP_ENV' ) && WPCOM_IS_VIP_ENV ) && ( ! defined( 'WP_CLI' ) || ! WP_CLI ) ) {
			return false;
		}

		global $wpdb;

		// Delete static options.
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

	/**
	 * Retrieves transient keys
	 *
	 * Retrieves a list of all transient keys currently stored.
	 *
	 * @since 1.1.1
	 *
	 * @return array Array of transient keys
	 */
	public static function list_cache_items() {

		$transient_keys = get_option( 'bc_transient_keys' );

		if ( ! is_array( $transient_keys ) ) {
			$transient_keys = array();
		}

		return $transient_keys;

	}

	/**
	 * Store cache item
	 *
	 * Stores an item to transient cache for later use.
	 *
	 * @param string $key        The generated transient key.
	 * @param string $type       The type of key to store.
	 * @param mixed  $value      The value of the item to cache.
	 * @param int    $expiration The number of seconds the item should be cached for.
	 *
	 * @since 1.1.1
	 *
	 * @return int 1 on success, 0 on failure or -1 if key is already cached
	 */
	public static function set_cache_item( $key, $type, $value, $expiration = 600 ) {

		// Allow for the complete bypass of the caching system for development purposes.
		if ( defined( 'BRIGHTCOVE_BYPASS_CACHE' ) && true === BRIGHTCOVE_BYPASS_CACHE ) {
			return 1;
		}

		$key        = sanitize_key( $key );
		$type       = sanitize_text_field( $type );
		$expiration = absint( $expiration );

		$transient_keys = self::list_cache_items();

		if ( in_array( $key, $transient_keys ) && get_transient( $key ) ) {
			return - 1; // Key already cached.
		}

		if ( set_transient( sanitize_key( $key ), $value, $expiration ) ) {

			$transient_keys[ sanitize_key( $key ) ] = sanitize_text_field( $type );

		} else { // For some reason we couldn't save the transient

			return 0;

		}

		if ( update_option( 'bc_transient_keys', $transient_keys, false ) ) {
			return 1; // Key saved to Brightcove registry.
		}

		return 0;

	}

	/**
	 * Delete cache item
	 *
	 * Deletes a cached item from the cache and cache registry.
	 *
	 * @since 1.1.1
	 *
	 * @param string $key  The cache key or * for all.
	 * @param string $type The type of cache key (for group cleanup).
	 *
	 * @return bool True on success or false.
	 */
	public static function delete_cache_item( $key = '', $type = '' ) {

		// Check that valid item was given.
		if ( '' === $key && '' === $type ) {
			return false;
		}

		$transient_keys = self::list_cache_items();
		$transients     = array();

		if ( '*' === $key ) { // Clear all saved cache items.

			foreach ( $transient_keys as $transient_key => $transient_value ) {
				delete_transient( $transient_key );
			}

			delete_option( 'bc_transient_keys' );

		} else { // Only clear specified items.

			if ( ! $transient_keys || ! is_array( $transient_keys ) ) {
				return false;
			}

			// If a specific key is set arrange it for clearing.
			if ( '' !== $key ) {

				$key = sanitize_key( $key );

				if ( ! array_search( $key, $transient_keys ) ) {
					return false;
				}

				unset( $transient_keys[ $key ] );
				$transients[] = $key;

			}

			// If type is set clear by type.
			if ( '' !== $type ) {

				$type = sanitize_text_field( $type );

				foreach ( $transient_keys as $transient_key => $transient_type ) {

					if ( $type === $transient_type ) {
						$transients[] = $transient_key;
					}
				}
			}

			foreach ( $transients as $key ) {
				delete_transient( $key );
			}

		}

		return update_option( 'bc_transient_keys', $transient_keys, false );

	}

	/**
	 * Fetch cached item
	 *
	 * Fetches item from cache and prunes cache registry if expired.
	 *
	 * @since 1.1.1
	 *
	 * @param string $key The cache key to retrieve.
	 *
	 * @return mixed The cached item's value (or FALSE if not found).
	 */
	public static function get_cache_item( $key ) {

		// Allow for the complete bypass of the caching system for development purposes.
		if ( defined( 'BRIGHTCOVE_BYPASS_CACHE' ) && true === BRIGHTCOVE_BYPASS_CACHE ) {
			return false;
		}

		$key = sanitize_key( $key );

		$transient = get_transient( $key );

		if ( false === $transient ) { // Delete if from the list if the transient has expired.

			$transient_keys = self::list_cache_items();

			unset( $transient_keys[ $key ] );

			update_option( 'bc_transient_keys', $transient_keys, false );

		}

		return $transient;

	}

	public static function get_requests_transient_key( $account_id ) {

		$keys = array();

		foreach ( self::list_cache_items() as $key ) {
			$regex = '#(_transient__brightcove_req_' . $account_id . '[a-zA-Z0-9-]+)#';
			preg_match( $regex, $key, $matches );
			if ( empty( $matches ) ) {
				continue;
			}

			$keys[] = $matches[0];
		}

		return ( empty( $keys ) ) ? false : $keys;
	}

	/**
	 * Render Video Player.
	 *
	 * Renders the  player from Brightcove based on passed parameters
	 *
	 * @since 1.4
	 *
	 * @param array $atts The shortcode attributes.
	 *
	 * @return string The HTML code for the player
	 */
	public static function get_video_player( $atts ) {
		$account_id  = BC_Utility::sanitize_id( $atts['account_id'] );
		$player_id   = BC_Utility::sanitize_player_id( $atts['player_id'] );
		$id          = BC_Utility::sanitize_id( $atts['video_id'] );
		$height      = sanitize_text_field( $atts['height'] );
		$width       = sanitize_text_field( $atts['width'] );
		$min_width   = sanitize_text_field( $atts['min_width'] );
		$max_width   = sanitize_text_field( $atts['max_width'] );
		$padding_top = sanitize_text_field( $atts['padding_top'] );
		$autoplay    = ( 'autoplay' === $atts['autoplay'] ) ? 'autoplay' : '';
		$embed       = sanitize_text_field( $atts['embed'] );

		ob_start();
		?>
		<!-- Start of Brightcove Player -->

		<?php if ( 'in-page' === $embed ) : ?>
			<div style="display: block; position: relative; min-width: <?php echo esc_attr( $min_width ); ?>; max-width: <?php echo esc_attr( $max_width ); ?>;">
				<div style="padding-top: <?php echo esc_attr( $padding_top ); ?>; ">
					<video
							data-video-id="<?php echo esc_attr( $id ); ?>" data-account="<?php echo esc_attr( $account_id ); ?>"
							data-player="<?php echo esc_attr( $player_id ); ?>"
							data-usage="<?php echo esc_attr( self::get_usage_data() ); ?>javascript"
							data-embed="default" class="video-js"
							controls <?php echo esc_attr( $autoplay ); ?>
							style="width: <?php echo esc_attr( $width ); ?>; height: <?php echo esc_attr( $height ); ?>; position: absolute; top: 0; bottom: 0; right: 0; left: 0;">
					</video>

					<script src="//players.brightcove.net/<?php echo esc_attr( $account_id ); ?>/<?php echo esc_attr( $player_id ); ?>_default/index.min.js"></script>
				</div>
			</div>

		<?php elseif ( 'iframe' === $embed ) : ?>
			<?php
			if ( ! empty( $autoplay ) ) {
				$autoplay = '&' . $autoplay;
			}
			?>

			<div style="display: block; position: relative; min-width: <?php echo esc_attr( $min_width ); ?>; max-width: <?php echo esc_attr( $max_width ); ?>;">
				<div style="padding-top: <?php echo esc_attr( $padding_top ); ?>; ">
					<iframe
							src="//players.brightcove.net/<?php echo esc_attr( $account_id ); ?>/<?php echo esc_attr( $player_id ); ?>_default/index.html?videoId=<?php echo esc_attr( $id ); ?>&usage=<?php echo esc_attr( self::get_usage_data() ); ?>iframe<?php echo esc_attr( $autoplay ); ?>"
							allowfullscreen
							webkitallowfullscreen
							mozallowfullscreen
							style="width: <?php echo esc_attr( $width ); ?>; height: <?php echo esc_attr( $height ); ?>; position: absolute; top: 0; bottom: 0; right: 0; left: 0;">
					</iframe>
				</div>
			</div>
		<?php else : ?>

			<?php if ( '0' === $width && '0' === $height ) : ?>
				<div style="display: block; position: relative; max-width: 100%;"><div style="padding-top: 56.25%;">
			<?php endif; ?>

			<?php
			printf(
				'<iframe src="//players.brightcove.net/%s/%s_default/index.html?%sId=%s&%s" allowfullscreen="" webkitallowfullscreen="" mozallowfullscreen="" style="width: %s; height: %s;%s"></iframe>',
				$account_id,
				$player_id,
				'video',
				$id,
				esc_attr( self::get_usage_data() ) . 'iframe',
				( '0' === $width ) ? '100%' : $width . 'px',
				( '0' === $height ) ? '100%' : $height . 'px',
				( '0' === $width && '0' === $height ) ? 'position: absolute; top: 0px; bottom: 0px; right: 0px; left: 0px;' : ''
			);
			?>

			<?php if ( '0' === $width && '0' === $height ) : ?>
				</div></div>
			<?php endif; ?>

		<?php endif; ?>

		<!-- End of Brightcove Player -->

		<?php
		$html = ob_get_clean();

		/**
		 * Filter the Brightcove Player HTML.
		 *
		 * @param string  $html       HTML markup of the Brightcove Player.
		 * @param string  $type       "playlist" or "video".
		 * @param string  $id         The brightcove player or video ID.
		 * @param string  $account_id The Brightcove account ID.
		 * @param string  $player_id  The brightcove player ID.
		 * @param int     $width      The Width to display.
		 * @param int     $height     The height to display.
		 */
		$html = apply_filters( 'brightcove_video_html', $html, 'video', $id, $account_id, $player_id, $width, $height );

		return $html;
	}

	/**
	 * Render Playlist Player.
	 *
	 * Renders the playlist player from Brightcove based on passed parameters.
	 *
	 * @since 1.4
	 *
	 * @param array  $atts The shortcode attributes.
	 *
	 * @return string The HTML code for the player
	 */
	public static function get_playlist_player( $atts ) {
		$account_id  = BC_Utility::sanitize_id( $atts['account_id'] );
		$player_id   = BC_Utility::sanitize_player_id( $atts['player_id'] );
		$id          = BC_Utility::sanitize_id( $atts['playlist_id'] );
		$height      = sanitize_text_field( $atts['height'] );
		$width       = sanitize_text_field( $atts['width'] );
		$min_width   = sanitize_text_field( $atts['min_width'] );
		$max_width   = sanitize_text_field( $atts['max_width'] );
		$padding_top = sanitize_text_field( $atts['padding_top'] );
		$autoplay    = ( 'autoplay' === $atts['autoplay'] ) ? 'autoplay' : '';
		$embed       = sanitize_text_field( $atts['embed'] );

		if ( 'default' === $player_id ) {

			$player_api = new BC_Player_Management_API();
			$players    = $player_api->player_list_playlist_enabled();

			if ( is_wp_error( $players ) || ! is_array( $players ) || $players['item_count'] < 1 ) {
				return '<div class="brightcove-player-warning">' . __( 'A specified Source does not have a playlist capable player <a href="https://studio.brightcove.com/products/videocloud/players/">configured</a>. Make sure there is at least one player with "Display Playlist" enabled.', 'brightcove' ) . '</div>';
			}

			$player_id = esc_attr( $players['items'][0]['id'] );

		}

		ob_start();
		?>
		<!-- Start of Brightcove Player -->

		<?php if ( 'in-page-vertical' === $embed ) : ?>
			<style type="text/css">
				.video-js {
					width: <?php echo esc_attr( $width ); ?>;
					height: <?php echo esc_attr( $height ); ?>;
					float: left;
				}
				.bcplayer {
					width: <?php echo esc_attr( $width ); ?>;
					position: relative;
				}
				.playlist-wrapper {
					width: <?php echo esc_attr( $width ); ?>;
					overflow-y: hidden;
				}
				vjs-playlist vjs-csspointerevents vjs-mouse {
					width: <?php echo esc_attr( $width ); ?>;
				}
				.vjs-playlist-item-list {
					/* 3 is the number of thumbnails to show, height is thumbnail heights plus padding */
					height: calc(<?php echo esc_attr( $height ); ?> * 0.2 * 3 + 4px * 3);
					position: relative;
				}
				.vjs-playlist-item-list .vjs-playlist-item {
					height: calc(<?php echo esc_attr( $height ); ?> * 0.2);
				}
				.vjs-playlist-item-list .vjs-playlist-item .vjs-playlist-thumbnail {
					width: calc(<?php echo esc_attr( $width ); ?> * 0.2);
				}
				.vjs-playlist-item-list .vjs-playlist-item .vjs-playlist-thumbnail vjs-playlist-title-container {
					margin-left: calc(<?php echo esc_attr( $width ); ?> * 0.2);
					width: calc(<?php echo esc_attr( $width ); ?> * 0.8 - 30px);
				}
				.vjs-playlist-item-list .vjs-playlist-item .vjs-playlist-thumbnail .vjs-playlist-now-playing-text {
					margin-left: calc(<?php echo esc_attr( $width ); ?> * 0.2 + 6px);
					width: calc(<?php echo esc_attr( $width ); ?> * 0.8 - 30px);
				}
				.vjs-playlist-item.vjs-selected {
					background: rgb(45, 45, 45);
				}
			</style>

			<div class="bcplayer">
				<video
						data-playlist-id="<?php echo esc_attr( $id ); ?>"
						data-account="<?php echo esc_attr( $account_id ); ?>"
						data-player="<?php echo esc_attr( $player_id ); ?>"
						data-embed="default"
						data-application-id
						data-usage="<?php echo esc_attr( self::get_usage_data() ); ?>javascript"
						class="video-js"
						controls <?php echo esc_attr( $autoplay ); ?>>
				</video>
				<script src="//players.brightcove.net/<?php echo esc_attr( $account_id ); ?>/<?php echo esc_attr( $player_id ); ?>_default/index.min.js"></script>
				<div class="playlist-wrapper">
					<ol class="vjs-playlist vjs-csspointerevents vjs-mouse"> </ol>
				</div>
			</div>

		<?php elseif ( 'in-page-horizontal' === $embed ) : ?>
			<style type="text/css">
				.video-js {
					width: <?php echo esc_attr( $width ); ?>;
					height: <?php echo esc_attr( $height ); ?>;
					float: left;
				}
				.bcplayer {
					width: <?php echo esc_attr( $width ); ?>;
					height: calc (<?php echo esc_attr( $height ); ?> + 110px);
					position: relative;
				}
				.playlist-wrapper {
					width: <?php echo esc_attr( $width ); ?>;
					height: 110px;
					overflow-x: hidden;
					overflow-y: hidden;
				}
				.vjs-playlist.vjs-playlist {
					width: auto;
					white-space: nowrap;
					overflow-y: hidden;
				}
				.vjs-playlist-item-list {
					height: 75px;
				}
				.vjs-playlist-item {
					display: inline-block;
					height: 75px;
				}
				cite.vjs-playlist-name {
					display: none;
				}
				.vjs-playlist-description {
					display: none;
				}
			</style>

			<div class="bcplayer">
				<video
						data-playlist-id="<?php echo esc_attr( $id ); ?>"
						data-account="<?php echo esc_attr( $account_id ); ?>"
						data-player="<?php echo esc_attr( $player_id ); ?>"
						data-embed="default"
						data-application-id
						data-usage="<?php echo esc_attr( self::get_usage_data() ); ?>javascript"
						class="video-js"
						controls <?php echo esc_attr( $autoplay ); ?>>
				</video>
				<script src="//players.brightcove.net/<?php echo esc_attr( $account_id ); ?>/<?php echo esc_attr( $player_id ); ?>_default/index.min.js"></script>
				<div class="playlist-wrapper">
					<ol class="vjs-playlist vjs-csspointerevents vjs-mouse"> </ol>
				</div>
			</div>
		<?php elseif ( 'iframe' === $embed ) : ?>
			<?php
			if ( ! empty( $autoplay ) ) {
				$autoplay = '&' . $autoplay;
			}
			?>

			<div style="display: block; position: relative; min-width: <?php echo esc_attr( $min_width ); ?>; max-width: <?php echo esc_attr( $max_width ); ?>;">
				<div style="padding-top: <?php echo esc_attr( $padding_top ); ?>; ">
					<iframe
						src="//players.brightcove.net/<?php echo esc_attr( $account_id ); ?>/<?php echo esc_attr( $player_id ); ?>_default/index.html?playlistId=<?php echo esc_attr( $id ); ?>&usage=<?php echo esc_attr( self::get_usage_data() ); ?>iframe<?php echo esc_attr( $autoplay ); ?>"
						allowfullscreen
						webkitallowfullscreen
						mozallowfullscreen
						style="width: <?php echo esc_attr( $width ); ?>; height: <?php echo esc_attr( $height ); ?>; position: absolute; top: 0; bottom: 0; right: 0; left: 0;">
					</iframe>
				</div>
			</div>
		<?php else : ?>

			<?php if ( '0' === $width && '0' === $height ) : ?>
				<div style="display: block; position: relative; max-width: 100%;"><div style="padding-top: 56.25%;">
			<?php endif; ?>

			<?php
			printf(
				'<iframe src="//players.brightcove.net/%s/%s_default/index.html?%sId=%s&%s" allowfullscreen="" webkitallowfullscreen="" mozallowfullscreen="" style="width: %s; height: %s;%s"></iframe>',
				$account_id,
				$player_id,
				'playlist',
				$id,
				esc_attr( self::get_usage_data() ) . 'iframe',
				( '0' === $width ) ? '100%' : $width . 'px',
				( '0' === $height ) ? '100%' : $height . 'px',
				( '0' === $width && '0' === $height ) ? 'position: absolute; top: 0px; bottom: 0px; right: 0px; left: 0px;' : ''
			);
			?>

			<?php if ( '0' === $width && '0' === $height ) : ?>
				</div></div>
			<?php endif; ?>

		<?php endif; ?>

		<!-- End of Brightcove Player -->

		<?php
		$html = ob_get_clean();

		/**
		 * Filter the Brightcove Player HTML.
		 *
		 * @param string  $html       HTML markup of the Brightcove Player.
		 * @param string  $type       "playlist" or "video".
		 * @param string  $id         The brightcove player or video ID.
		 * @param string  $account_id The Brightcove account ID.
		 * @param string  $player_id  The brightcove player ID.
		 * @param int     $width      The Width to display.
		 * @param int     $height     The height to display.
		 */
		return apply_filters( 'brightcove_video_html', $html, 'playlist', $id, $account_id, $player_id, $width, $height );
	}

	/**
	 * Return usage screen.
	 *
	 * @since 1.4
	 *
	 * @return string Usage screen.
	 */
	public static function get_usage_data() {
		global $wp_version;

		return 'cms:wordpress:' . $wp_version . ':' . BRIGHTCOVE_VERSION . ':';
	}

	/**
	 * Create a JSON object of supported languages.
	 *
	 * @since 1.2.0
	 */
	public static function languages() {
		$languages = array(
			esc_html__( 'English', 'brightcove' )          => 'en',
			esc_html__( 'Abkhaz', 'brightcove' )           => 'ab',
			esc_html__( 'Afar', 'brightcove' )             => 'aa',
			esc_html__( 'Afrikaans', 'brightcove' )        => 'af',
			esc_html__( 'Akan', 'brightcove' )             => 'ak',
			esc_html__( 'Albanian', 'brightcove' )         => 'sq',
			esc_html__( 'Amharic', 'brightcove' )          => 'am',
			esc_html__( 'Arabic', 'brightcove' )           => 'ar',
			esc_html__( 'Aragonese', 'brightcove' )        => 'an',
			esc_html__( 'Armenian', 'brightcove' )         => 'hy',
			esc_html__( 'Assamese', 'brightcove' )         => 'as',
			esc_html__( 'Avaric', 'brightcove' )           => 'av',
			esc_html__( 'Avestan', 'brightcove' )          => 'ae',
			esc_html__( 'Aymara', 'brightcove' )           => 'ay',
			esc_html__( 'Azerbaijani', 'brightcove' )      => 'az',
			esc_html__( 'Bambara', 'brightcove' )          => 'bm',
			esc_html__( 'Bashkir', 'brightcove' )          => 'ba',
			esc_html__( 'Basque', 'brightcove' )           => 'eu',
			esc_html__( 'Belarusian', 'brightcove' )       => 'be',
			esc_html__( 'Bengali', 'brightcove' )          => 'bn',
			esc_html__( 'Bihari', 'brightcove' )           => 'bh',
			esc_html__( 'Bislama', 'brightcove' )          => 'bi',
			esc_html__( 'Bosnian', 'brightcove' )          => 'bs',
			esc_html__( 'Breton', 'brightcove' )           => 'br',
			esc_html__( 'Bulgarian', 'brightcove' )        => 'bg',
			esc_html__( 'Burmese', 'brightcove' )          => 'my',
			esc_html__( 'Catalan', 'brightcove' )          => 'ca',
			esc_html__( 'Chomorro', 'brightcove' )         => 'ch',
			esc_html__( 'Chechen', 'brightcove' )          => 'ce',
			esc_html__( 'Chichewa', 'brightcove' )         => 'ny',
			esc_html__( 'Chinese', 'brightcove' )          => 'zh',
			esc_html__( 'Chuvash', 'brightcove' )          => 'cv',
			esc_html__( 'Cornish', 'brightcove' )          => 'kw',
			esc_html__( 'Corsican', 'brightcove' )         => 'co',
			esc_html__( 'Cree', 'brightcove' )             => 'cr',
			esc_html__( 'Croatian', 'brightcove' )         => 'hr',
			esc_html__( 'Czech', 'brightcove' )            => 'cs',
			esc_html__( 'Danish', 'brightcove' )           => 'da',
			esc_html__( 'Divehi', 'brightcove' )           => 'dv',
			esc_html__( 'Dutch', 'brightcove' )            => 'nl',
			esc_html__( 'Dzongkha', 'brightcove' )         => 'dz',
			esc_html__( 'Esperanto', 'brightcove' )        => 'eo',
			esc_html__( 'Estonian', 'brightcove' )         => 'et',
			esc_html__( 'Ewe', 'brightcove' )              => 'ee',
			esc_html__( 'Faroese', 'brightcove' )          => 'fo',
			esc_html__( 'Fijian', 'brightcove' )           => 'fj',
			esc_html__( 'Finnish', 'brightcove' )          => 'fi',
			esc_html__( 'French', 'brightcove' )           => 'fr',
			esc_html__( 'Fula', 'brightcove' )             => 'ff',
			esc_html__( 'Galician', 'brightcove' )         => 'gl',
			esc_html__( 'Georgian', 'brightcove' )         => 'ka',
			esc_html__( 'German', 'brightcove' )           => 'de',
			esc_html__( 'Greek', 'brightcove' )            => 'el',
			esc_html__( 'Guarani', 'brightcove' )          => 'gn',
			esc_html__( 'Gujarati', 'brightcove' )         => 'gu',
			esc_html__( 'Haitian', 'brightcove' )          => 'ht',
			esc_html__( 'Hausa', 'brightcove' )            => 'ha',
			esc_html__( 'Hebrew', 'brightcove' )           => 'he',
			esc_html__( 'Herero', 'brightcove' )           => 'hz',
			esc_html__( 'Hindi', 'brightcove' )            => 'hi',
			esc_html__( 'Hiri Motu', 'brightcove' )        => 'ho',
			esc_html__( 'Hungarian', 'brightcove' )        => 'hu',
			esc_html__( 'Interlingua', 'brightcove' )      => 'ia',
			esc_html__( 'Indonesian', 'brightcove' )       => 'id',
			esc_html__( 'Irish', 'brightcove' )            => 'ga',
			esc_html__( 'Igbo', 'brightcove' )             => 'ig',
			esc_html__( 'Inupiaq', 'brightcove' )          => 'ik',
			esc_html__( 'Icelandic', 'brightcove' )        => 'is',
			esc_html__( 'Italian', 'brightcove' )          => 'it',
			esc_html__( 'Inuktitut', 'brightcove' )        => 'iu',
			esc_html__( 'Japanese', 'brightcove' )         => 'ja',
			esc_html__( 'Javanese', 'brightcove' )         => 'jv',
			esc_html__( 'Kalaallisut', 'brightcove' )      => 'kl',
			esc_html__( 'Kannada', 'brightcove' )          => 'kn',
			esc_html__( 'Kanuri', 'brightcove' )           => 'kr',
			esc_html__( 'Kashmiri', 'brightcove' )         => 'ks',
			esc_html__( 'Kazakh', 'brightcove' )           => 'kk',
			esc_html__( 'Khmer', 'brightcove' )            => 'km',
			esc_html__( 'Kikuyu', 'brightcove' )           => 'ki',
			esc_html__( 'Kinyarwanda', 'brightcove' )      => 'rw',
			esc_html__( 'Kyrgyz', 'brightcove' )           => 'ky',
			esc_html__( 'Komi', 'brightcove' )             => 'kv',
			esc_html__( 'Kongo', 'brightcove' )            => 'kg',
			esc_html__( 'Korean', 'brightcove' )           => 'ko',
			esc_html__( 'Kurdish', 'brightcove' )          => 'ku',
			esc_html__( 'Kwanyama', 'brightcove' )         => 'kj',
			esc_html__( 'Latin', 'brightcove' )            => 'la',
			esc_html__( 'Luxembourgish', 'brightcove' )    => 'lb',
			esc_html__( 'Ganda', 'brightcove' )            => 'lg',
			esc_html__( 'Limburgish', 'brightcove' )       => 'li',
			esc_html__( 'Lingala', 'brightcove' )          => 'ln',
			esc_html__( 'Lao', 'brightcove' )              => 'lo',
			esc_html__( 'Lithuanian', 'brightcove' )       => 'lt',
			esc_html__( 'Luba-Katanga', 'brightcove' )     => 'lu',
			esc_html__( 'Latvian', 'brightcove' )          => 'lv',
			esc_html__( 'Manx', 'brightcove' )             => 'gv',
			esc_html__( 'Macedonian', 'brightcove' )       => 'mk',
			esc_html__( 'Malagasy', 'brightcove' )         => 'mg',
			esc_html__( 'Malay', 'brightcove' )            => 'ms',
			esc_html__( 'Malayalam', 'brightcove' )        => 'ml',
			esc_html__( 'Maltese', 'brightcove' )          => 'mt',
			esc_html__( 'Maori', 'brightcove' )            => 'mi',
			esc_html__( 'Marathi', 'brightcove' )          => 'mr',
			esc_html__( 'Marshallese', 'brightcove' )      => 'mh',
			esc_html__( 'Mongolian', 'brightcove' )        => 'mn',
			esc_html__( 'Nauruan', 'brightcove' )          => 'na',
			esc_html__( 'Navajo', 'brightcove' )           => 'nv',
			esc_html__( 'Northern Ndebele', 'brightcove' ) => 'nd',
			esc_html__( 'Nepali', 'brightcove' )           => 'ne',
			esc_html__( 'Ndonga', 'brightcove' )           => 'ng',
			esc_html__( 'Norwegian Bokmal', 'brightcove' ) => 'nb',
			esc_html__( 'Norwegian Nyorsk', 'brightcove' ) => 'nn',
			esc_html__( 'Norwegian', 'brightcove' )        => 'no',
			esc_html__( 'Nuosu', 'brightcove' )            => 'ii',
			esc_html__( 'Southern Ndebele', 'brightcove' ) => 'nr',
			esc_html__( 'Occitan', 'brightcove' )          => 'oc',
			esc_html__( 'Ojibwe', 'brightcove' )           => 'oj',
			esc_html__( 'Oromo', 'brightcove' )            => 'om',
			esc_html__( 'Oriya', 'brightcove' )            => 'or',
			esc_html__( 'Ossetian', 'brightcove' )         => 'os',
			esc_html__( 'Panjabi', 'brightcove' )          => 'pa',
			esc_html__( 'Pali', 'brightcove' )             => 'pi',
			esc_html__( 'Persian', 'brightcove' )          => 'fa',
			esc_html__( 'Polish', 'brightcove' )           => 'pl',
			esc_html__( 'Pashto', 'brightcove' )           => 'ps',
			esc_html__( 'Portuguese', 'brightcove' )       => 'pt',
			esc_html__( 'Quechua', 'brightcove' )          => 'qu',
			esc_html__( 'Romanian', 'brightcove' )         => 'ro',
			esc_html__( 'Romanish', 'brightcove' )         => 'rm',
			esc_html__( 'Russian', 'brightcove' )          => 'ru',
			esc_html__( 'Sanskrit', 'brightcove' )         => 'sa',
			esc_html__( 'Sardinian', 'brightcove' )        => 'sc',
			esc_html__( 'Sindhi', 'brightcove' )           => 'sd',
			esc_html__( 'Northern Sami', 'brightcove' )    => 'se',
			esc_html__( 'Samoan', 'brightcove' )           => 'sm',
			esc_html__( 'Sango', 'brightcove' )            => 'sg',
			esc_html__( 'Serbian', 'brightcove' )          => 'sr',
			esc_html__( 'Scottish Gaelic', 'brightcove' )  => 'gd',
			esc_html__( 'Shona', 'brightcove' )            => 'sn',
			esc_html__( 'Sinhala', 'brightcove' )          => 'si',
			esc_html__( 'Slovak', 'brightcove' )           => 'sk',
			esc_html__( 'Slovene', 'brightcove' )          => 'sl',
			esc_html__( 'Somali', 'brightcove' )           => 'so',
			esc_html__( 'Southern Sotho', 'brightcove' )   => 'st',
			esc_html__( 'Spanish', 'brightcove' )          => 'es',
			esc_html__( 'Sudanese', 'brightcove' )         => 'su',
			esc_html__( 'Swahili', 'brightcove' )          => 'sw',
			esc_html__( 'Swati', 'brightcove' )            => 'ss',
			esc_html__( 'Swedish', 'brightcove' )          => 'sv',
			esc_html__( 'Tamil', 'brightcove' )            => 'ta',
			esc_html__( 'Telugu', 'brightcove' )           => 'te',
			esc_html__( 'Tajik', 'brightcove' )            => 'tg',
			esc_html__( 'Thai', 'brightcove' )             => 'th',
			esc_html__( 'Tigrinya', 'brightcove' )         => 'ti',
			esc_html__( 'Tibetan', 'brightcove' )          => 'bo',
			esc_html__( 'Turkmen', 'brightcove' )          => 'tk',
			esc_html__( 'Tagalog', 'brightcove' )          => 'tl',
			esc_html__( 'Tswana', 'brightcove' )           => 'tn',
			esc_html__( 'Tonga', 'brightcove' )            => 'to',
			esc_html__( 'Turkish', 'brightcove' )          => 'tr',
			esc_html__( 'Tsonga', 'brightcove' )           => 'ts',
			esc_html__( 'Tatar', 'brightcove' )            => 'tt',
			esc_html__( 'Twi', 'brightcove' )              => 'tw',
			esc_html__( 'Tahitian', 'brightcove' )         => 'ty',
			esc_html__( 'Uyghur', 'brightcove' )           => 'ug',
			esc_html__( 'Ukrainian', 'brightcove' )        => 'uk',
			esc_html__( 'Urdu', 'brightcove' )             => 'ur',
			esc_html__( 'Uzbek', 'brightcove' )            => 'uz',
			esc_html__( 'Venda', 'brightcove' )            => 've',
			esc_html__( 'Vietnamese', 'brightcove' )       => 'vi',
			esc_html__( 'Volapuk', 'brightcove' )          => 'vo',
			esc_html__( 'Walloon', 'brightcove' )          => 'wa',
			esc_html__( 'Welsh', 'brightcove' )            => 'cy',
			esc_html__( 'Wolof', 'brightcove' )            => 'wo',
			esc_html__( 'Western Frisian', 'brightcove' )  => 'fy',
			esc_html__( 'Xhosa', 'brightcove' )            => 'xh',
			esc_html__( 'Yiddish', 'brightcove' )          => 'yi',
			esc_html__( 'Yoruba', 'brightcove' )           => 'yo',
			esc_html__( 'Zhuang', 'brightcove' )           => 'za',
			esc_html__( 'Zulu', 'brightcove' )             => 'zu',
		);

		return $languages;
	}
}
