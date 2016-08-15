<?php

class BC_Admin_Videos_Page {

	public function __construct() {
		add_action( 'brightcove/admin/videos_page', array( $this, 'render' ) );
        add_action( 'current_screen', array( $this, 'verify_source_configuration' ) );
	}

	/**
	 * Generates an HTML table with all configured sources
	 */
	public function render() {
		/*
		 * This is all handled by the Backbone app,
		 * refer to app.js->load() for the hook into
		 * the Backbone app for video-media.
		 *
		 * Span .wrap is so the add-new stylings from core can be applied
		 */
		?>
		<span class="wrap">
			<h2>
				<img class="bc-page-icon" src="<?php echo esc_url( BRIGHTCOVE_URL . 'images/menu-icon.svg' ); ?>"> <?php esc_html_e( 'Brightcove Videos', 'brightcove' ); ?>
				<a class="brightcove-add-new-video add-new-h2" href="#add-new-brightcove-video"><?php esc_html_e( 'Add New', 'brightcove' ); ?></a>
			</h2>
		</span>
		<div class="brightcove-media-videos"></div>
		<?php
	}

    public function verify_source_configuration() {

	    global $bc_accounts;

        if( 'brightcove_page_page-brightcove-videos' !== get_current_screen()->id ) {
            return false;
        }

        $account = $bc_accounts->get_account_details_for_user( get_current_user_id() );
        if( ! $account ) {
            wp_safe_redirect( admin_url( 'admin.php?page=brightcove-sources' ) );
        }
    }
}
