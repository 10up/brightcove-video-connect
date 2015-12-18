<?php

class BC_Videos {

	protected $cms_api;
	protected $tags;

	public function __construct() {

		$this->cms_api = new BC_CMS_API();
		$this->tags    = new BC_Tags();

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
		$video_id = BC_Utility::sanitize_id( $sanitized_post_data['video_id'] );

		if ( array_key_exists( 'name', $sanitized_post_data ) && '' !== $sanitized_post_data['name'] ) {
			$update_data['name'] = utf8_uri_encode( sanitize_text_field( $sanitized_post_data['name'] ) );
		}

		if ( array_key_exists( 'description', $sanitized_post_data ) && ! empty( $sanitized_post_data['description'] ) ) {
			$update_data['description'] = BC_Utility::sanitize_payload_item( $sanitized_post_data['description'] );
		}

		if ( array_key_exists( 'long_description', $sanitized_post_data ) && ! empty( $sanitized_post_data['long_description'] ) ) {
			$update_data['long_description'] = BC_Utility::sanitize_payload_item( $sanitized_post_data['long_description'] );
		}

		if ( array_key_exists( 'tags', $sanitized_post_data ) && ! empty( $sanitized_post_data['tags'] ) ) {
			// Convert tags string to an array.
			$update_data['tags'] = array_map( 'trim', explode( ',', $sanitized_post_data['tags'] ) );
		}

		$bc_accounts->set_current_account( $sanitized_post_data['account'] );

		$request = $this->cms_api->video_update( $video_id, $update_data );

		$bc_accounts->restore_default_account();

		/**
		 * If we had any tags in the update, add them to the tags collection if we don't already track them.
		 */
		if ( array_key_exists( 'tags', $update_data ) && is_array( $update_data['tags'] ) && count( $update_data['tags'] ) ) {
			$existing_tags = $this->tags->get_tags();
			$new_tags      = array_diff( $update_data['tags'], $existing_tags );
			if ( count( $new_tags ) ) {
				$this->tags->add_tags( $new_tags );
			}
		}

		if ( is_wp_error( $request ) || $request === false ) {
			return false;
		}

		return true;
	}

	protected function have_enough_time( $start_time, $buffer_time = 15, $max_time = false ) {

		if ( ! $max_time ) {
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

		if ( ! $start_time ) {
			$start_time = time();
		} else {
			$buffer_time_in_seconds = apply_filters( 'brightcove_buffer_time_in_seconds', 15 );
			// We give around a 15s buffer for inserting one page of records
			if ( ! $this->have_enough_time( $start_time, $buffer_time_in_seconds, $max_time ) ) {
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
		$tags            = $this->tags->get_tags();
		$dates           = BC_Utility::get_video_playlist_dates_for_display( 'videos' );
		$tags_count      = count( $tags );

		for ( $offset = $current_offset; $offset < $number_of_videos; $offset += $videos_per_page ) {
			$offset             = min( $offset, $number_of_videos );
			$current_video_list = $this->cms_api->video_list( $videos_per_page, $offset );

			foreach ( $current_video_list as $video ) {
				$tags                    = array_merge( $tags, $video['tags'] );
				$yyyy_mm                 = substr( preg_replace( '/[^0-9-]/', '', $video['created_at'] ), 0, 7 ); // Get YYYY-MM from created string
				$video_dates[ $yyyy_mm ] = $yyyy_mm;
			}

			$tags = array_unique( $tags );

			if ( count( $tags ) > $tags_count ) {
				$this->tags->add_tags( $tags );
			}

			ksort( $video_dates );

			$video_dates = array_keys( $video_dates ); // Only interested in the dates

			BC_Utility::set_video_playlist_dates( 'videos', $video_dates, $bc_accounts->get_account_id() );

			set_transient( $transient_key_current_offset, $offset += $videos_per_page, $max_time );

			delete_transient( $transient_key_synching );

			if ( ! $this->have_enough_time( $start_time ) ) {
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

		if ( ! $existing_video->have_posts() ) {
			return false;
		}

		return end( $existing_video->posts );

	}

	public function sort_api_response( $videos ) {

		foreach ( $videos as $key => $video ) {

			$id            = BC_Utility::sanitize_and_generate_meta_video_id( $video['id'] );
			$videos[ $id ] = $video;
			unset( $videos[ $key ] );

		}

		ksort( $videos );

		return $videos;

	}

}
