<?php

/**
 * Interface to the Brightcove CMS API and DI API.
 *
 * Handles interaction to the Brightcove Content Management System (CMS) API
 * as well as uploading and replacing videos with the Dynamic Ingest API.
 *
 * @since   1.0.0
 *
 * @package Brightcove_Video_Connect
 */
class BC_CMS_API extends BC_API {

	/**
	 * Base URL of the CMS API.
	 *
	 * @since  1.0.0
	 */
	const CMS_BASE_URL = 'https://cms.api.brightcove.com/v1/accounts/';

	/**
	 * Base URL of the Dynamic Ingest API.
	 *
	 * @since  1.0.0
	 */
	const DI_BASE_URL = 'https://ingest.api.brightcove.com/v1/accounts/';

	/**
	 * Setup processing of CMS API
	 *
	 * Sets up class variables allowing for processing of Brightcove CMS API functionality.
	 *
	 * @since 1.0.0
	 *
	 * @param BC_Accounts $accounts the Brightcove accounts
	 *
	 * @return BC_CMS_API an instance of the BC CMS API object
	 */
	public function __construct() {

		parent::__construct();

	}

	/**
	 * Creates a playlist
	 *
	 * Creates a new empty playlist in the Brightcove Video Cloud.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name the name of the playlst
	 * @param string $type the type of playlist (see BC CMS API docs)
	 * @param array $args Array of other attributes for the playlist
	 *
	 * @return array|bool Array of playlist data or false if failure
	 */
	public function playlist_add( $name, $type = 'ACTIVATED_NEWEST_TO_OLDEST', $args = array() ) {

		$allowed_types = array(
			'EXPLICIT',
			'ACTIVATED_OLDEST_TO_NEWEST',
			'ACTIVATED_NEWEST_TO_OLDEST',
			'ALPHABETICAL',
			'PLAYS_TOTAL',
			'PLAYS_TRAILING_WEEK',
			'START_DATE_OLDEST_TO_NEWEST',
			'START_DATE_NEWEST_TO_OLDEST',
		);

		$type = strtoupper( sanitize_text_field( $type ) );

		if ( ! in_array( $type, $allowed_types ) ) {
			return false;
		}

		$data         = array();
		$data['name'] = utf8_uri_encode( sanitize_text_field( $name ) );
		$data['type'] = $type;

		foreach ( $args as $index => $value ) {

			if ( ! is_array( $value ) ) {
				$value = utf8_uri_encode( sanitize_text_field( $value ) );
			}

			$data[ $index ] = $value;

		}

		return $this->send_request( esc_url_raw( self::CMS_BASE_URL . $this->get_account_id() . '/playlists' ), 'JSON_POST', $data );

	}

	/**
	 * Retrieve a count of all playlists
	 *
	 * Retrieves a count of all playlists. Can be limited with queries.
	 *
	 * @since 1.0.0
	 *
	 * @param string $query Query terms to search for
	 *
	 * @return int|bool number of available playlists retrieved or false if error
	 */
	public function playlist_count( $query = '' ) {

		$args = array();

		if ( '' != sanitize_text_field( $query ) ) {
			$args['q'] = sanitize_text_field( $query );
		}

		$url = add_query_arg(
			$args,
			self::CMS_BASE_URL . $this->get_account_id() . '/counts/playlists'
		);

		$data = $this->send_request( esc_url_raw( $url ) );

		if ( isset( $data['count'] ) ) {
			return $data['count'];
		}

		return false;

	}

	/**
	 * Delete a given playlist from the Brightcove cloud
	 *
	 * Deletes a playlist, specified by the playlist ID, from the Brightcove cloud
	 *
	 * @since 1.0.0
	 *
	 * @param string $playlist_id the id of the playlist to delete
	 *
	 * @return bool true if successful or false
	 */
	public function playlist_delete( $playlist_id ) {

		$playlist_id = sanitize_title_with_dashes( $playlist_id );

		return $this->send_request( esc_url_raw( self::CMS_BASE_URL . $this->get_account_id() . '/playlists/' . $playlist_id ), 'DELETE' );

	}

