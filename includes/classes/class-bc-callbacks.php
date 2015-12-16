<?php

class BC_Callbacks {
	public function __construct() {
		add_action( 'wp_ajax_bc_ingest', array( $this, 'ingest_callback' ) );
		add_action( 'wp_ajax_nopriv_bc_ingest', array( $this, 'ingest_callback' ) );
		add_action( 'wp_ajax_nopriv_bc_notifications', array( $this, 'video_notification' ) );
		/* Can only be self invoked from admin */
		add_action( 'wp_ajax_bc_initial_sync', array( $this, 'initial_sync' ) );
	}

	private function set_time_limit( $time = 300) {
		set_time_limit( $time );
	}

	public function initial_sync() {
		$this->set_time_limit();
		$start_time = time();
		global $bc_accounts;
		$sync_status = $bc_accounts->get_initial_sync_status();
		if ( false !== $sync_status ) {
			$videos = new BC_Videos();
			$videos->handle_initial_sync( $sync_status[0], $start_time );
		} else {
			wp_send_json_success('no vids left');
		}
		wp_send_json_error(time() - $start_time);
	}

	/**
	 * Function for processing a callback notification from Brightcove
	 *
     * Valid callback URI: /wp-admin/admin-post.php?bc_auth=4455f75b
     * Valid callback JSON:
     * {"timestamp":1427307045995,"account_id":"4089003419001","event":"video-change","video":"4133902975001","version":0}
     **/
	public function video_notification() {
		if ( ! isset( $_GET[ 'bc_auth'] ) ) {
			return;
		}

		$auth = $_GET['bc_auth'];

		$json = file_get_contents('php://input');
		$decoded = json_decode($json, true);

		if ( ! is_array( $decoded ) ) {
			return;
		}

		if ( ! isset( $decoded[ 'account_id' ] ) || ! isset( $decoded[ 'video' ] ) ) {
			return;
		}

		$account_id = BC_Utility::sanitize_id( $decoded[ 'account_id' ] );
		$valid_auth = BC_Utility::get_auth_key_for_id( $account_id );

		if ( $valid_auth !== $auth ) {
			// Someone was spoofing callbacks?
			return;
		}

        $video_id = BC_Utility::sanitize_id( $decoded[ 'video' ] );

		if ( ! $video_id ) {
			wp_send_json_error( 'missing video id' ); // Some sort of error occurred with the callback and we have no video_id.
		}

		global $bc_accounts;

		if ( ! $bc_accounts->set_current_account_by_id( $account_id ) ) {
			wp_send_json_error( 'bad account id' ); // Bad account id in callback
		}

		$cms_api = new BC_CMS_API();

		$video_details = $cms_api->video_get( $video_id );

		if ( false === $video_details ) {
			wp_send_json_error( 'video does not exist' );
		}

		$videos = new BC_Videos();

		$video_update =  $videos->add_or_update_wp_video( $video_details );

		$bc_accounts->restore_default_account();

		$this->trigger_background_fetch();

		if ( $video_update ) {
			wp_send_json_success( 'video successfully updated' );
		} else {
			wp_send_json_error( 'unable to update video' );

		}

	}

	/**
	 * We receive a post with JSON (nothing in $_POST)
	 * $_GET['id'] must contain the video ID
	 * $_GET['auth'] must contain the anti spoof hash.
	 */
    public function ingest_callback() {
        $json = file_get_contents('php://input');
        $decoded = json_decode($json, true);

		if (  !isset( $decoded[ 'entity' ] ) ||
			!isset( $_GET[ 'id' ] ) ||
			!isset( $_GET[ 'auth' ] ) ||
			"SUCCESS" !== $decoded[ 'status' ]
		) {
			exit;
		}

		$video_id = BC_Utility::sanitize_and_generate_meta_video_id( $_GET[ 'id' ] );
		$valid_auth = BC_Utility::get_auth_key_for_id( $video_id );

		if ( BC_Utility::sanitize_and_generate_meta_video_id( $decoded[ 'entity' ] ) !== $video_id ) {
			// We get lots of callbacks so we want to make sure that it's not
			// one of the transcodes that has completed, but rather this video.
			exit;
		}

		if ( $valid_auth !== $_GET[ 'auth' ] ) {
			// Someone was spoofing callbacks?
			exit;
		}

        BC_Utility::remove_pending_uploads( $video_id );
		// @todo: Set video uploaded state as complete.
		$this->trigger_background_fetch();
		exit;

	}

	public function trigger_background_fetch() {

		$this->set_time_limit();
		$videos = new BC_Videos();
		$videos->sync_videos();
		$playlists = new BC_Playlists();
		$playlists->sync_playlists();
	}
}
