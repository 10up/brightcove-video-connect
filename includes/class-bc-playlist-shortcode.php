<?php

class BC_Playlist_Shortcode {

	public function __construct() {

	}

	public static function shortcode() {
		add_shortcode( 'bc_playlist', array( 'BC_Playlist_Shortcode', 'bc_playlist' ) );
	}

	public static function bc_playlist( $atts ) {
		global $allowedtags, $bc_accounts;

		$defaults = array(
			'playlist_id'   => '',
			'account_id'    => '',
			'height'        => 250,
			'width'         => 500, // Nothing shows up playlist-wise when width is under 640px
		);

		$atts = shortcode_atts( $defaults, $atts, 'bc_playlist' );

		if( false === $atts['playlist_id'] ) {
			return false;
		}

		$players = get_option( '_bc_player_playlist_ids_' . $atts['account_id'] );

		if( ! $players || !is_array( $players ) ) {
			return '';
		}
		$player_key = apply_filters( 'brightcove_player_key', array_shift( $players ) );

		$html = sprintf( '<iframe style="width: ' . intval( $atts['width'] ). 'px; height: ' . intval( $atts['height'] ) . 'px;" src="//players.brightcove.net/%1$s/%2$s_default/index.html?playlistId=%3$s" height="%4$s" width="%5$s" allowfullscreen webkitallowfullscreen mozallowfullscreen></iframe>', BC_Utility::sanitize_id( $atts['account_id'] ), esc_attr( $player_key ), BC_Utility::sanitize_id( $atts['playlist_id'] ), esc_attr( $atts['height'] ), esc_attr( $atts['width'] ) );

		return $html;
		}
}