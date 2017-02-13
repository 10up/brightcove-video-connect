<?php

/**
 * Interface to the Brightcove Player Management and Embed APIs
 *
 * Handles interaction to the Brightcove Player Management and Embed APIs allowing
 * for the manipulation of parent and child players.
 *
 * @since   1.0.0
 *
 * @link    https://docs.brightcove.com/en/video-cloud/player-management/reference/versions/v1/index.html
 * @package Brightcove_Video_Connect
 */
class BC_Player_Management_API extends BC_API {

	/**
	 * Base URL of the Player Management API and Embed API.
	 *
	 * @since  1.0.0
	 */
	const BASE_URL = 'https://players.api.brightcove.com/v1/accounts/';

	/**
	 * Setup processing of Player Management API
	 *
	 * Sets up class variables allowing for processing of Brightcove Player Management API functionality.
	 *
	 * @since 1.0.0
	 *
	 * @return BC_Player_Management_API an instance of the BC Player Management API object
	 */
	public function __construct() {

		parent::__construct();

	}

	/**
	 * Publishes a Player and returns an embed
	 *
	 * @since 1.0.0
	 *
	 * @param $player_id The id of the requested player
	 *
	 * @return bool|mixed Returns a JSON string or false, in the case of failure
	 */
	public function player_publish( $player_id ) {

		global $bc_accounts;

		$player_id = sanitize_title_with_dashes( $player_id );

		$url = esc_url_raw( self::BASE_URL . $bc_accounts->get_account_id() . '/players/' . $player_id . '/publish' );
		return $this->send_request( $url, 'JSON_POST' );
	}

	/**
	 * Create a new player
	 *
	 * Sends available configuration options to the API for player creation.
	 *
	 * @since 1.0.0
	 *
	 * @param array $opts an array of available configuration options
	 *
	 * @return boolean|string Returns a JSON string or false, in the case of failure
	 */
	public function player_create( $opts ) {

		global $bc_accounts;

		$opts = (array) $opts;

		$url = esc_url_raw( self::BASE_URL . $bc_accounts->get_account_id() . '/players', 'JSON_POST', $opts );

		return $this->send_request( $url );
	}

	public function player_delete() {
	}

	/**
	 * Retrieve configuration of specific player.
	 *
	 * Retrieves the configuration for a specified player.
	 *
	 * @since 1.0.0
	 *
	 * @param string         $player_id the id of the requested player
	 * @param boolean|string $branch    Can be either master, preview or false. Default is false
	 *
	 * @return array|bool array of the player configuration retrieved or false if error
	 */
	public function player_get( $player_id, $branch = false ) {

		global $bc_accounts;

		$player_id = sanitize_title_with_dashes( $player_id );

		if ( in_array( $branch, array( 'master', 'preview' ) ) ) {
			$url = esc_url_raw( self::BASE_URL . $bc_accounts->get_account_id() . '/players/' . $player_id . '/configuration/' . $branch );
		} else {
			$url = esc_url_raw( self::BASE_URL . $bc_accounts->get_account_id() . '/players/' . $player_id . '/configuration' );
		}

		return $this->send_request( $url );

	}

	/**
	 * List available players
	 *
	 * Returns a list of available players in a given account as well as
	 * the total count of players available
	 *
	 * @since 1.0.0
	 *
	 * @param string $player_id ID of parent if looking for child players
	 *
	 * @return array|bool Array of available players or false if error
	 */
	public function player_list( $player_id = '' ) {

		global $bc_accounts;

		if ( '' != $player_id ) {
			$player_id = utf8_uri_encode( sanitize_text_field( $player_id ) ) . '/embeds';
		}

		$url = esc_url_raw( self::BASE_URL . $bc_accounts->get_account_id() . '/players/' . $player_id );

		return apply_filters( 'brightcove_player_list', $this->send_request( $url ) );

	}

