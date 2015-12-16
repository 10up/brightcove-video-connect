<?php

class BC_Videos {

	protected $cms_api;
	protected $tags;

	public function __construct() {
		$this->cms_api = new BC_CMS_API();
		$this->tags = new BC_Tags();

		/**
		 * With Force Sync option, we allow the syncing to happen as part of the
		 * page load, otherwise we just let the uploads, and video edit notifications
		 * to trigger sync actions
		 */

		if ( defined( 'BRIGHTCOVE_FORCE_SYNC' ) && BRIGHTCOVE_FORCE_SYNC ) {
			add_action( 'admin_init', array( $this, 'sync_videos' ) );
		}
	}

	/**
	 * Updates Metadata to the Brightcove API
	 *
	 * @param array $sanitized_post_data . This should be sanitized POST data
	 *
	 * @return bool|WP_Error
	 */
	public function update_bc_video( $sanitized_post_data ) {
		global $bc_accounts;
		$video_id       = BC_Utility::sanitize_id( $sanitized_post_data['video_id'] );

		if ( array_key_exists( 'name', $sanitized_post_data ) && '' !== $sanitized_post_data['name'] ) {
			$update_data['name'] = utf8_uri_encode( sanitize_text_field( $sanitized_post_data['name'] ) );
		}

		if ( array_key_exists( 'description', $sanitized_post_data ) && !empty( $sanitized_post_data['description'] ) ) {
			$update_data['description'] = BC_Utility::sanitize_payload_item( $sanitized_post_data['description'] );
		}

		if ( array_key_exists( 'long_description', $sanitized_post_data ) && !empty( $sanitized_post_data['long_description'] ) ) {
			$update_data['long_description'] = BC_Utility::sanitize_payload_item( $sanitized_post_data['long_description'] );
		}

		if ( array_key_exists( 'tags', $sanitized_post_data ) && !empty( $sanitized_post_data['tags'] ) ) {
			// Convert tags string to an array.
			$update_data['tags'] = array_map( 'trim', explode( ',', $sanitized_post_data['tags'] ) );
		}

		$bc_accounts->set_current_account( $sanitized_post_data['account'] );

		$request = $this->cms_api->video_update( $video_id, $update_data );

		$bc_accounts->restore_default_account();

		/**
		 * If we had any tags in the update, add them to the tags collection if we don't already track them.
		 */
		if ( array_key_exists( 'tags', $update_data ) && is_array( $update_data['tags'] ) && count($update_data['tags'] ) ) {
			$existing_tags = $this->tags->get_tags();
			$new_tags = array_diff( $update_data[ 'tags' ], $existing_tags);
			if (count($new_tags)) {
				$this->tags->add_tags( $new_tags );
			}
		}

		if ( is_wp_error( $request ) || $request === false ) {
			return false;
		}


		return true;
	}

	protected function have_enough_time( $start_time, $buffer_time = 15, $max_time = false ) {
		if ( !$max_time ) {
			$max_time = (int) ini_get( 'max_execution_time' );
			$max_time = $max_time > 0 ? $max_time : 30; // If we can't get max_time, we assume it's 30s
		}

		return ( ( time() + $buffer_time - $start_time ) <= $max_time );
	}