	/**
	 * Retrieve a single playlist
	 *
	 * Retrieves a specified playlist from the CMS API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $playlist_id the id of the requested playlist
	 *
	 * @return array|bool array of the video information retrieved or false if error
	 */
	public function playlist_get( $playlist_id ) {

		$playlist_id = sanitize_title_with_dashes( $playlist_id );

		return $this->send_request( esc_url_raw( self::CMS_BASE_URL . $this->get_account_id() . '/playlists/' . $playlist_id ) );

	}

	/**
	 * Get a list of all playlists
	 *
	 * Retrieves a list of all playlists in the user's account
	 *
	 * @since 1.0.0
	 *
	 * @return array|bool|mixed array of all playlists of false if failure
	 */
	public function playlist_list() {

		$results = $this->send_request( esc_url_raw( self::CMS_BASE_URL . $this->get_account_id() . '/playlists' ) );

		if ( is_array( $results ) ) {

			foreach ( $results as $index => $result ) {

				// Note: the width and height parameters added here are currently unused.
				$results[ $index ]['width']  = apply_filters( 'bv_playlist_default_width', 0 );
				$results[ $index ]['height'] = apply_filters( 'bv_playlist_default_height', 0 );

			}
		}

		return $results;

	}

	/**
	 * Update a playlist in the Brightcove Video Cloud
	 *
	 * Updates a playlist with the provided id and optional other data in the video cloud.
	 *
	 * @since 1.0.0
	 *
	 * @param string $playlist_id the id of the playlist to update
	 * @param array $args optional array of other arguments to update in the playlist
	 *
	 * @return array|bool array of data about the updated playlist or false on failure.
	 */
	public function playlist_update( $playlist_id, $args = array() ) {

		$playlist_id = BC_Utility::sanitize_payload_item( $playlist_id );
		$data        = BC_Utility::sanitize_payload_args_recursive( $args );

		return $this->send_request( esc_url_raw( self::CMS_BASE_URL . $this->get_account_id() . '/playlists/' . $playlist_id ), 'PATCH', $data );
	}

	/**
	 * Create a video in the Brightcove Video Cloud
	 *
	 * Creates a video with the provided name and optional other data in the video cloud.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name the name of the video
	 * @param array $args optional array of other arguments used in video creation
	 *
	 * @return array|bool array of data about the new video or false on failure.
	 */
	public function video_add( $name, $args = array() ) {

		$data         = BC_Utility::sanitize_payload_args_recursive( $args );
		$data['name'] = utf8_uri_encode( sanitize_text_field( $name ) );

		return $this->send_request( esc_url_raw( self::CMS_BASE_URL . $this->get_account_id() . '/videos' ), 'POST', $data );

	}

	/**
	 * Get number of videos in account.
	 *
	 * Returns the number of videos available to the current account or false if failure.
	 *
	 * @since 1.0.0
	 *
	 * @return int|bool number of videos in the account or false if failure
	 */
	public function video_count( $filter = '' ) {

		$data = $this->send_request( esc_url_raw( self::CMS_BASE_URL . $this->get_account_id() . '/counts/videos' . $filter ) );

		if ( is_wp_error( $data ) ) {
			return $data; // WP_Error object
		}

		if ( isset( $data['count'] ) ) {
			return $data['count'];
		}

		return false;

	}

	/**
	 * Delete a video
	 *
	 * Deletes the specified video from the Brightcove Video Cloud
	 *
	 * @since 1.0.0
	 *
	 * @param string $video_id the id of the requested video
	 *
	 * @return array|bool array of the video information retrieved or false if error
	 */
	public function video_delete( $video_id ) {

		$video_id = sanitize_title_with_dashes( $video_id );

		return $this->send_request( esc_url_raw( self::CMS_BASE_URL . $this->get_account_id() . '/videos/' . $video_id ), 'DELETE' );

	}

