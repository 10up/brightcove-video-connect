<?php

class BC_Admin_Menu {

	public function __construct() {
			add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
			add_action( 'admin_head', array( $this, 'redirect_top_page_menu_js' ) );
	}

	/**
	 * Generates the Brightcove Menus and non-menu admin pages
	 */
	public function register_admin_menu() {
		global $submenu;

		if ( BC_Utility::current_user_can_brightcove() ) {

			add_menu_page( esc_html__( 'Brightcove', 'brightcove' ), esc_html__( 'Brightcove', 'brightcove' ), 'edit_posts', 'brightcove', array( $this, 'render_settings_page' ), plugins_url( 'images/sidebar-icon.svg', dirname( __DIR__ ) ), 50 );
			add_submenu_page( 'brightcove', esc_html__( 'Brightcove Videos', 'brightcove' ), esc_html__( 'Videos', 'brightcove' ), 'edit_posts', self::get_videos_page_uri_component(), array( $this, 'render_videos_page' ) );
			add_submenu_page( 'brightcove', esc_html__( 'Brightcove Playlists', 'brightcove' ), esc_html__( 'Playlists', 'brightcove' ), 'edit_posts', self::get_playlists_page_uri_component(), array( $this, 'render_playlists_page' ) );
			add_submenu_page( 'brightcove', esc_html__( 'Brightcove Settings', 'brightcove' ), esc_html__( 'Settings', 'brightcove' ), 'manage_options', 'brightcove-sources', array( $this, 'render_settings_page' ) );
			add_submenu_page( 'brightcove', esc_html__( 'Brightcove Labels', 'brightcove' ), esc_html__( 'Labels', 'brightcove' ), 'manage_options', 'brightcove-labels', array( $this, 'render_labels_page' ) );

			// These have no parent menu slug so they don't appear in the menu
			add_submenu_page( null, esc_html__( 'Add Source', 'brightcove' ), esc_html__( 'Add Source', 'brightcove' ), 'manage_options', 'page-brightcove-edit-source', array( $this, 'render_edit_source_page' ) );
			add_submenu_page( null, esc_html__( 'Edit Label', 'brightcove' ), esc_html__( 'Edit Label', 'brightcove' ), 'manage_options', 'page-brightcove-edit-label', array( $this, 'render_edit_label_page' ) );

			// Removes the Brightcove Submenu from the menu that WP automatically provides when registering a top level page
			array_shift( $submenu['brightcove'] );

		}
	}

	public static function get_playlists_page_uri_component() {
		return 'page-brightcove-playlists';
	}

	public static function get_videos_page_uri_component() {
		return 'page-brightcove-videos';
	}

	/**
	 * Provides hook for Settings panel to hook into
	 */
	public function render_settings_page() {
		/**
		 * Fires when the setting page loads.
		 */
		do_action( 'brightcove/admin/settings_page' );
	}

	/**
	 * Provides hook for labels page to hook into
	 */
	public function render_labels_page() {
		/**
		 * Fires when the labels page loads.
		 */
		do_action( 'brightcove/admin/labels_page' );
	}

	public function render_videos_page() {
		/**
		 * Fires when the videos page loads.
		 */
		do_action( 'brightcove/admin/videos_page' );
	}

	public function render_playlists_page() {
		/**
		 * Fires when the playlists page loads.
		 */
		do_action( 'brightcove/admin/playlists_page' );
	}

	/**
	 * Provides hook for Add/Edit source panel to hook into
	 */
	public function render_edit_source_page() {
		/**
		 * Fires when the edit source page loads.
		 */
		do_action( 'brightcove/admin/edit_source_page' );
	}

	/**
	 * Provides hook for Edit label page to hook into
	 */
	public function render_edit_label_page() {
		/**
		 * Fires when the edit label page loads.
		 */
		do_action( 'brightcove/admin/edit_label_page' );
	}

	public function redirect_top_page_menu_js() {
		global $bc_accounts;

		$account = $bc_accounts->get_account_details_for_user( get_current_user_id() );
		// If user does not have a default account, redirect to Sources page.
		if ( ! $account ) {
			?>
			<script>
				jQuery(document).ready(function($){
					$('#toplevel_page_brightcove a').attr( 'href', '<?php echo admin_url( 'admin.php?page=brightcove-sources' ); ?>' );
				});
			</script>
			<?php
		}

	}
}
