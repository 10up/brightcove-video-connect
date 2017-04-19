<?php

/**
 * Interface to the Brightcove API
 *
 * Handles interaction to the Brightcove API structure including authentication
 * and the processing of calls for all associated APIs.
 *
 * @since   1.0.0
 *
 * @package Brightcove_Video_Connect
 */
abstract class BC_API {

	/**
	 * The account ID to make requests against
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var string $account_id the account id
	 */
	public $account_id;

	/**
	 * Array of errors encountered during API operations
	 *
	 * @since  1.0.0
	 * @access private
	 * @var array $errors array of errors
	 */
	private $errors;

	public function __construct() {

		$this->errors = array();

	}

	/**
	 * Get current account id
	 *
	 * Returns the current acount id.
	 *
	 * @since 1.0.0
	 *
	 * @return string the account id
	 */
	protected function get_account_id() {

		global $bc_accounts;

		return $bc_accounts->get_account_id();

	}

	/**
	 * Return all errors
	 *
	 * Returns the array of all errors encountered during API operations.
	 *
	 * @since 1.0.0
	 *
	 * @return array Errors encountered during API operations
	 */
	public function get_errors() {

		return $this->errors;
	}

	/**
	 * Returns the last error encountered during API operations.
	 *
	 * Returns the last error encountered during API operations or
	 * false if no errors have been encountered.
	 *
	 * @since 1.0.0
	 *
	 * @return mixed will return last error or false if none was found
	 */
	public function get_last_error() {

		if ( empty( $this->errors ) ) {
			return false;
		}

		return end( array_values( $this->errors ) );

	}

	private function cached_get( $url, $args ) {

		global $bc_accounts;

		/**
		 * Filter the length of time to cache proxied remote calls to the Brightcove API.
		 *
		 * @param int $cache_time_in_seconds The cache time to use, in seconds. Default 180.
		 */
		$cache_time_in_seconds = apply_filters( 'brightcove_proxy_cache_time_in_seconds', 180 );
		$account_id            = $bc_accounts->get_account_id();
		$max_key_length        = 45; // transients support a max key of 45
		$transient_key         = substr( '_brightcove_req_' . $account_id . BC_Utility::get_hash_for_object( $url ), 0, $max_key_length );
		$request               = BC_Utility::get_cache_item( $transient_key );
		if ( false === $request ) {
			if ( function_exists( 'vip_safe_wp_remote_get' ) ) {
				$request = vip_safe_wp_remote_get( $url, '', 3, 3, 20, $args );
			} else {
				$request = wp_remote_get( $url, $args );
			}
			$successful_response_codes = array( 200, 201, 202, 204 );

			if ( in_array( wp_remote_retrieve_response_code( $request ), $successful_response_codes ) ) {
				BC_Utility::set_cache_item( $transient_key, '', $request, $cache_time_in_seconds );
			}
		}

		return $request;
	}