	/**
	 * Retrieve a single video
	 *
	 * Retrieves a specified video from the CMS API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $video_id the id of the requested video
	 *
	 * @return array|bool array of the video information retrieved or false if error
	 */
	public function video_get( $video_id ) {

		$video_id = sanitize_title_with_dashes( $video_id );

		return $this->send_request( esc_url_raw( self::CMS_BASE_URL . $this->get_account_id() . '/videos/' . $video_id ) );
	}

	/**
	 * Retrieve a single video's images
	 *
	 * Retrieves a specified video's images from the CMS API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $video_id the id of the requested video
	 *
	 * @return array|bool array of the video's images retrieved or false if error
	 */
	public function video_get_images( $video_id ) {

		$video_id = sanitize_title_with_dashes( $video_id );

		return $this->send_request( esc_url_raw( self::CMS_BASE_URL . $this->get_account_id() . '/videos/' . $video_id . '/images' ) );

	}

	/**
	 * Retrieve a single video's references
	 *
	 * Retrieves an array of manual playlist IDs that the video ID is contained
	 * in. Note that this request does not look at smart playlists.
	 *
	 * @since 1.0.0
	 *
	 * @param string $video_id the id of the requested video
	 *
	 * @return array|bool array of the video's playlist references retrieved or false if error
	 */
	public function video_get_references( $video_id ) {

		$video_id = sanitize_title_with_dashes( $video_id );

		return $this->send_request( esc_url_raw( self::CMS_BASE_URL . $this->get_account_id() . '/videos/' . $video_id . '/references' ) );

	}

	/**
	 * Retrieve a single video's sources
	 *
	 * Retrieves a specified video's sources from the CMS API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $video_id the id of the requested video
	 *
	 * @return array|bool array of the video's sources retrieved or false if error
	 */
	public function video_get_sources( $video_id ) {

		$video_id = sanitize_title_with_dashes( $video_id );

		return $this->send_request( esc_url_raw( self::CMS_BASE_URL . $this->get_account_id() . '/videos/' . $video_id . '/sources' ) );

	}

	/**
	 * Retrieve a list of all videos
	 *
	 * Retrieves a list of all videos. Can be limited with arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param int $limit Number of videos to return - must be an integer between 1 and 100.
	 * @param int $offset Number of videos to skip (for paging results). Must be a positive integer.
	 * @param string $query Query terms to search for.
	 * @param string $sort A string that specifies the field to sort by. Start with - to sort descending.
	 * @param bool $playable Available at the /videos endpoint.
	 *
	 * @return array|bool array of available videos retrieved or false if error
	 */
	public function video_list( $limit = 20, $offset = 0, $query = '', $sort = '-created_at', $playable = true ) {

		/*
		 * Available fields for sort:
		 *
		 * name
		 * reference_id
		 * created_at
		 * published_at
		 * updated_at
		 * schedule_starts_at
		 * schedule_ends_at
		 * state
		 * plays_total
		 * plays_trailing_week
		 *
		 * Available Search Fields
		 *
		 * name	strings or quoted strings
		 * text	strings or quoted strings (name, description, long_description)
		 * tags	strings or quoted strings
		 * reference_id	string or quoted string
		 * state	ACTIVE, INACTIVE, DELETED, PENDING
		 * updated_at	date range
		 * created_at	date range
		 * schedule.starts_at	date range
		 * schedule.ends_at	date range
		 * published_at	date range
		 * complete	true or false
		 */

		$args = array();

		if ( 20 != absint( $limit ) ) {
			$args['limit'] = absint( $limit );
		}

		if ( 0 != absint( $offset ) ) {
			$args['offset'] = absint( $offset );
		}

		if ( 'updated_at' != sanitize_text_field( $sort ) ) {
			$args['sort'] = sanitize_text_field( $sort );
		}

		if ( false === $playable ) {
			$args['playable'] = false;
		}

		if ( '' != sanitize_text_field( $query ) ) {

			// If Post variables are being escaped, the encoded quote do not return the intended results from the API.
			$query = stripslashes( $query );

			$args['q'] = sanitize_text_field( $query );

		}

		$url  = add_query_arg(
			$args,
			self::CMS_BASE_URL . $this->get_account_id() . '/videos'
		);

		$results = $this->send_request( $url );

		if ( is_array( $results ) ) {

			foreach ( $results as $index => $result ) {

				// Note: the width and height parameters added here are currently unused.
				$results[ $index ]['width']  = apply_filters( 'bv_video_default_width', 0 );
				$results[ $index ]['height'] = apply_filters( 'bv_video_default_height', 0 );

			}
		}

		return $results;

	}

