<?php

class BC_Playlist_Shortcode {

	public static function shortcode() {

		add_shortcode( 'bc_playlist', array( 'BC_Playlist_Shortcode', 'bc_playlist' ) );
	}

	/**
	 * Render video
	 *
	 * Shortcode handler for BC Video embeds
	 *
	 * @since 1.0
	 *
	 * @param array $atts Array of shortcode parameters.
	 *
	 * @return string HTML for displaying shortcode.
	 */
	public static function bc_playlist( $atts ) {

		$defaults = array(
			'player_id'   => 'default',
			'account_id'  => '',
			'playlist_id' => '',
			'autoplay'    => '',
			'embed'       => '',
			'padding_top' => '56.25%',
			'min_width'   => '0px',
			'max_width'   => '100%',
			'height'      => 0,
			'width'       => 0,
		);

		$atts = shortcode_atts( $defaults, $atts, 'bc_playlist' );

		return BC_Utility::get_playlist_player( $atts );
	}
}
