<?php

class BC_Video_Shortcode {

	/**
	 * Register the bc_video shortcode with WordPress
	 */
	public static function shortcode() {

		add_shortcode( 'bc_video', array( 'BC_Video_Shortcode', 'bc_video' ) );
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
	public static function bc_video( $atts ) {

		$defaults = array(
			'player_id'   => 'default',
			'account_id'  => '',
			'video_id'    => '',
			'autoplay'    => '',
			'embed'       => '',
			'padding_top' => '56.25%',
			'min_width'   => '0px',
			'max_width'   => '100%',
			'height'      => 0,
			'width'       => 0,
		);

		$atts = shortcode_atts( $defaults, $atts, 'bc_video' );

		return BC_Utility::get_video_player( $atts );
	}
}
