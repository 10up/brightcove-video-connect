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
}