	/**
	 * Update a video in the Brightcove Video Cloud
	 *
	 * Updates a video with the provided id and optional other data in the video cloud.
	 *
	 * @since 1.0.0
	 *
	 * @param string $video_id the id of the video to update
	 * @param array $args optional array of other arguments used in video creation
	 *
	 * @return array|bool array of data about the updated video or false on failure.
	 */
	public function video_update( $video_id, $args = array() ) {

		$data     = array();
		$video_id = utf8_uri_encode( sanitize_text_field( $video_id ) );

		$data = BC_Utility::sanitize_payload_args_recursive( $args );

		return $this->send_request( esc_url_raw( self::CMS_BASE_URL . $this->get_account_id() . '/videos/' . $video_id ), 'PATCH', $data );

	}

	/**
	 * Upload a video for processing
	 *
	 * Sends the URL of a video's master copy to the Dynamic Ingest API for processing.
	 *
	 * @since 1.0.0
	 *
	 * @param string $video_id the ID of the video we're adding the master to
	 * @param string $video_url the url of the video stored locally
	 * @param string $profile the profile to use for processing
	 *
	 * @return string|bool the id of the ingest request or false on failure
	 */
	public function video_upload( $video_id, $video_url, $profile = 'videocloud-default-v1' ) {

		$data              = array( 'profile' => sanitize_text_field( $profile ) );
		$data['master']    = array( 'url' => esc_url_raw( $video_url ) );
		$data['callbacks'] = BC_Notification_API::callback_paths();

		return $this->send_request( esc_url_raw( self::DI_BASE_URL . $this->get_account_id() . '/videos/' . $video_id . '/ingest-requests' ), 'POST', $data );
	}

	/**
	 * Upload a poster (preroll image) for processing
	 *
	 * Sends a URL of the video's poster image to the Dynamic Ingest API for processing.
	 *
	 * @param string $video_id Video cloud ID
	 * @param string $poster_url URL for the video poster image
	 * @param int [$height]   Pixel height of the image
	 * @param int [$width]    Pixel width of the image
	 *
	 * @return string|bool The ingest request ID or false on failure
	 */
	public function poster_upload( $video_id, $poster_url, $height = 0, $width = 0 ) {
		// Sanitize values
		$height   = absint( $height );
		$width    = absint( $width );
		$video_id = urlencode( $video_id );

		// Build out the data
		$data              = array();
		$data['callbacks'] = BC_Notification_API::callback_paths();

		$data['poster'] = array(
			'url' => esc_url_raw( $poster_url ),
		);

		if ( 0 !== $height ) {
			$data['poster']['height'] = $height;
		}
		if ( 0 !== $width ) {
			$data['poster']['width'] = $width;
		}

		// Send the data
		return $this->send_request( esc_url_raw( self::DI_BASE_URL . $this->get_account_id() . '/videos/' . $video_id . '/ingest-requests' ), 'POST', $data );
	}