	public function handle_initial_sync( $account_id, $start_time = false ) {
		global $bc_accounts;
		if ( $bc_accounts->is_initial_sync_complete( $account_id ) ) {
			return true;
		}

		$max_time = (int) ini_get( 'max_execution_time' );

		if ( !$start_time ) {
			$start_time = time();
		} else {
			$buffer_time_in_seconds = apply_filters( 'brightcove_buffer_time_in_seconds', 15 );
			// We give around a 15s buffer for inserting one page of records
			if ( !$this->have_enough_time( $start_time, $buffer_time_in_seconds, $max_time ) ) {
				return;
			}
		}

		$transient_key_synching = '_brightcove_currently_synching';
		$currently_syncing      = get_transient( $transient_key_synching );

		if ( $currently_syncing ) {
			return false;
		}

		// Prevent other syncs from occurring
		set_transient( $transient_key_synching, $account_id, $max_time );

		$transient_key_number_of_videos = '_brightcove_video_count_' . $account_id;
		$number_of_videos               = get_transient( $transient_key_number_of_videos );

		if ( false === $number_of_videos ) {
			$number_of_videos = $this->cms_api->video_count();
		}

		$transient_key_current_offset = '_brightcove_sync_offset_' . $account_id;

		$current_offset = get_transient( $transient_key_current_offset );

		if ( false === $current_offset ) {
			$current_offset = 0;
		}

		$videos_per_page = apply_filters( 'brightcove_videos_per_page', 100 ); // Brightcove as of April 7th 2015 can only fetch 100 videos at a time
		$tags = $this->tags->get_tags();
		$dates = BC_Utility::get_video_playlist_dates_for_display( 'videos' );
		$tags_count = count($tags);

		for ( $offset = $current_offset; $offset < $number_of_videos; $offset += $videos_per_page ) {
			$offset             = min( $offset, $number_of_videos );
			$current_video_list = $this->cms_api->video_list( $videos_per_page, $offset );

			foreach ( $current_video_list as $video ) {
				$tags = array_merge( $tags, $video[ 'tags' ] );
				$yyyy_mm = substr( preg_replace( '/[^0-9-]/', '', $video['created_at'] ), 0, 7 ); // Get YYYY-MM from created string
				$video_dates[$yyyy_mm] = $yyyy_mm;
			}

			$tags = array_unique( $tags );

			if ( count($tags) > $tags_count ) {
				$this->tags->add_tags( $tags );
			}

			ksort( $video_dates );

			$video_dates = array_keys( $video_dates ); // Only interested in the dates

			BC_Utility::set_video_playlist_dates( 'videos', $video_dates, $bc_accounts->get_account_id() );

			set_transient( $transient_key_current_offset, $offset += $videos_per_page, $max_time );

			delete_transient( $transient_key_synching );

			if ( !$this->have_enough_time( $start_time ) ) {
				global $bc_accounts;
				$bc_accounts->trigger_sync_via_callback();

				return;
				// trigger sync event;
			} else {
				return $this->handle_initial_sync( $account_id, $start_time );
			}

		}

		$bc_accounts->set_initial_sync_status( $account_id, true );
	}


