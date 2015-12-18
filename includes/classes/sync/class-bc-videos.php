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