    /**
     * List all players for all accounts available
     *
     * Returns a list of available players for each accounts, as well as
     * the total count of players available, grouped by account
     *
     * @since 1.2.3
     *
     * @return array|bool Array of available players or false if error
     */
    public function all_player_by_account() {

        global $bc_accounts;

        $all_accounts_id = $bc_accounts->get_all_accounts_id();
        $players = false;

        foreach ($all_accounts_id as $account_id) {
            $url = esc_url_raw( self::BASE_URL . $account_id . '/players/');
            $players[$account_id] = $this->send_request($url);
        }

        return apply_filters( 'brightcove_all_player_by_account', $players );
    }

	/**
	 * Update a player
	 *
	 * Only updates the $name and $description of a player. To update player config, use `player_config_update()` method
	 *
	 * @since 1.0.0
	 *
	 * @param string $player_id             Required. The hex representation of the player as presented by the API
	 * @param bool|string $name             Optional. The new name for the player. Default is false (no update)
	 * @param bool|string $description      Optional. The new description for the player. Default is false (no update)
	 *
	 * @return bool|mixed
	 */
	public function player_update( $player_id, $name = false, $description = false ) {

		global $bc_accounts;

		if ( ! $description ) {
			if ( ! $name && $description ) {
				return false;
			} else {
				if ( ! is_array( $description ) ) {
					$description = (array) $description;
				}
			}
		}

		$player_id = sanitize_title_with_dashes( $player_id );

		$url = esc_url_raw( self::BASE_URL . $bc_accounts->get_account_id() . '/players/' . $player_id );

		if ( $name ) {
			$data[] = sanitize_title( $name );
		}
		if ( $description ) {
			$data[] = sanitize_title( $description );
		}

		return $this->send_request( $url, 'PATCH', $data );
	}

	/**
	 * Updates the configuration of a player.
	 *
	 * Only updates configuration setting. To modify name or description, use the `player_update()` method
	 *
	 * @since 1.0.0
	 *
	 * @param string $player_id             Required. The hex representation of the player as presented by the API
	 * @param bool  $autoplay               Optional. Default is false
	 * @param array $media                  Optional. Media settings as defined in the API
	 *
	 * @return bool|mixed
	 */
	public function player_config_update( $player_id, $autoplay = false, $media = array() ) {

		global $bc_accounts;

		$player_id = sanitize_title_with_dashes( $player_id );
		$url = esc_url_raw( self::BASE_URL . $bc_accounts->get_account_id() . '/players/' . $player_id . '/configuration' );

		$data = new stdClass;
		$data->player = new stdClass;
		$data->player->autoplay = ( $autoplay ) ? true : false;

		if( is_array( $media ) ) {
			$data->media = new stdClass;
			//$data->media = (object) $media;
			foreach( $media as $key => $val ) {
				$data->media->key = is_array( $val ) ? (object) $val : $val;
			}
		}

		return $this->send_request( $url, 'PATCH', (array) $data );
	}

	/**
	 * Lists playlist enabled players
	 *
	 * Retrieves the matching players that provide playlist capabilities
	 *
	 * @param string $player_id ID of parent if looking for child players
	 *
	 * @return array|bool Array of available players or false if error
	 */
	public function player_list_playlist_enabled( $player_id = '' ) {
		$all_players = $this->player_list( $player_id );
		$players = array();
		if ( ! is_wp_error( $all_players ) && is_array( $all_players ) && isset( $all_players['items'] ) ) {
			foreach( $all_players['items'] as $key => $player ) {
				$is_playlist_enabled = ( isset( $player['branches']['master']['configuration']['playlist'] ) && true === $player['branches']['master']['configuration']['playlist'] ) ? true : false;
				if ( true === $is_playlist_enabled ) {
					$players[] = $player;
				}
			}
		} else {
			return $all_players;
		}
		$all_players['items'] = $players;
		$all_players['item_count'] = count( $players );

		return $all_players;
	}

}
