<?php
/**
 * Permissions model for accessing Brightcove.
 *
 * @package Brightcove_Video_Connect
 * SA = Super Admin
 * AD = Admin
 * ED = Editor
 * AU = Author
 * CO = Contributor
 *
 * Capability/Role                                  SA AD ED AU CO
 * Set default Brightcove source for own Account     Y  Y  Y  Y  Y
 * View videos                                       Y  Y  Y  Y  Y
 * View playlists                                    Y  Y  Y  Y  Y
 * Insert videos into posts                          Y  Y  Y  Y  Y
 * Upload new videos                                 Y  Y  Y
 * Edit video metadata                               Y  Y  Y
 * Delete videos                                     Y  Y  Y
 * Add/Edit/Delete Brightcove sources                Y  Y
 * Set default Brightcove source for WordPress site  Y  Y
 */

/**
 * Brightcove_Permissions class.
 */
class BC_Permissions {

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wp_roles;
		if ( ! isset( $wp_roles->roles['administrator']['capabilities']['brightcove_manipulate_accounts'] ) ) {
			$this->add_admin_capabilities();
		}

		if ( ! isset( $wp_roles->roles['editor']['capabilities']['brightcove_manipulate_videos'] ) ) {
			$this->add_editor_capabilities();
		}
	}

	/**
	 * Adds new capabilities to administrators to manage videos.
	 */
	protected function add_admin_capabilities() {

		$admin_roles = array(
			'brightcove_manipulate_accounts',
			'brightcove_set_site_default_account',
			'brightcove_set_user_default_account',
			'brightcove_get_user_default_account',
			'brightcove_manipulate_playlists',
			'brightcove_manipulate_videos',
		);

		if ( defined( 'WPCOM_IS_VIP_ENV' ) && WPCOM_IS_VIP_ENV ) {
			wpcom_vip_add_role_caps( 'administrator', $admin_roles );
		} else {

			$administrator = get_role( 'administrator' );

			foreach ( $admin_roles as $admin_role ) {
				$administrator->add_cap( $admin_role );
			}
		}
	}

	/**
	 * Adds new capabilities to editors to manage videos.
	 */
	protected function add_editor_capabilities() {

		$editor_roles = array(
			'brightcove_manipulate_playlists',
			'brightcove_manipulate_videos',
		);

		if ( defined( 'WPCOM_IS_VIP_ENV' ) && WPCOM_IS_VIP_ENV ) {
			wpcom_vip_add_role_caps( 'editor', $editor_roles );
		} else {

			$editor = get_role( 'editor' );

			foreach ( $editor_roles as $editor_role ) {
				$editor->add_cap( $editor_role );
			}
		}
	}

}
