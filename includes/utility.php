<?php
/**
 * Utility functions for the plugin.
 *
 * @package Brightcove_Video_Connect
 */

namespace Brightcove\Utility;

/**
 * Get asset info from extracted asset files
 *
 * @param string $slug Asset slug as defined in build/webpack configuration
 * @param string $attribute Optional attribute to get. Can be version or dependencies
 * @return ($attribute is null ? array{version: string, dependencies: array<string>} : $attribute is 'dependencies' ? array<string> : string)
 */
function get_asset_info( $slug, $attribute = null ) {
	if ( file_exists( BRIGHTCOVE_PATH . 'dist/js/' . $slug . '.asset.php' ) ) {
		$asset = require BRIGHTCOVE_PATH . 'dist/js/' . $slug . '.asset.php';
	} elseif ( file_exists( BRIGHTCOVE_PATH . 'dist/css/' . $slug . '.asset.php' ) ) {
		$asset = require BRIGHTCOVE_PATH . 'dist/css/' . $slug . '.asset.php';
	} else {
		$asset = [
			'version'      => BRIGHTCOVE_VERSION,
			'dependencies' => [],
		];
	}
	if ( ! empty( $attribute ) && isset( $asset[ $attribute ] ) ) {
		return $asset[ $attribute ];
	}

	return $asset;
}

/**
 * The list of knows contexts for enqueuing scripts/styles.
 *
 * @return array<string>
 */
function get_enqueue_contexts() {
	return [ 'admin', 'frontend', 'shared' ];
}

/**
 * Generate an URL to a script, taking into account whether SCRIPT_DEBUG is enabled.
 *
 * @param string $script Script file name (no .js extension)
 * @param string $context Context for the script ('admin', 'frontend', or 'shared')
 *
 * @throws \RuntimeException If an invalid $context is specified.
 *
 * @return string URL
 */
function script_url( $script, $context ) {

	if ( ! in_array( $context, get_enqueue_contexts(), true ) ) {
		throw new \RuntimeException( 'Invalid $context specified in TenUpPlugin script loader.' );
	}

	return BRIGHTCOVE_URL . "dist/js/{$script}.js";
}

/**
 * Generate an URL to a stylesheet, taking into account whether SCRIPT_DEBUG is enabled.
 *
 * @param string $stylesheet Stylesheet file name (no .css extension)
 * @param string $context Context for the script ('admin', 'frontend', or 'shared')
 *
 * @throws \RuntimeException If an invalid $context is specified.
 *
 * @return string URL
 */
function style_url( $stylesheet, $context ) {

	if ( ! in_array( $context, get_enqueue_contexts(), true ) ) {
		throw new \RuntimeException( 'Invalid $context specified in TenUpPlugin stylesheet loader.' );
	}

	return BRIGHTCOVE_URL . "dist/css/{$stylesheet}.css";
}
