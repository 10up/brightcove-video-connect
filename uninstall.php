<?php

/**
 * Uninstall Brightcove
 *
 * Removes all Brightcove data stored by the plugin
 *
 * @since   1.0.0
 *
 * @package Brightcove_Video_Connect
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) || ! WP_UNINSTALL_PLUGIN ) {
	exit();
}

if ( ! class_exists( 'BC_Utility' ) ) {
	require( dirname( __FILE__ ) . '/includes/class-bc-utility.php' );
}

BC_Utility::uninstall_plugin();
