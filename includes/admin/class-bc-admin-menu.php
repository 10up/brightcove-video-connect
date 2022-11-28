<?php
/**
 * BC_Admin_Menu class file.
 *
 * @package Brightcove_Video_Connect
 */

/**
 * BC_Admin_Menu class
 */
class BC_Admin_Menu {

	/**
	 * Constructor method
	 */
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
			if ( is_array( $submenu['brightcove'] ) ) {
				array_shift( $submenu['brightcove'] );
			}

		}
	}

	/**
	 * Gets the playlists page URI
	 *
	 * @return string
	 */
	public static function get_playlists_page_uri_component() {
		return 'page-brightcove-playlists';
	}

	/**
	 * Get the URI for the video page
	 *
	 * @return string
	 */
	public static function get_videos_page_uri_component() {
		return 'page-brightcove-videos';
	}

	/**
	 * Provides hook for Settings panel to hook into
	 */
	public function render_settings_page() {
		/**
		 * Fires when the setting page loads.
		 *
		 * This hook doesn't follow standard naming convention but needs to stay as it is for retro compatibility.
		 */
		do_action_deprecated( 'brightcove/admin/settings_page', [], '2.7.1' );
		do_action( 'brightcove_admin_settings_page' );
	}

	/**
	 * Provides hook for labels page to hook into
	 */
	public function render_labels_page() {
		/**
		 * Fires when the labels page loads.
		 *
		 * This hook doesn't follow standard naming convention but needs to stay as it is for retro compatibility.
		 */
		do_action_deprecated( 'brightcove/admin/labels_page', [], '2.7.1' );
		do_action( 'brightcove_admin_labels_page' );
	}

	/**
	 * Render the videos page
	 */
	public function render_videos_page() {
		/**
		 * Fires when the videos page loads.
		 *
		 * This hook doesn't follow standard naming convention but needs to stay as it is for retro compatibility.
		 */
		do_action_deprecated( 'brightcove/admin/videos_page', [], '2.7.1' );
		do_action( 'brightcove_admin_videos_page' );
	}

	/**
	 * Render playlists page
	 */
	public function render_playlists_page() {
		/**
		 * Fires when the playlists page loads.
		 *
		 * This hook doesn't follow standard naming convention but needs to stay as it is for retro compatibility.
		 */
		do_action_deprecated( 'brightcove/admin/playlists_page', [], '2.7.1' );
		do_action( 'brightcove_admin_playlists_page' );
	}

	/**
	 * Provides hook for Add/Edit source panel to hook into
	 */
	public function render_edit_source_page() {
		/**
		 * Fires when the edit source page loads.
		 *
		 * This hook doesn't follow standard naming convention but needs to stay as it is for retro compatibility.
		 */
		do_action_deprecated( 'brightcove/admin/edit_source_page', [], '2.7.1' );
		do_action( 'brightcove_admin_edit_source_page' );
	}

	/**
	 * Provides hook for Edit label page to hook into
	 */
	public function render_edit_label_page() {
		/**
		 * Fires when the edit label page loads.
		 *
		 * This hook doesn't follow standard naming convention but needs to stay as it is for retro compatibility.
		 */
		do_action_deprecated( 'brightcove/admin/edit_label_page', [], '2.7.1' );
		do_action( 'brightcove_admin_edit_label_page' );
	}

	/**
	 * Redirects to the top level menu.
	 */
	public function redirect_top_page_menu_js() {
		global $bc_accounts;

		$account = $bc_accounts->get_account_details_for_user( get_current_user_id() );
		// If user does not have a default account, redirect to Sources page.
		if ( ! $account ) {
			?>
			<script>
				jQuery(document).ready(function($){
					$('#toplevel_page_brightcove a').attr( 'href', '<?php echo esc_url( admin_url( 'admin.php?page=brightcove-sources' ) ); ?>' );
				});
			</script>
			<?php
		}

	}
}
