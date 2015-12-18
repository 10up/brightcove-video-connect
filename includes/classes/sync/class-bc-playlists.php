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

	}

	/**
	 * Updates Metadata to the Brightcove API
	 *
	 * @param array $sanitized_post_data This should be sanitized POST data.
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

		if ( is_wp_error( $request ) || false === $request ) {
			return false;
		}

		if ( is_array( $request ) && isset( $request['id'] ) ) {
			return true;
		}

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
