<?php

class BC_Video_Upload {

	protected $cms_api;

	public function __construct( $cms_api ) {

		$this->cms_api = $cms_api;
	}

	public function process_tags( $tags_string ) {

		if ( '' === $tags_string || ! is_string( $tags_string ) ) {
			return false;
		}

		return array_map( 'trim', explode( ',', $tags_string ) );
	}

	/**
	 *
	 * Take an uploaded file, and a supplied name and create a video ID and
	 * ingestion request for them.
	 *
	 * @param $files
	 * @param $account_hash
	 * @param $tags
	 * @param $name
	 *
	 * @return array|WP_Error
	 */
	public function process_uploaded_video( $files, $account_hash, $tags, $name ) {

		global $bc_accounts;

		$status = array(
			'upload' => 'fail',
			'ingest' => 'fail',
			'url'    => '',
		);

		$account = $bc_accounts->get_account_by_hash( $account_hash );

		if ( ! $account ) {
			return new WP_Error( 'invalid-account', esc_html__( 'Invalid account', 'brightcove' ) );
		}

		if ( isset( $files ) && isset( $files['file'] ) ) {

			// Check that file is supported by WP.
			if ( $this->check_allowed_file( $files['file'] ) === false ) {

				$error_message = esc_html__( 'Video type is not supported', 'brightcove' );
				BC_Logging::log( sprintf( 'VIDEO UPLOAD: %s', $error_message ) );

				return new WP_Error( 'video_upload_error', $error_message );

			}

			$uploaded = wp_handle_upload( $files['file'], array( 'test_form' => false ) );

			if ( isset( $uploaded['error'] ) ) {

				$error_message = esc_html__( $uploaded['error'], 'brightcove' );
				BC_Logging::log( sprintf( 'VIDEO UPLOAD ERROR: %s', $error_message ) );

				return new WP_Error( 'video_upload_error', $error_message );

			} else {

				$status['upload'] = 'success';
				$status['url']    = $uploaded['url'];

			}

			$tags_array = $this->process_tags( $tags );
			$data       = array();

			if ( false !== $tags_array ) {
				$data['tags'] = $tags_array;
			}

			$video_id_creation_result = $this->cms_api->video_add( $name, $data );

			if ( false === $video_id_creation_result ) {
				return new WP_Error( esc_html__( 'Unable to create a video on brightcove side', 'brightcove' ) );
			}

			if ( is_wp_error( $video_id_creation_result ) ) {
				return $video_id_creation_result;
			}

			if ( isset( $video_id_creation_result['created_at'] ) ) {

				$video_id           = BC_Utility::sanitize_and_generate_meta_video_id( $video_id_creation_result['id'] );
				$status['video_id'] = $video_id_creation_result['id'];
				BC_Utility::add_pending_upload( $video_id, $uploaded['file'] );

				$video_ingestion_request_result = $this->cms_api->video_upload( $video_id_creation_result['id'], $uploaded['url'] );

				if ( is_array( $video_ingestion_request_result ) && isset( $video_ingestion_request_result['id'] ) ) {
					$status['ingest']   = 'success';
					$status['ingestId'] = $video_ingestion_request_result['id'];
				}

				$status['videoDetails'] = $video_id_creation_result;

			}
		}

		return $status;

	}

	public static function update_video_meta() {

		if ( ! wp_verify_nonce( $_POST['nonce'], '_bc_ajax_search_nonce' ) ) {
			return;
		}
		if ( ! array_key_exists( 'update-metadata', $_POST ) ) {
			return;
		}

		$video_id = BC_Utility::sanitize_id( $_POST['video-id'] );
		$api      = new BC_CMS_API();
		$video    = $api->video_get( $video_id );

		$updated_data = array();

		foreach ( $_POST as $key => $postdata ) {

			echo esc_attr( $key );
			$updated_data = BC_Utility::sanitize_payload_item( $postdata );

		}

		if ( array_key_exists( 'video-related-url', $_POST ) ) {

			$video_related_url = esc_url_raw( $_POST['video-related-url'] );

			if ( strlen( $video_related_url ) ) {
				$updated_data['link'] = array_merge( $video['link'], array( 'url' => $video_related_url ) );
			}
		}

		if ( array_key_exists( 'video-related-text', $_POST ) ) {
			$updated_data['link'] = array_merge( $video['link'], array( 'text' => sanitize_text_field( $_POST['video-related-text'] ) ) );
		}

		if ( array_key_exists( 'video-tags', $_POST ) ) {

			$tags = explode( ',', $_POST['video-tags'] );
			$tags = array_filter( $tags, 'trim' );
			$tags = array_filter( $tags, 'sanitize_text_field' );

			$updated_data['tags'] = array_merge( $video['tags'], $tags );

		}

		$api->video_update( $video_id, $updated_data );

	}

	// Checks if file type is video and whether it's supported by WordPress.
	public function check_allowed_file( $file_data ) {

		$bc_allowed_types = BC_Utility::get_all_brightcove_mimetypes();
		$allowed_ext      = array_search( $file_data['type'], $bc_allowed_types );

		if ( false === $allowed_ext ) {
			return false;
		}

		// Check if type is allowed by WordPress.
		$ext = pathinfo( $file_data['name'], PATHINFO_EXTENSION );

		// If the extension matches the type.
		if ( $allowed_ext === $ext ) {
			return true;
		}

		return false;

	}
}
