<?php
/**
 * BC_Oauth_API class file.
 *
 * @package Brightcove_Video_Connect
 */

/**
 * Brightcove oAuth 2.0 API
 *
 * Uses the Brightcove oAuth implementation to secure an access token for API requests.
 */
class BC_Oauth_API {
	/**
	 * The Brightcove oAuth endpoint.
	 */
	const ENDPOINT_BASE = 'https://oauth.brightcove.com/v3';

	/**
	 * Transient name
	 *
	 * @var string
	 */
	protected $transient_name;

	/**
	 * Client id
	 *
	 * @var string
	 */
	protected $client_id;

	/**
	 * Client secret
	 *
	 * @var string
	 */
	protected $client_secret;

	/**
	 * http headers
	 *
	 * @var array
	 */
	protected $http_headers;

	/**
	 * Constructor method.
	 */
	public function __construct() {

		global $bc_accounts;

		$this->transient_name = BC_Utility::generate_transient_key( 'brightcove_oauth_access_token_', $bc_accounts->get_account_hash() );
		$this->set_account_credentials( $bc_accounts->get_client_id(), $bc_accounts->get_client_secret() );

	}

	/**
	 * Sets keys and headers to be used in oAuth calls
	 *
	 * @param string $client_id Client ID
	 * @param string $client_secret Client Secret
	 */
	public function set_account_credentials( $client_id, $client_secret ) {
		$this->client_id     = $client_id;
		$this->client_secret = $client_secret;
		$this->http_headers  = array(
			'headers' => array(
				'Content-type'  => 'application/json',
				'Authorization' => sprintf( 'Basic %s', base64_encode( $this->client_id . ':' . $this->client_secret ) ), // phpcs:ignore
			),
		);

	}

	/**
	 * Uses the Brightcove oAuth API to retrieve and store an access key for use with requests. The token is stored as a transient
	 * with an expiration time matching that which is returned from Brightcove. The call to the API is only performed if that transient
	 * is invalid or expired. Return a WP_Error object for use in WordPress in the case of failure.
	 *
	 * @since  1.0.0
	 *
	 * @see    BC_Utility::get_cache_item()
	 * @see    set_transient()
	 * @see    BC_Utility::delete_cache_item()
	 * @see    wp_remote_post()
	 *
	 * @param bool $force_new_token whether or not to obtain a new OAuth token
	 * @param bool $retry           true to retry on failure or false
	 *
	 * @return string|WP_Error
	 */
	public function request_access_token( $force_new_token = false, $retry = true ) {

		$transient_name = $this->transient_name;

		$token = $force_new_token ? false : BC_Utility::get_cache_item( $transient_name );

		if ( ! $token ) {

			$endpoint = esc_url_raw( self::ENDPOINT_BASE . '/access_token?grant_type=client_credentials' );

			$request = wp_remote_post( $endpoint, $this->http_headers );

			if ( 400 === wp_remote_retrieve_response_code( $request ) ) {

				// Just in case
				BC_Utility::delete_cache_item( $transient_name );

				// translators: %1$s: client_id, %2$s: client_secret
				$oauth_error = new WP_Error( 'oauth_access_token_failure', sprintf( __( 'There is a problem with your Brightcove %1$s or %2$s', 'brightcove' ), '<code>client_id</code>', '<code>client_secret</code>' ) );

				BC_Logging::log( sprintf( 'BC OAUTH ERROR: %s', $oauth_error->get_error_message() ) );

				return $oauth_error;

			}

			$body = wp_remote_retrieve_body( $request );
			$data = json_decode( $body );

			if ( isset( $data->access_token ) ) {

				$token = $data->access_token;
				BC_Utility::set_cache_item( $transient_name, 'oauth', $token, $data->expires_in );

			} else {

				if ( ! $retry ) {
					return new WP_Error( 'oauth_access_token_response_failure', sprintf( esc_html__( 'oAuth API did not return us an access token', 'brightcove' ) ) );
				}

				return $this->request_access_token( $force_new_token, false );

			}
		}

		return $token;

	}

	/**
	 * Backwards compat method for request_access_token()
	 *
	 * @param false $force_new_token whether or not to obtain a new OAuth token
	 * @param bool $retry            true to retry on failure or false
     *
	 * @return false|string|WP_Error
	 */
	public function _request_access_token( $force_new_token = false, $retry = true ) {
		_doing_it_wrong( __METHOD__, 'This function was moved to request_access_token()', 'Brightcove Video Connect 2.8.0' );

		return $this->request_access_token( $force_new_token, $retry );
	}

	/**
	 * Checks if the account credentials are valid.
	 *
	 * @return bool true if valid, false if not
	 */
	public function is_valid_account_credentials() {

		$token = $this->request_access_token();

		if ( is_wp_error( $token ) || false === $token ) {
			return false;
		}

		return true;

	}

}