	/**
	 * Sends API requests to remote server
	 *
	 * Sends the request to the remote server using the appropriate method and
	 * logs any errors in the event of failures.
	 *
	 * @param string  $url             the endpoint to connect to
	 * @param string  $method          the http method to use
	 * @param array   $data            array of further data to send to the server
	 * @param boolean $force_new_token whether or not to force obtaining a new oAuth token
	 *
	 *
	 * @return mixed the return data from the call of false if a failure occurred
	 */
	protected function send_request( $url, $method = 'GET', $data = array(), $force_new_token = false ) {

		$method = strtoupper( sanitize_text_field( $method ) );

		$allowed_methods = array(
			'DELETE',
			'GET',
			'PATCH',
			'POST',
			'PUT',
			'JSON_DELETE',
			'JSON_POST',
		); //only allow methods required by the brightcove APIs

		if ( ! in_array( $method, $allowed_methods ) ) {
			return false;
		}

		$url = esc_url_raw( $url );

		$transient_key = false;
		if ( $method === "GET" ) {
			$hash           = substr( BC_Utility::get_hash_for_object( array(
				                                                           "url"  => $url,
				                                                           "data" => $data,
			                                                           ) ), 0, 20 );
			$transient_key  = "_bc_request_$hash";
			$cached_request = BC_Utility::get_cache_item( $transient_key );

			if ( false !== $cached_request ) {
				return $cached_request;
			}
		}

		$auth_header = $this->get_authorization_header( $force_new_token );

		if ( is_wp_error( $auth_header ) ) {
			return $auth_header;
		}

		add_filter( 'http_request_timeout', array( $this, 'increase_http_timeout' ) );

		$headers = array( 'Authorization' => $auth_header );

		// All JSON_ methods are used to indicate that application/json is the content type
		if ( false !== strpos( $method, 'JSON' ) ) {
			$headers['Content-type'] = 'application/json';
			$method                  = str_replace( 'JSON_', '', $method );
		} else {
			$headers['Content-type'] = 'application/x-www-form-urlencoded';
		}

		$args = array(
			'headers' => $headers,
		);

		switch ( $method ) {

			case 'GET':

				$request = $this->cached_get( $url, $args );

				break;

			case 'POST':

				$args['body'] = wp_json_encode( $data );

				$request = wp_remote_post( $url, $args );
				break;

			default:

				$args['method'] = $method;
				$args['body']   = wp_json_encode( $data );

				if ( ! empty( $data ) ) {
					$args['body'] = json_encode( $data );
				}

				$request = wp_remote_request( $url, $args );
				break;

		}

		if ( 401 === wp_remote_retrieve_response_code( $request ) ) {
			if ( "Unauthorized" === $request['response']['message'] ) {
				// Token may have expired, so before we give up, let's retry
				// the request with a fresh OAuth token.
				if ( ! $force_new_token ) {
					return $this->send_request( $url, $method, $data, true );
				} else {
					$this->errors[] = array(
						'url'   => $url,
						'error' => new WP_Error( 'unauthorized-oauth', __( 'API says permission denied, check your client ID and client secret', 'brightcove' ) ),
					);

					return false;
				}
			}
		}

		//log errors for further processing or return the body
		if ( is_wp_error( $request ) ) {

			$this->errors[] = array(
				'url'   => $url,
				'error' => $request->get_error_message(),
			);
			BC_Logging::log( sprintf( 'WP_ERROR: %s', $request->get_error_message() ) );

			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $request ), true );

		$successful_response_codes = array( 200, 201, 202, 204 );

		if ( ! in_array( wp_remote_retrieve_response_code( $request ), $successful_response_codes ) ) {
			$message = esc_html__( 'An unspecified error has occurred.', 'brightcove' );
			if ( isset( $body[0] ) && isset( $body[0]['error_code'] ) ) {

				$message = $body[0]['error_code'];

			} elseif ( isset ( $body['message'] ) ) {

				$message = $body['message'];

			}

			$this->errors[] = array(
				'url'   => $url,
				'error' => new WP_Error( $request['response']['message'], $message ),
			);

			BC_Logging::log( sprintf( 'BC API ERROR: %s', $message ) );

			return false;
		}
		if ( '204' == wp_remote_retrieve_response_code( $request ) ) {

			return true;

		}

		if ( $transient_key && $body && ( ! defined( 'WP_DEBUG' ) || false === WP_DEBUG ) ) {
			// Store body for 60s to prevent hammering the BC APIs.
			BC_Utility::set_cache_item( $transient_key, 'api-request', $body, 60 );
		}

		return $body;

	}

	/**
	 * Increase the http timeout for API requests
	 */

	public function increase_http_timeout( $timeout ) {

		$timeout += 5;

		return $timeout;
	}

	/**
	 * Adds the required oAuth token header to make an API call to the Brightcove APIs
	 *
	 * @since  1.0.0
	 *
	 * @param boolean $force_new_token Whether or not we should obtain a fresh OAuth token for the request.
	 *
	 * @return string String containing oAuth token
	 */
	protected function get_authorization_header( $force_new_token = false ) {

		$oauth = new BC_Oauth_API();

		$token = $oauth->_request_access_token( $force_new_token );

		if ( is_wp_error( $token ) ) {
			return $token;
		}

		return 'Bearer ' . $token;

	}
}
