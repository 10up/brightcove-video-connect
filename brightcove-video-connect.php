<?php
/**
 * Plugin Name: Brightcove Video Connect
 * Plugin URI: https://wordpress.org/plugins/brightcove-video-connect/
 * Description: A Brightcove™ Connector for WordPress that leverages enhanced APIs and Brightcove™ Capabilities
 * Version: 1.4.0
 * Author: 10up
 * Author URI: http://10up.com
 * License: GPLv2+
 * Text Domain: brightcove
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2015 10up
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  021.0.2301  USA
 */

define( 'BRIGHTCOVE_VERSION', '1.4.0' );
define( 'BRIGHTCOVE_URL', plugin_dir_url( __FILE__ ) );
define( 'BRIGHTCOVE_PATH', dirname( __FILE__ ) . '/' );
define( 'BRIGHTCOVE_BASENAME', plugin_basename( __FILE__ ) );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once( BRIGHTCOVE_PATH . 'cli/class-brightcove-cli.php' );
}
/**
 * Activate the plugin
 */
function brightcove_activate() {

	BC_Utility::activate();
}

/**
 * Deactivate the plugin
 * Uninstall routines should be in uninstall.php
 */
function brightcove_deactivate() {

	BC_Utility::deactivate();
}

// Wireup actions.
global $pagenow;

if ( in_array( $pagenow, array( 'admin-ajax.php', 'admin.php', 'post-new.php', 'edit.php', 'post.php' ) ) ) {

	add_action( 'init', array( 'BC_Setup', 'action_init' ) );
	add_action( 'init', array( 'BC_Setup', 'bc_check_minimum_wp_version' ) );

} else {

	require_once( BRIGHTCOVE_PATH . 'includes/class-bc-playlist-shortcode.php' );
	require_once( BRIGHTCOVE_PATH . 'includes/class-bc-video-shortcode.php' );
	require_once( BRIGHTCOVE_PATH . 'includes/class-bc-utility.php' );
	require_once( BRIGHTCOVE_PATH . 'includes/class-bc-accounts.php' );
	require_once( BRIGHTCOVE_PATH . 'includes/api/class-bc-api.php' );
	require_once( BRIGHTCOVE_PATH . 'includes/api/class-bc-oauth.php' );
	require_once( BRIGHTCOVE_PATH . 'includes/api/class-bc-player-management-api.php' );

	global $bc_accounts;

	$bc_accounts = new BC_Accounts();

	add_action( 'admin_notices', array( 'BC_Setup', 'bc_activation_admin_notices' ) );

}

add_action( 'init', array( 'BC_Video_Shortcode', 'shortcode' ), 11 );
add_action( 'init', array( 'BC_Playlist_Shortcode', 'shortcode' ), 11 );
add_action( 'init', array( 'BC_Setup', 'action_init_all' ), 9 ); // Ensures the menu is loaded on all pages.
add_action( 'init', array( 'BC_Notification_API', 'setup' ), 9 );

//add_action( 'brightcove_upgrade', array( 'BC_Notification_API', 'maybe_backport_subscriptions' ) ); // @TODO Verify API as errors don't seem to match the documentation

if ( ! defined( 'WPCOM_IS_VIP_ENV' ) || ! WPCOM_IS_VIP_ENV ) {

	// Activation / Deactivation.
	register_deactivation_hook( __FILE__, 'brightcove_deactivate' );
	register_activation_hook( __FILE__, 'brightcove_activate' );

	// Add settings to plugin action links.
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( 'BC_Utility', 'bc_plugin_action_links' ) );

}

// Add WP-CLI Support (should be before init).
require_once( BRIGHTCOVE_PATH . 'includes/class-bc-setup.php' );
require_once( BRIGHTCOVE_PATH . 'includes/class-bc-notification-api.php' );

// Upgrade routine
$installed = get_option( 'brightcove_version' );
if ( ! $installed || version_compare( $installed, BRIGHTCOVE_VERSION, '<' ) ) {
	/**
	 * Upgrade the Brightcove installation to add missing settings or event listeners.
	 *
	 * @param string $installed
	 */
	do_action( 'brightcove_upgrade', $installed );

	// Store the version installed for later
	add_option( 'brightcove_version', BRIGHTCOVE_VERSION, '', 'yes' );
}
