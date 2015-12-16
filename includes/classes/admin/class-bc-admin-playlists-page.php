<?php

class BC_Admin_Playlists_Page {

	public function __construct() {
		add_action( 'brightcove/admin/playlists_page', array( $this, 'render' ) );
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
			<h2><img class="bc-page-icon" src="<?php echo esc_url( BRIGHTCOVE_URL . 'images/admin/menu-icon.svg' ); ?>"> <?php esc_html_e( 'Brightcove Playlists', 'brightcove' ); ?></h2>
		</span>
		<div class="brightcove-media-playlists"></div>
	<?php
	}
}