<?php

class BC_Admin_Media_API {

	private $cms_api;
	private $playlists;
	private $video_upload;
	private $videos;

	public function __construct() {

		global $bc_accounts;

		if ( ! $bc_accounts || ! $bc_accounts->get_account_details_for_user() ) {
			return new WP_Error( 'bc-account-no-perms-invalid', esc_html__( 'You do not have permission to use this Brightcove Account', 'brightcove' ) );
		}

		$this->cms_api      = new BC_CMS_API();
		$this->playlists    = new BC_Playlists( $this->cms_api );
		$this->videos       = new BC_Videos( $this->cms_api );
		$this->video_upload = new BC_Video_Upload( $this->cms_api );

		/* All of these actions are for authenticated users only for a reason */
		add_action( 'wp_ajax_bc_media_query', array( $this, 'brightcove_media_query' ) );
		add_action( 'wp_ajax_bc_media_update', array( $this, 'bc_ajax_update_video_or_playlist' ) );
		add_action( 'wp_ajax_bc_media_delete', array( $this, 'bc_ajax_delete_video_or_playlist' ) );
		add_action( 'wp_ajax_bc_media_upload', array( $this, 'brightcove_media_upload' ) ); // For uploading a file

	}

	private function  bc_helper_check_ajax() {

		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX || ! isset( $_POST['nonce'] ) ) {
			wp_send_json_error( esc_html__( 'Invalid Request', 'brightcove' ) );
		}

		$nonce = $_POST['nonce'];