	/**
	 * Sync videos with Brightcove
	 *
	 * Retrieve all videos and create/update when necessary.
	 *
	 * @since 1.0.0
	 *
	 * @todo  Enable queuing to avoid large syncs which may tax resources
	 *
	 * @param bool $retry whether this is a 2nd attempt or not
	 *
	 * @return bool True on success or false
	 */
	public function sync_videos( $retry = false ) {
		global $bc_accounts;
		$force_sync = false;

		if ( defined( 'BRIGHTCOVE_FORCE_SYNC' ) && BRIGHTCOVE_FORCE_SYNC ) {
			$force_sync = true;
		}

		if ( !$force_sync && get_transient( 'brightcove_sync_videos' ) ) {
			return false;
		}

		$accounts           = $bc_accounts->get_sanitized_all_accounts();
		$completed_accounts = array();

		if ( empty( $accounts ) ) {
			return false;
		}

		foreach ( $accounts as $account => $account_data ) {

			$account_id = $account_data['account_id'];

			// We may have multiple accounts for an account_id, prevent syncing that account more than once.
			if ( !in_array( $account_id, $completed_accounts ) ) {

				// Have we sync'd this account yet?
				$sync_complete = $bc_accounts->is_initial_sync_complete( $account_id );

				if ( !$sync_complete ) {
					$this->handle_initial_sync( $account_id );
				} else {

					$sync_type = $bc_accounts->get_sync_type( $account_id );

					$hours_sync = is_numeric( $sync_type ) ? $sync_type : 24;

					$completed_accounts[] = $account_id;

					$bc_accounts->set_current_account( $account );

					$videos_per_page = apply_filters( 'brightcove_videos_per_page', 100 ); // Brightcove as of April 7th 2015 can only fetch 100 videos at a time

					$hours_sync = apply_filters( 'brightcove_hours_to_sync', $hours_sync );

					if ( 'full' === $sync_type ) {
						$video_count = $this->cms_api->video_count();
					} else {
						$video_count = $this->cms_api->video_count( "?q=updated_at:-{$hours_sync}hours.." );

					}

					if ( is_wp_error( $video_count ) ) {
						return false;
					}

					$videos = array();

					if ( 1 <= $video_count ) {

						for ( $offset = 0; $offset < $video_count; $offset += $videos_per_page ) {

							$offset = min( $offset, $video_count );
							if ( 'full' === $sync_type ) {
								$list = $this->cms_api->video_list( $videos_per_page, $offset, "", "-updated_at" );
							} else {
								$list = $this->cms_api->video_list( $videos_per_page, $offset, "updated_at:-{$hours_sync}hours.." );
							}

							if ( is_array( $list ) ) {

								$videos = array_merge( $videos, $list );

							} else {

								BC_Logging::log(
									sprintf(
										'Could not retrieve videos for account %s, offset %d. Sync may be incomplete',
										$account_id,
										$offset
									)
								);

							}

						}

						if ( !is_array( $videos ) ) {
							if ( !$retry ) {
								return $this->sync_videos( true );
							} else {
								return false; // Something happened we retried, we failed.
							}
						}

					}

					$videos = $this->sort_api_response( $videos );

					if ( $force_sync || BC_Utility::hash_changed( 'videos', $videos, $bc_accounts->get_account_id() ) ) {

						if ( 'full' === $sync_type ) {
							$video_ids_to_keep = array(); // for deleting outdated playlists
						}
						$video_dates = array();

						/* process all videos */
						foreach ( $videos as $video ) {

							$this->add_or_update_wp_video( $video );
							if ( 'full' === $sync_type ) {
								$video_ids_to_keep[] = BC_Utility::sanitize_and_generate_meta_video_id( $video['id'] );
							}
							$yyyy_mm               = substr( preg_replace( '/[^0-9-]/', '', $video['created_at'] ), 0, 7 ); // Get YYYY-MM from created string
							$video_dates[$yyyy_mm] = $yyyy_mm;

						}

						ksort( $video_dates );

						$video_dates = array_keys( $video_dates ); // Only interested in the dates

						BC_Utility::set_video_playlist_dates( 'videos', $video_dates, $bc_accounts->get_account_id() );

						BC_Utility::store_hash( 'videos', $videos, $bc_accounts->get_account_id() );

					}

				}

				$bc_accounts->restore_default_account();

			}
		}

		set_transient( 'brightcove_sync_videos', true, 30 );

		return true;

	}

