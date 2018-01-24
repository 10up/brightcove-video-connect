<?php

/**
 * Interface to the Brightcove Player Management APIs version 2.
 *
 * Handles interaction to the Brightcove Player Management and Embed APIs allowing
 * for the manipulation of parent and child players.
 *
 * @since   1.4.0
 *
 * @link    https://brightcovelearning.github.io/Brightcove-API-References/player-management-api/v2/doc/index.html
 * @package Brightcove_Video_Connect
 */
class BC_Player_Management_API2 extends BC_API {

	/**
	 * Base URL of the Player Management API and Embed API.
	 */
	const BASE_URL = 'https://players.api.brightcove.com/v2/accounts/';

	/**
	 * Setup processing of Player Management API
	 */
	public function __construct() {
		parent::__construct();
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
	 * Get the list of all players for all accounts available.
	 *
	 * @return array Array of available players.
	 */
	public function get_all_players() {
		global $bc_accounts;

		$all_accounts_id = $bc_accounts->get_all_accounts_id();
		$players         = array();

		foreach ( $all_accounts_id as $account_id ) {
			$bc_accounts->set_current_account_by_id( $account_id );

			$url             = esc_url_raw( self::BASE_URL . $account_id . '/players/' );
			$account_players = $this->send_request( $url );

			$players[ $account_id ] = array();

			foreach ( $account_players['items'] as $player ) {
				$player['is_playlist'] = false;

				if ( isset( $player['branches']['master']['configuration']['playlist'] ) && $player['branches']['master']['configuration']['playlist'] ) {
					$player['is_playlist'] = true;
				} else {
					if ( isset( $player['branches']['master']['configuration']['plugins'] ) ) {
						$plugins = $player['branches']['master']['configuration']['plugins'];

						foreach ( $plugins as $plugin ) {
							if ( isset( $plugin['register_id'] ) && strpos( $plugin['register_id'], 'videojs-bc-playlist-ui' ) !== false ) {
								$player['is_playlist'] = true;
							}
						}
					}
				}

				$players[ $account_id ][] = $player;
			}
		}

		return apply_filters( 'brightcove_get_all_player', $players );
	}
}
