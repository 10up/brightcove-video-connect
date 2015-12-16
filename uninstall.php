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

BC_Utility::uninstall_plugin();