	/**
	 * In the event video object data is stale in WordPress, or a video has never been generated,
	 * create/update WP data store with Brightcove data.
	 *
	 * @param      $video
	 * @boolean       $add_only true denotes that we know the object is not in our libary and we are adding it first time to the library. This is to improve the initial sync.
	 *
	 * @return bool|WP_Error
	 */
	public function add_or_update_wp_video( $video, $add_only = false ) {

		$hash     = BC_Utility::get_hash_for_object( $video );
		$video_id = $video['id'];

		if ( !$add_only ) {
			$stored_hash = $this->get_video_hash_by_id( $video_id );
			// No change to existing playlist
			if ( $hash === $stored_hash ) {
				return true;
			}
		}

		$post_excerpt = ( !is_null( $video['description'] ) ) ? $video['description'] : '';
		$post_content = ( !is_null( $video['long_description'] ) ) ? $video['long_description'] : $post_excerpt;
		$post_title   = ( !is_null( $video['name'] ) ) ? $video['name'] : '';

		$post_date = new DateTime( $video['created_at'] );
		$post_date = $post_date->format( 'Y-m-d g:i:s' );

		$utc_timezone = new DateTimeZone( 'GMT' );
		$gmt          = new DateTime( $video['created_at'], $utc_timezone );
		$gmt          = $gmt->format( 'Y-m-d g:i:s' );

		$video_post_args = array(
			'post_type'     => 'brightcove-video',
			'post_title'    => $post_title,
			'post_content'  => $post_content,
			'post_excerpt'  => $post_excerpt,
			'post_date'     => $post_date,
			'post_date_gmt' => $gmt,
			'post_status'   => 'publish',
		);

		if ( !$add_only ) {
			$existing_post = $this->get_video_by_id( $video_id );

			if ( $existing_post ) {

				$video_post_args['ID'] = $existing_post->ID;
				$post_id               = wp_update_post( $video_post_args );

			} else {

				$post_id = wp_insert_post( $video_post_args );

			}
		} else {
			$post_id = wp_insert_post( $video_post_args );
		}

		if ( !$post_id ) {

			$error_message = esc_html__('The video has not been created in WordPress', 'brightcove' );
			BC_Logging::log( sprintf( 'BC WORDPRESS ERROR: %s' ), $error_message );

			return new WP_Error( 'post-not-created', $error_message );

		}

		BC_Logging::log( sprintf( esc_html__( 'BC WORDPRESS: Video with ID #%d has been created', 'brightcove' ), $post_id ) );

		if ( !empty( $video['tags'] ) ) {
			wp_set_post_terms( $post_id, $video['tags'], 'brightcove_tags', false );
		}

		update_post_meta( $post_id, '_brightcove_hash', $hash );
		update_post_meta( $post_id, '_brightcove_video_id', BC_Utility::sanitize_and_generate_meta_video_id( $video['id'] ) );
		update_post_meta( $post_id, '_brightcove_transcoded', $video['complete'] );
		update_post_meta( $post_id, '_brightcove_account_id', $video['account_id'] );
		update_post_meta( $post_id, '_brightcove_video_object', $video );

		$meta      = array();
		$meta_keys = apply_filters( 'brightcove_meta_keys', array(
			'images',
			'state',
			'cue_points',
			'custom_fields',
			'duration',
			'economics',
		) );

		foreach ( $meta_keys as $key ) {

			if ( !empty( $video[$key] ) ) {
				$meta[$key] = $video[$key];
			}

		}

		update_post_meta( $post_id, '_brightcove_metadata', $meta );

		return true;

	}

	/**
	 * Accepts a video ID and checks to see if there is a record in WordPress. Returns the post object on success and false on failure.
	 *
	 * @param $video_id
	 *
	 * @return bool|WP_Post
	 */
	public function get_video_by_id( $video_id ) {

		$video_id = BC_Utility::sanitize_and_generate_meta_video_id( $video_id );

		$existing_video = new WP_Query(
			array(
				'meta_key'               => '_brightcove_video_id',
				'meta_value'             => $video_id,
				'post_type'              => 'brightcove-video',
				'posts_per_page'         => 1,
				'update_post_term_cache' => false,
			)
		);

		if ( !$existing_video->have_posts() ) {
			return false;
		}

		return end( $existing_video->posts );

	}

	public function sort_api_response( $videos ) {

		foreach ( $videos as $key => $video ) {

			$id          = BC_Utility::sanitize_and_generate_meta_video_id( $video['id'] );
			$videos[$id] = $video;
			unset( $videos[$key] );

		}

		ksort( $videos );

		return $videos;

	}

	public function get_video_hash_by_id( $video_id ) {

		$video = $this->get_video_by_id( $video_id );

		if ( !$video ) {

			return false;

		} else {

			return get_post_meta( $video->ID, '_brightcove_hash', true );

		}

	}

}
