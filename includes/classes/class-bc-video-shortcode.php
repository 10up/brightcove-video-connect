<?php

class BC_Video_Shortcode {

	/**
	 * Register the bc_video shortcode with WordPress
	 */
	public static function shortcode() {

		add_shortcode( 'bc_video', array( 'BC_Video_Shortcode', 'bc_video' ) );
	}

	/**
	 * Shortcode handler for BC Video embeds
	 *
	 * @param $atts
	 *
	 * @return string
	 */
	public static function bc_video( $atts ) {

		$defaults = array(
			'player_id'  => '',
			'account_id' => '',
			'video_id'   => '',
			'height'     => 250,
			'width'      => 500,
		);

		$atts = shortcode_atts( $defaults, $atts, 'bc_video' );

		return BC_Video_Shortcode::player( $atts['video_id'], $atts['account_id'], $atts['player_id'], $atts['width'], $atts['height'] );
	}

	/**
	 * Renders the iFrame player from Brightcove based on passed parameters
	 *
	 * @param     $video_id
	 * @param     $account_id
	 * @param     $player_id
	 * @param int $width
	 * @param int $height
	 *
	 * @return string
	 */
	public static function player( $video_id, $account_id, $player_id, $width = 500, $height = 250 ) {

		// Sanitize and Verify
		$account_id = BC_Utility::sanitize_id( $account_id );
		$player_id  = 'default' == $player_id ? 'default' : BC_Utility::sanitize_id( $player_id );
		$video_id   = BC_Utility::sanitize_id( $video_id );
		$height     = (int) $height;
		$width      = (int) $width;

		$html = '<div style="width: ' . $width . 'px; height: ' . $height . 'px">';

		$html .= '<style>
			.video-js {
			    height: 100%;
			    width: 100%;
			}
			.vjs-big-play-button {
				display: none;
			}
			</style>';

		$html .= '<!-- Start of Brightcove Player -->';
		$html .= sprintf(
			'<video data-account="%s" data-player="%s" data-embed="default" data-video-id="%s" class="video-js" controls></video>',
			$account_id,
			$player_id,
			$video_id
		);
		$html .= sprintf(
			'<script src="//players.brightcove.net/%s/%s_default/index.min.js"></script>',
			$account_id,
			$player_id
		);
		$html .= '<!-- End of Brightcove Player -->';

		$html .= '</div>';

		return $html;
	}

	/**
	 * Determines if a video has recieved a callback alerting us that it is transcoded and ready for use
	 *
	 * @param $video_id
	 *
	 * @return bool
	 */
	public static function is_video_transcoded( $video_id ) {

		$videos = new BC_Videos( new BC_CMS_API( new BC_Accounts ) );
		$video  = $videos->get_video_by_id( $video_id );

		if ( ! $video ) {
			return new WP_Error( 'brightcove-still-being-transcoded', esc_html__( 'No video was found', 'brightcove' ) );
		}

		$is_transcoded = get_post_meta( $video->ID, '_brightcove_transcoded', true );

		return '1' === $is_transcoded;
	}
}
