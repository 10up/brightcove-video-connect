<?php

class BC_Experiences_Shortcode {

	/**
	 * Register the bc_video shortcode with WordPress
	 */
	public static function shortcode() {

		add_shortcode( 'bc_experience', array( 'BC_Experiences_Shortcode', 'bc_experience' ) );
	}

	/**
	 * Render Experience
	 *
	 * Shortcode handler for BC Experiences embeds
	 *
	 * @since 1.4.2
	 *
	 * @param array $atts Array of shortcode parameters.
	 *
	 * @return string HTML for displaying shortcode.
	 */
	public static function bc_experience( $atts ) {

		$defaults = array(
			'experience_id' => '',
			'account_id'    => '',
			'embed'         => '',
			'min_width'     => '0px',
			'max_width'     => '100%',
			'height'        => 0,
			'width'         => 0,
			'video_ids'     => '',
			'playlist_id'   => '',
		);

		$atts = shortcode_atts( $defaults, $atts, 'bc_experience' );

		return BC_Utility::get_experience_player( $atts );
	}
}
