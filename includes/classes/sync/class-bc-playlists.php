<?php

class BC_Playlists {

	protected $cms_api;

	/**
	 * List of IDs handled during sync operation.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $playlist_ids;

	public function __construct() {

		$this->cms_api      = new BC_CMS_API();
		$this->playlist_ids = array();

		/**
		 * With Force Sync option, we allow the syncing to happen as part of the
		 * page load, otherwise we just let the uploads, and video edit notifications
		 * to trigger sync actions
		 */

		if ( defined( 'BRIGHTCOVE_FORCE_SYNC' ) && BRIGHTCOVE_FORCE_SYNC ) {
			add_action( 'admin_init', array( $this, 'sync_playlists' ) );
		}
	}

	/**
	 * Updates Metadata to the Brightcove API
	 *
	 * @param array $sanitized_post_data . This should be sanitized POST data
	 *
	 * @return bool|WP_Error
	 */
	public function update_bc_playlist( $sanitized_post_data ) {

		global $bc_accounts;
		if ( ! wp_verify_nonce( $_POST['nonce'], '_bc_ajax_search_nonce' ) ) {
			return false;
		}

		$playlist_id = BC_Utility::sanitize_id( $sanitized_post_data['playlist_id'] );

		$update_data = array(
			'type' => 'EXPLICIT',
		);

		if ( array_key_exists( 'name', $sanitized_post_data ) && '' !== $sanitized_post_data['name'] ) {
			$update_data['name'] = utf8_uri_encode( sanitize_text_field( $sanitized_post_data['name'] ) );
		}

		if ( array_key_exists( 'playlist_videos', $sanitized_post_data ) && ! empty( $sanitized_post_data['playlist_videos'] ) ) {
			$update_data['video_ids'] = BC_Utility::sanitize_payload_item( $sanitized_post_data['playlist_videos'] );
		}

		$bc_accounts->set_current_account( $sanitized_post_data['account'] );

		$request = $this->cms_api->playlist_update( $playlist_id, $update_data );

		$bc_accounts->restore_default_account();

		if ( is_wp_error( $request ) || $request === false ) {
			return false;
		}

		if ( is_array( $request ) && isset( $request['id'] ) ) {
			if ( true === $this->add_or_update_wp_playlist( $request ) ) {
				return true;
			}

			$error_message = esc_html__( 'The Playlist failed to sync with WordPress', 'brightcove' );
			BC_Logging::log( sprintf( 'WORDPRESS PLAYLIST SYNC: %s', $error_message ) );

			return new WP_Error( 'playlist-wp-sync-error', $error_message );
		}

		return true;
	}

	/**
	 * Sync playlists with Brightcove
	 *
	 * Retrieve all playlists and create/update when necessary.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $retry whether this is a 2nd attempt or not
	 *
	 * @return bool True on success or false
	 */
	public function sync_playlists( $retry = false ) {

		global $bc_accounts;

		$force_sync = false;

		if ( defined( 'BRIGHTCOVE_FORCE_SYNC' ) && BRIGHTCOVE_FORCE_SYNC ) {
			$force_sync = true;
		}
		if ( ! $force_sync && get_transient( 'brightcove_sync_playlists' ) ) {
			return false;
		}

		$accounts           = $bc_accounts->get_sanitized_all_accounts();
		$completed_accounts = array();

		foreach ( $accounts as $account => $account_data ) {

			// We may have multiple accounts for an account_id, prevent syncing that account more than once.
			if ( ! in_array( $account_data['account_id'], $completed_accounts ) ) {

				$completed_accounts[] = $account_data['account_id'];

				$bc_accounts->set_current_account( $account );

				$playlists = $this->cms_api->playlist_list();

				if ( ! is_array( $playlists ) ) {

					if ( ! $retry ) {

						return $this->sync_playlists( true );

					} else {

						return false;
						// Something happened we retried, we failed

					}

				}

				$playlists = $this->sort_api_response( $playlists );

				if ( $force_sync || BC_Utility::hash_changed( 'playlists', $playlists, $this->cms_api->account_id ) ) {

					$playlist_ids_to_keep = array(); // for deleting outdated playlists
					$playlist_dates       = array();
					/* process all playlists */

					foreach ( $playlists as $playlist ) {

						$this->add_or_update_wp_playlist( $playlist );
						$playlist_ids_to_keep[]     = BC_Utility::sanitize_and_generate_meta_video_id( $playlist['id'] );
						$yyyy_mm                    = substr( preg_replace( '/[^0-9-]/', '', $playlist['created_at'] ), 0, 7 ); // Get YYYY-MM from created string
						$playlist_dates[ $yyyy_mm ] = $yyyy_mm;
					}

					ksort( $playlist_dates );

					$playlist_dates = array_keys( $playlist_dates ); // Only interested in the dates

					BC_Utility::set_video_playlist_dates( 'playlists', $playlist_dates, $bc_accounts->get_account_id() );

					BC_Utility::store_hash( 'playlists', $playlists, $this->cms_api->account_id );

				}

			}

			$bc_accounts->restore_default_account();

		}
		set_transient( 'brightcove_sync_playlists', true, 30 );

		return true;
	}

	/**
	 * Initial playlist sync
	 *
	 * Retrieve all playlists and create/update when necessary.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $is_cli whether the call is coming via WP_CLI
	 *
	 * @return bool True on success or false
	 */
	public function handle_initial_sync( $is_cli = false ) {

		if ( true === $is_cli ) {
			WP_CLI::line( esc_html__( 'Starting Playlist Sync', 'brightcove' ) );
		}

		global $bc_accounts;

		$playlists = $this->cms_api->playlist_list();

		if ( ! is_array( $playlists ) ) {
			return false;
		}

		if ( true === $is_cli ) {
			WP_CLI::line( esc_html__( sprintf( 'There are %d playlists to sync for this account. Please be patient.', sizeof( $playlists ) ), 'brightcove' ) );
		}

		$playlists = $this->sort_api_response( $playlists );

		$playlist_ids_to_keep = array(); // for deleting outdated playlists
		$playlist_dates       = array();
		/* process all playlists */

		foreach ( $playlists as $playlist ) {

			$this->add_or_update_wp_playlist( $playlist );
			$playlist_ids_to_keep[]     = BC_Utility::sanitize_and_generate_meta_video_id( $playlist['id'] );
			$yyyy_mm                    = substr( preg_replace( '/[^0-9-]/', '', $playlist['created_at'] ), 0, 7 ); // Get YYYY-MM from created string
			$playlist_dates[ $yyyy_mm ] = $yyyy_mm;

		}

		ksort( $playlist_dates );

		$playlist_dates = array_keys( $playlist_dates ); // Only interested in the dates

		BC_Utility::set_video_playlist_dates( 'playlists', $playlist_dates, $bc_accounts->get_account_id() );

		BC_Utility::store_hash( 'playlists', $playlists, $this->cms_api->account_id );

		if ( true === $is_cli ) {
			WP_CLI::line( esc_html__( 'Playlist Sync Complete', 'brightcove' ) );
		}

		return true;

	}

	/**
	 * In the event playlist object data is stale in WordPress, or a playlist has never been generated,
	 * create/update WP data store with Brightcove data.
	 *
	 * @param      $playlist
	 *
	 * @return bool|WP_Error
	 */
	public function add_or_update_wp_playlist( $playlist ) {

		$hash        = BC_Utility::get_hash_for_object( $playlist );
		$playlist_id = $playlist['id'];

		$stored_hash = $this->get_playlist_hash_by_id( $playlist_id );

		// No change to existing playlist
		if ( $hash === $stored_hash ) {
			return true;
		}

		$post_excerpt = ( ! is_null( $playlist['description'] ) ) ? $playlist['description'] : '';
		$post_content = $post_excerpt;
		$post_title   = ( ! is_null( $playlist['name'] ) ) ? $playlist['name'] : '';

		$post_date = new DateTime( $playlist['created_at'] );
		$post_date = $post_date->format( 'Y-m-d g:i:s' );

		$utc_timezone = new DateTimeZone( 'GMT' );
		$gmt          = new DateTime( $playlist['created_at'], $utc_timezone );
		$gmt          = $gmt->format( 'Y-m-d g:i:s' );

		$playlist_post_args = array(
			'post_type'     => 'brightcove-playlist',
			'post_title'    => $post_title,
			'post_name'     => $post_title . uniqid(),
			'post_content'  => $post_content,
			'post_excerpt'  => $post_excerpt,
			'post_date'     => $post_date,
			'post_date_gmt' => $gmt,
			'post_status'   => 'publish',
		);

		$existing_post = $this->get_playlist_by_id( $playlist_id );

		if ( $existing_post ) {

			$playlist_post_args['ID'] = $existing_post->ID;
			$post_id                  = wp_update_post( $playlist_post_args, true );

		} else {

			$post_id = wp_insert_post( $playlist_post_args, true );

		}

		if ( is_wp_error( $post_id ) ) {

			$error_message = $post_id->get_error_message();

			BC_Logging::log( sprintf( 'BC WORDPRESS ERROR: %s' ), $error_message );

			return new WP_Error( 'post-not-created', $error_message );

		}

		$this->playlist_ids[] = $post_id;

		update_post_meta( $post_id, '_brightcove_hash', $hash );
		update_post_meta( $post_id, '_brightcove_playlist_id', BC_Utility::sanitize_and_generate_meta_video_id( $playlist_id ) );
		update_post_meta( $post_id, '_brightcove_account_id', BC_Utility::sanitize_id( $playlist['account_id'] ) );
		update_post_meta( $post_id, '_brightcove_playlist_object', $playlist );

		if ( isset( $playlist['video_ids'] ) ) {

			$video_ids = BC_Utility::sanitize_payload_item( $playlist['video_ids'] );

			foreach ( $video_ids as $video_id ) {
				update_post_meta( $post_id, '_brightcove_video_id', $video_id );
			}

		}

		$meta      = array();
		$meta_keys = apply_filters( 'brightcove_meta_keys', array( 'favorite', 'limit', 'search' ) );

		foreach ( $meta_keys as $key ) {

			if ( ! empty( $playlist[ $key ] ) ) {
				$meta[ $key ] = $playlist[ $key ];
			}

		}

		update_post_meta( $post_id, '_brightcove_metadata', $meta );

		return true;

	}

	/**
	 * Returns playlist ids
	 *
	 * Returns a list of playlist ids from the last add_update operations.
	 *
	 * @since 1.0.0
	 *
	 * @return array Array of video ids
	 */
	public function get_playlist_id_list() {

		return $this->playlist_ids;

	}

	/**
	 * Resets playlist ID list
	 *
	 * Used to reset the playlist id list for more acurate tracking
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function reset_playlist_id_list() {

		$this->playlist_ids = array();

	}

	/**
	 * Accepts a playlist ID and checks to see if there is a record in WordPress. Returns the post object on success and false on failure.
	 *
	 * @param $playlist_id
	 *
	 * @return bool|WP_Post
	 */
	public function get_playlist_by_id( $playlist_id ) {

		$existing_playlists = new WP_Query(
			array(
				'meta_key'               => '_brightcove_playlist_id',
				'meta_value'             => BC_Utility::sanitize_and_generate_meta_video_id( $playlist_id ),
				'post_type'              => 'brightcove-playlist',
				'posts_per_page'         => 1,
				'update_post_term_cache' => false,

			)
		);

		if ( ! $existing_playlists->have_posts() ) {

			return false;

		} else {

			return end( $existing_playlists->posts );

		}

	}

	public function sort_api_response( $playlists ) {

		foreach ( $playlists as $key => $playlist ) {

			$id               = BC_Utility::sanitize_and_generate_meta_video_id( $playlist['id'] );
			$playlists[ $id ] = $playlist;
			unset( $playlists[ $key ] );

		}

		ksort( $playlists );

		return $playlists;

	}

	public function get_playlist_hash_by_id( $playlist_id ) {

		$playlist = $this->get_playlist_by_id( $playlist_id );

		if ( ! $playlist ) {

			return false;

		} else {

			return get_post_meta( $playlist->ID, '_brightcove_hash', true );

		}

	}

}