	/**
	 * Upload a thumbnail image for processing
	 *
	 * Sends a URL of the video's thumbnail image to the Dynamic Ingest API for processing.
	 *
	 * @param string $video_id Video cloud ID
	 * @param string $thumbnail_url URL for the thumbnail image
	 * @param int [$height]      Pixel height of the image
	 * @param int [$width]       Pixel width of the image
	 *
	 * @return string|bool The ingest request ID or false on failure
	 */
	public function thumbnail_upload( $video_id, $thumbnail_url, $height = 0, $width = 0 ) {
		// Sanitize values
		$height   = absint( $height );
		$width    = absint( $width );
		$video_id = urlencode( $video_id );

		// Build out the data
		$data              = array();
		$data['callbacks'] = BC_Notification_API::callback_paths();

		$data['thumbnail'] = array(
			'url' => esc_url_raw( $thumbnail_url ),
		);

		if ( 0 !== $height ) {
			$data['thumbnail']['height'] = $height;
		}
		if ( 0 !== $width ) {
			$data['thumbnail']['width'] = $width;
		}

		// Send the data
		return $this->send_request( esc_url_raw( self::DI_BASE_URL . $this->get_account_id() . '/videos/' . $video_id . '/ingest-requests' ), 'POST', $data );
	}

	/**
	 * Upload a single caption file for processing
	 *
	 * Sends a URL of the video's caption file to the Dynamic Ingest API for processing.
	 *
	 * @param string $video_id Video cloud ID
	 * @param string $caption_file_url URL for a WebVTT file
	 * @param string [$language]       ISO 639 2-letter language code for text tracks
	 * @param string [$label]          User-readable title
	 *
	 * @return string|bool The ingest request ID or false on failure
	 */
	public function caption_upload( $video_id, $caption_file_url, $language = 'en', $label = '' ) {
		// Text track
		$track = new BC_Text_Track( $caption_file_url, $language, 'captions', $label, false );

		// Send the data
		return $this->text_track_upload( $video_id, array( $track ) );
	}

	/**
	 * Upload a collection of text tracks for a specific video.
	 *
	 * Sends the URLs of various video text track files to the Dynamic Ingest API for processing.
	 *
	 * @param string $video_id
	 * @param BC_Text_Track[] $text_tracks
	 *
	 * @return string|bool The ingest request ID or false on failure
	 */
	public function text_track_upload( $video_id, $text_tracks ) {
		// Prepare data
		$data                = array();
		$data['callbacks']   = BC_Notification_API::callback_paths();
		$data['text_tracks'] = array();
		foreach ( $text_tracks as $track ) {
			$data['text_tracks'][] = $track->toArray();
		}

		// Send the data
		return $this->send_request( esc_url_raw( self::DI_BASE_URL . $this->get_account_id() . '/videos/' . $video_id . '/ingest-requests' ), 'POST', $data );
	}

	/**
	 * Get a list of custom video fields for the account.
	 *
	 * @return array|bool Array of all custom video fields of false if failure
	 */
	public function video_fields() {
		$results = $this->send_request( esc_url_raw( self::CMS_BASE_URL . $this->get_account_id() . '/video_fields' ) );

		return $results;
	}

	/**
	 * Subscribe to Brightcove API events
	 *
	 * @param string $endpoint
	 * @param array $events
	 *
	 * @return string|bool Subscription ID on success, false on failure
	 */
	public function create_subscription( $endpoint, $events = array() ) {
		$data             = array();
		$data['endpoint'] = $endpoint;

		// Sanitize events
		$events = array_map( 'sanitize_text_field', $events );

		$data['events'] = $events;

		$response = $this->send_request( esc_url_raw( self::DI_BASE_URL . $this->get_account_id() . '/subscriptions' ), 'POST', $data );

		if ( false === $response || ! isset( $response['id'] ) ) {
			return false;
		}

		return $response['id'];
	}

	/**
	 * Unsubscribe from Brightcove API events
	 *
	 * @param string $subscription_id
	 *
	 * @return mixed|bool
	 */
	public function remove_subscription( $subscription_id ) {
		$subscription_id = sanitize_text_field( $subscription_id );

		$this->send_request( esc_url_raw( self::DI_BASE_URL . $this->get_account_id() . '/subscriptions/' . $subscription_id ), 'DELETE' );
	}
}
