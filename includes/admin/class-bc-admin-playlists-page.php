<?php

class BC_Admin_Playlists_Page {

	public function __construct() {

		add_action( 'brightcove/admin/playlists_page', array( $this, 'render' ) );
		add_action( 'admin_notices', array( $this, 'validate_players' ) );
	}

	/**
	 * Generates an HTML table with all configured sources
	 */
	public function render() {

		/*
		 * This is all handled by the Backbone app,
		 * refer to app.js->load() for the hook into
		 * the Backbone app for playlist-media.
		 */
		?>
		<span class="wrap">
			<h2>
				<img class="bc-page-icon" src="<?php echo esc_url( BRIGHTCOVE_URL . 'images/menu-icon.svg' ); ?>"> <?php esc_html_e( 'Brightcove Playlists', 'brightcove' ); ?>
			</h2>
		</span>
		<div class="brightcove-media-playlists"></div>
		<?php
	}

	/**
	 * Ensure there is a playlist capable player available
	 */
	public function validate_players() {

		global $pagenow, $plugin_page;

		// only on admin.php?page=page-brightcove-playlists
		if ( $pagenow != 'admin.php' || $plugin_page != 'page-brightcove-playlists' ) {
			return;
		}

		$player_api = new BC_Player_Management_API();
		$players    = $player_api->player_list_playlist_enabled();
		if ( is_wp_error( $players ) || ! is_array( $players ) || $players['item_count'] < 1 ) {
			BC_Utility::admin_notice_messages( array(
				                                   array(
					                                   'message' => __( 'A specified Source does not have a playlist capable player <a href="https://studio.brightcove.com/products/videocloud/players/">configured</a>. Make sure there is at least one player with "Display Playlist" enabled.', 'brightcove' ),
					                                   'type'    => 'error',
				                                   ),
			                                   )
			);
		}
	}
}
