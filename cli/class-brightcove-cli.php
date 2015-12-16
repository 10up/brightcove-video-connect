<?php

/**
 * Implements a set of commands to interact with the Brightcove Video Connect plugin
 */
class BC_Brightcove_CLI extends WP_CLI_Command {

	/**
	 * Runs an activation routine for the plugin
	 */
	public function activate() {
		update_option( '_brightcove_plugin_activated', true, 'no' );
		flush_rewrite_rules();
	}

	/**
	 * Runs the deactivation routine for the plugin
	 */
	public function deactivate() {
		BC_Utility::deactivate();
	}

	/**
	 * Cleanup on uninstall
	 */
	public function uninstall() {
		BC_Utility::uninstall_plugin();
	}
}
WP_CLI::add_command( 'brightcove', 'BC_Brightcove_CLI' );