		if ( ! wp_verify_nonce( $nonce, '_bc_ajax_search_nonce' ) ) {
			wp_send_json_error( esc_html__( 'Invalid Request', 'brightcove' ) );
		}
	}

	public function bc_ajax_update_video_or_playlist() {

		global $bc_accounts;

		// call this to check if we are allowed to continue
		$this->bc_helper_check_ajax();
		$type_msg = '';

		$updated_data = array();

		// check if playlist or video data was sent
		$fields = array( 'long_description', 'description', 'name', 'playlist_id', 'video_id', 'tags', 'width', 'height' );
		foreach ( $fields as $field ) {
			$updated_data[ $field ] = isset( $_POST[ $field ] ) ? sanitize_text_field( $_POST[ $field ] ) : '';
		}

		// Only Playlists have playlist_videos. We only do this if we're updating playlists
		if( isset( $_POST['playlist_videos'] ) ) {
			$playlist_videos = array();
			foreach ( $_POST[ 'playlist_videos' ] as $video_id ) {
				$playlist_videos[] = BC_Utility::sanitize_id( $video_id );
			}
			$updated_data[ 'playlist_videos' ] = $playlist_videos;
		}

		// Only Videos have a video_id so we only do this when updating a video
		if( isset( $_POST['video_id'] ) ) {
			$updated_data['video_id'] = BC_Utility::sanitize_id( $_POST['video_id'] );
		}

		$updated_data[ $field ] = isset( $_POST[ $field ] ) ? sanitize_text_field( $_POST[ $field ] ) : '';

		$updated_data['update-playlist-metadata'] = sanitize_text_field( $_POST['nonce'] );

		$status = false;

		if ( ! in_array( $_POST['type'], array( 'playlists', 'videos') ) ) {
			wp_send_json_error(__('Type is not specified', 'brightcove'));
		}

		$hash = $_POST[ 'account' ];
		if ( false === $bc_accounts->get_account_by_hash( $hash ) ) {
			wp_send_json_error(__('No such account exists', 'brightcove'));
		}
		$updated_data['account'] = $hash;
		if ( $_POST['type'] === 'playlists' ) {
			$updated_data['playlist-name'] = $updated_data['name'];
			$status                        = $this->playlists->update_bc_playlist( $updated_data );
			$type_msg                      = 'playlist';
		} elseif ( $_POST['type'] === 'videos' ) {
			$status   = $this->videos->update_bc_video( $updated_data );
			$type_msg = 'video';
		}

		BC_Utility::clear_cached_api_requests( 'all' );
		BC_Utility::clear_cached_api_requests( $bc_accounts->get_account_id() );
		$bc_accounts->restore_default_account();

		if ( $status === true ) {
			wp_send_json_success( esc_html__( 'Successfully updated ', 'brightcove' ) . esc_html( $type_msg ) );
		} elseif ( is_wp_error( $status ) ) {
			wp_send_json_error( esc_html__( 'Failed to sync with WordPress!', 'brightcove' ) );
		} else {
			wp_send_json_error( esc_html__( 'Failed to update ', 'brightcove' ) . esc_html( $type_msg ) . '!' );
		}
	}

	public function bc_ajax_delete_video_or_playlist() {

		// call this to check if we are allowed to continue
		$this->bc_helper_check_ajax();
		$type          = sanitize_key( $_POST[ 'type' ] );
		$type_msg      = '';
		$id            = BC_Utility::sanitize_id( $_POST[ 'id' ] );
		$existing_post = null;

		// get type brightcove-playlist or brightcove-video
		if ( ! in_array( $type, array( 'playlists', 'videos' ) ) ) {
			wp_send_json_error( esc_html__( 'Type is not specified!', 'brightcove' ) );
		}

		// try to get the existing post based on id
		if ( 'playlists' === $type ) {
			// get playlist
			$existing_post = $this->playlists->get_playlist_by_id( $id );
			$type_msg      = 'playlist';
		} elseif ( $type === 'videos' ) {

			// find existing video
			$existing_post = $this->videos->get_video_by_id( $id );
			$type_msg      = 'video';
		} else {
			wp_send_json_error( esc_html__( 'Wrong type is specified!', 'brightcove' ) );
		}

		if ( ! is_a( $existing_post, 'WP_Post' ) ) {
			wp_send_json_error( esc_html__( ucfirst( $type_msg ) . ' doesn\'t exist', 'brightcove' ) );
		}

		global $bc_accounts;

		$hash = sanitize_text_field( $_POST[ 'account' ] );
		if ( false === $bc_accounts->get_account_by_hash( $hash ) ) {
			wp_send_json_error(__('No such account exists', 'brightcove'));
		}

		$bc_accounts->set_current_account( $hash );

		// Remove from Brightcove
		$delete_status = false;
		$delete_message = '';
		if ( $type === 'videos' ) {
			$delete_status = $this->cms_api->video_delete( $id );
			if ( ! $delete_status ) {
				// We were not able to delete video from Brightcove, so force a resync to get back our media object
				$this->videos->sync_videos();
				$delete_message = esc_html__( 'Unable to remove video from Brightcove!', 'brightcove' );
			} else {
				$delete_message = esc_html__( 'Successfully deleted your video.', 'brightcove' );
			}
		} elseif ( $type === 'playlists' ) {
			$delete_status = $this->cms_api->playlist_delete( $id );
			if ( ! $delete_status ) {
				// We were not able to delete playlist from Brightcove, so force a resync to get back our media object
				$this->playlists->sync_playlists();
				$delete_message = esc_html__( 'Unable to remove playlist from Brightcove!', 'brightcove' );
			} else {
				$delete_message = esc_html__( 'Successfully deleted your playlist.', 'brightcove' );
			}
		}

		BC_Utility::clear_cached_api_requests( 'all' );
		BC_Utility::clear_cached_api_requests( $bc_accounts->get_account_id() );
		$bc_accounts->restore_default_account();

		if ( $delete_status ) {
			$deleted_obj = BC_Utility::delete_object( $existing_post->ID );
			if ( ! $deleted_obj || 0 === $deleted_obj ) {
				$delete_message = esc_html__( 'Unable to remove ' . $type_msg . ' from WordPress!', 'brightcove' );
				// We couldn't delete the post, lets try a sync and hopefully that clears it up for us.
				if ( 'videos' === $type ) {
					$this->videos->sync_videos();
				} else {
					$this->playlists->sync_playlists();
				}
			}
		}

		if ( $delete_status ) {
			wp_send_json_success( $delete_message );
		} else {
			wp_send_json_error( $delete_message );
		}
	}

	public function brightcove_media_upload() {

		global $bc_accounts;

		foreach ( array( 'nonce', 'tags', 'name', 'account' ) as $parameter ) {
			if ( ! isset( $_POST[ $parameter ] ) ) {
				wp_send_json_error();
			}
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], '_bc_ajax_search_nonce' ) ) {
			wp_send_json_error();
		}

		$account_hash = sanitize_text_field( $_POST['account'] );
		$account      = $bc_accounts->set_current_account( $account_hash );


		$tags = sanitize_text_field( $_POST['tags'] );
		$name = sanitize_text_field( $_POST['name'] );

		if ( ! is_array( $account ) || ! is_string( $name ) || '' === $name ) {
			$bc_accounts->restore_default_account();
			wp_send_json_error();
		}

		$ingestion_request_status = $this->video_upload->process_uploaded_video( $_FILES, $account_hash, $tags, $name );
		$bc_accounts->restore_default_account();

		if( is_wp_error( $ingestion_request_status ) ) {
			wp_send_json_error( $ingestion_request_status->get_error_message() );
		}

		wp_send_json_success( $ingestion_request_status );
		$uploaded_file = wp_handle_upload( $_FILES['file'], array( 'test_form' => false ) );
		if ( $uploaded_file ) {
			$uploaded_file['name']    = $name;
			$uploaded_file['tags']    = $tags;
			$uploaded_file['account'] = $account;

			$videos = new BC_Videos( $this->cms_api );
			$videos->sync_videos();

			$bc_accounts->restore_default_account();
			wp_send_json_success( $uploaded_file );
		} else {
			$bc_accounts->restore_default_account();
			wp_send_json_error( esc_html__( 'Unable to process file, does it have a valid extension?', 'brightcove' ) );
		}
	}

	public function fetch_all( $type, $posts_per_page = 100, $page = 1, $query_string = '', $sort_order = 'updated_at' ) {
		global $bc_accounts;
		$request_identifier = "{$type}-{$posts_per_page}-{$query_string}-{$sort_order}";
		$transient_key = substr( '_brightcove_req_all_' . BC_Utility::get_hash_for_object($request_identifier), 0, 45 );
		$results = get_transient( $transient_key );
		$results = is_array( $results ) ? $results : array();

		$initial_page = 1;
		$accounts = $bc_accounts->get_sanitized_all_accounts();
		$account_ids = array();
		foreach ($accounts as $account) {
			$account_ids[] = $account['account_id'];
		}

		$account_ids = array_unique( $account_ids );
		while ( count( $results ) <= ( $page * $posts_per_page ) ) {
			if (0 === count($account_ids)) {
				// No more vids to fetch
				break;
			}
			foreach( $account_ids as $key => $account_id ) {
				$bc_accounts->set_current_account_by_id( $account_id );
				$account_results = $this->cms_api->video_list($posts_per_page, $posts_per_page * ($initial_page - 1), $query_string, sanitize_text_field( $sort_order ));
				// Not enough account results returned, so we assume there are no more results to fetch.
				if (count($account_results) < $posts_per_page) {
					unset( $account_ids[ $key ] );
				}
				if (is_array( $account_results ) && count( $account_results) > 0 ) {
					$results = array_merge( $results, $account_results);
				} else {
					unset( $account_ids[ $key ] );
				}
			}
			$initial_page++;
		}

		set_transient( $transient_key, $results, 600); // High cache time due to high expense of fetching the data.
		$results = array_slice( $results, $posts_per_page * ( $page - 1 ), $posts_per_page);

		$bc_accounts->restore_default_account();
		return $results;
	}

	public function brightcove_media_query() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], '_bc_ajax_search_nonce' ) ) {
			wp_send_json_error();
		}

		$video_ids = false; // Used for the playlist edit.
		if ( isset( $_POST[ 'videoIds' ] ) ) {
			if ( 'none' === $_POST[ 'videoIds' ] ) {
				wp_send_json_success(array()); // Existing playlist with no video IDs.
			}
			$video_ids = array();
			// To handle the playlist of one video ID which is sent as a string instead of Array of 1 element.
			if ( is_string( $_POST[ 'videoIds' ] ) ) {
				$video_ids[] = BC_Utility::sanitize_id( $_POST[ 'videoIds'] );
			} else {
				foreach( $_POST[ 'videoIds' ] as $video_id ) {
					$video_ids[] = BC_Utility::sanitize_id( $video_id );
				}
			}
		}


		$account_id = ( isset( $_POST['account'] ) ) ? sanitize_text_field( $_POST['account'] ): 'all';
		if ( 'all' !== $account_id ) {
			$account_id = BC_Utility::sanitize_id( $_POST['account'] );
		}
		$query = ( isset( $_POST['search'] ) && '' !== $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : false;
		$tags = ( isset( $_POST['tags'] ) && '' !== $_POST['tags'] ) ? array_map( 'absint', (array) $_POST['tags'] ) : false;
		$tag_name = ( isset( $_POST['tagName'] ) && '' !== $_POST['tagName'] ) ? sanitize_text_field( $_POST['tagName'] ): false;
		$dates = ( isset( $_POST['dates'] ) && 'all' !== $_POST['dates'] ) ? BC_Utility::sanitize_date( $_POST['dates'] ): false;
		$posts_per_page = isset( $_POST['posts_per_page'] ) ? absint( $_POST['posts_per_page'] ) : apply_filters( 'brightcove_max_posts_per_page', 100 );
		$page = isset( $_POST['page_number'] ) ? absint( $_POST['page_number'] ) : 1;

		$type = isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : false;

		if ( ! $type || ! in_array( $type, array( 'videos', 'playlists' ) ) ) {
			wp_send_json_error( esc_html__( 'Invalid Search Type', 'brightcove' ) );
			exit; // Type can only be videos or playlists.
		}

		global $bc_accounts;

		if ('videos' === $type) {

			$query_terms = array();
			$query_string = '';

			if ($tag_name) {
				$query_terms[] = "tags:$tag_name";
				$query_string .= "tags:$tag_name";
			}

			if ($dates) {

				$query_terms[] = "updated_at:{$dates}-01..{$dates}-31";
				$query_string .= "updated_at:{$dates}-01..{$dates}-31";
			}

			if ($query) {
				array_unshift($query_terms, $query);
			}


			if ($video_ids) {
				// We send the video_ids sorted since we have them returned sorted by ID.
				// This way we get cache hits when playlist order, but not content have changed.
				$video_ids_sorted = $video_ids;
				sort($video_ids_sorted);
				$query_terms[] = "id:" . implode("+id:", $video_ids_sorted);
			}

			$query_string = implode("+", $query_terms);

			/**
			 * For playlists, we specify the order in the query string as follows:
			 * https://cms.api.brightcove.com/v1/accounts/<account_id>/videos?q=id:<video_id1>...+id:<video_idn>
			 *
			 * However it comes back to us sorted by video ID (smallest to largest, so afterwards we resort the dataset
			 * by playlist sort order.
			 */

			$bc_accounts->set_current_account_by_id( $account_id );
			$results = $this->cms_api->video_list($posts_per_page, $posts_per_page * ($page - 1), $query_string, 'updated_at');

			/**
			 * Since we use the video_list to fetch the videos for a playlist, it returns them to us
			 * ordered by video_id, so we use the order of video_ids (the playlist order) to sort them
			 * as per the playlist.
			 */

			if ( $video_ids ) {
				$ordered_results = array();
				foreach ( $video_ids as $video_id ) {
					foreach ( $results as $video_result ) {
						if ( $video_id === $video_result['id'] ) {
							$ordered_results[] = $video_result;
							break;
						}
					}
				}
				// $ordered_results is now in the same order as $video_ids
				$results = $ordered_results;
			}
		} else {
			$bc_accounts->set_current_account_by_id( $account_id );
			$results = $this->cms_api->playlist_list();
		}

		$bc_accounts->restore_default_account();
		wp_send_json_success( $results );
	}
}
