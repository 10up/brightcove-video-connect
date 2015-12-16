<?php

class BC_Players {

	protected $cms_api;
	protected $players_api;

	public function __construct() {

		$this->cms_api     = new BC_CMS_API();
		$this->players_api = new BC_Player_Management_API();

		if ( defined( 'BRIGHTCOVE_FORCE_SYNC' ) && BRIGHTCOVE_FORCE_SYNC ) {
			add_action( 'admin_init', array( $this, 'sync_players' ) );
		}
	}

	/**
	 * Initial player sync
	 *
	 * Retrieve all player and create/update when necessary.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $is_cli whether the call is coming via WP_CLI
	 *
	 * @return bool True on success or false
	 */
	public function handle_initial_sync( $is_cli = false ) {

		if ( true === $is_cli ) {
			WP_CLI::line( esc_html__('Starting Player Sync', 'brightcove' ) );
		}

		$players = $this->players_api->player_list();
		$players = $this->sort_api_response( $players );

		if ( ! is_array( $players ) ) {
			return false;
		}

		if ( true === $is_cli ) {
			WP_CLI::line( esc_html__( sprintf( 'There are %d players to sync for this account. Please be patient.', sizeof( $players ) ), 'brightcove' ) );
		}

		$player_ids_to_keep = array(); // for deleting outdated players

		/* process all players */
		foreach ( $players as $player ) {

			$this->add_or_update_wp_player( $player );
			$player_ids_to_keep[] = BC_Utility::sanitize_subscription_id( $player['id'] );

		}

		BC_Utility::remove_deleted_players( $player_ids_to_keep );

		BC_Utility::store_hash( 'players', $players, $this->cms_api->account_id );

		if ( true === $is_cli ) {
			WP_CLI::line( esc_html__('Player Sync Complete', 'brightcove' ) );
		}

		return true;
	}

	/**
	 * Retrieve all players and create/update when necessary
	 *
	 * @return bool
	 */
	public function sync_players( $retry = false ) {

		$force_sync = false;
		if ( defined( 'BRIGHTCOVE_FORCE_SYNC' ) && BRIGHTCOVE_FORCE_SYNC ) {
			$force_sync = true;
		}

		$players = $this->players_api->player_list();

		if ( ! is_array( $players ) ) {
			if ( ! $retry ) {
				return $this->sync_players( true );
			} else {
				return false; // Something happened we retried, we failed.
			}
		}

		$players = $this->sort_api_response( $players );

		if ( $force_sync || BC_Utility::hash_changed( 'players', $players, $this->cms_api->account_id ) ) {
			$player_ids_to_keep = array(); // for deleting outdated players
			/* process all players */
			foreach ( $players as $player ) {
				$this->add_or_update_wp_player( $player );
				$player_ids_to_keep[] = BC_Utility::sanitize_subscription_id( $player['id'] );
			}

			BC_Utility::remove_deleted_players( $player_ids_to_keep );

			BC_Utility::store_hash( 'players', $players, $this->cms_api->account_id );
		}

		return true;
	}

	function sort_api_response( $players ) {

		if ( ! is_array( $players ) || ! is_array( $players['items'] ) ) {
			return false;
		}

		$players = $players['items'];

		foreach ( $players as $key => $player ) {
			$id             = BC_Utility::sanitize_player_id( $player['id'] );
			$players[ $id ] = $player;
			unset( $players[ $key ] );
		}

		ksort( $players );

		return $players;

	}

	/**
	 * In the event player object data is stale in WordPress, or a player has never been generated,
	 * create/update option with Brightcove data.
	 *
	 * @param      $player
	 *
	 * @return bool success status
	 */
	public function add_or_update_wp_player( $player ) {

		global $bc_accounts;

		$force_sync = false;
		if ( defined( 'BRIGHTCOVE_FORCE_SYNC' ) && BRIGHTCOVE_FORCE_SYNC ) {
			$force_sync = true;
		}

		$hash      = BC_Utility::get_hash_for_object( $player );
		$player_id = $player['id'];

		$stored_hash = $this->get_player_hash_by_id( $player_id );

		// No change to existing player
		if ( ! $force_sync && $hash === $stored_hash ) {
			return true;
		}
		$is_playlist_enabled = ( isset( $player['branches']['master']['configuration']['playlist'] ) && true === $player['branches']['master']['configuration']['playlist'] ) ? true : false;

		$players = get_option( '_bc_player_playlist_ids_' . $bc_accounts->get_account_id() );
		// Sort out playlist-enabled players
		if ( $is_playlist_enabled ) {
			if( ! is_array( $players) || ! in_array( $player['id'], $players ) ) {
				$players[] = $player['id'];
			}
		}
		else {
			// Delete players that may be set but aren't playlist-enabled
			if( is_array( $players) && ! in_array( $player['id'], $players ) ) {
				unset( $players[array_search( $player['id'], $players)] );
			}
		}

		update_option( '_bc_player_playlist_ids_' . $bc_accounts->get_account_id(), $players );

		$key = BC_Utility::get_player_key( $player_id );

		return update_option( $key, $player );
	}

	/**
	 * Accepts a player ID and checks to see if there is an option in WordPress. Returns the player object on success and false on failure.
	 *
	 * @param $player_id
	 *
	 * @return player_object|false
	 */
	public function get_player_by_id( $player_id ) {

		$key = BC_Utility::get_player_key( $player_id );

		return get_option( $key );
	}

	public function get_player_hash_by_id( $player_id ) {

		$player = $this->get_player_by_id( $player_id );

		if ( ! $player ) {
			return false;
		} else {
			return BC_Utility::get_hash_for_object( $player );
		}
	}
}
