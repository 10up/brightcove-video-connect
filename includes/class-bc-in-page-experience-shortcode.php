<?php

/**
 * BC_In_Page_Experience_Shortcode Class
 *
 * @since 2.4.0
 */
class BC_In_Page_Experience_Shortcode {
	/**
	 * Register the bc_in_page_experiences shortcode with WordPress
	 */
	public static function shortcode() {
		add_shortcode( 'bc_in_page_experience', array( 'BC_In_Page_Experience_Shortcode', 'bc_in_page_experience' ) );
	}

	/**
	 * Render video
	 *
	 * Shortcode handler for BC Video embeds
	 *
	 * @since 2.4.0
	 *
	 * @param array $atts Array of shortcode parameters.
	 *
	 * @return string HTML for displaying shortcode.
	 */
	public static function bc_in_page_experience( $atts ) {
		$defaults = array(
			'account_id'            => '',
			'in_page_experience_id' => '',
			'embed'                 => '',
			'height'                => '',
			'width'                 => '',
		);

		$atts = shortcode_atts( $defaults, $atts, 'bc_in_page_experience' );

		$is_in_page_embed = 'in-page' === $atts['embed'];

		$published_url = 'https://players.brightcove.net/' . $atts['account_id'] . '/experience_' . $atts['in_page_experience_id'];
		$style         = 'display:block;border:none;margin-left:auto;margin-right:auto;';

		if ( $is_in_page_embed ) {
			$published_url .= '/live.js';
		} else {
			$published_url .= '/index.html';

			if ( ! empty( $atts['width'] ) ) {
				$style .= 'width: ' . $atts['width'] . ';';
			}

			if ( ! empty( $atts['height'] ) ) {
				$style .= 'height: ' . $atts['height'] . ';';
			}
		}

		ob_start();
		?>
			<!-- Start of Brightcove In-Page Experience Player -->
			<?php if ( $is_in_page_embed ) : ?>
				<div data-experience="<?php echo esc_attr( $atts['in_page_experience_id'] ); ?>"></div>
				<script src="<?php echo esc_url( $published_url ); ?>"></script>
			<?php else : ?>
				<iframe 
				src="<?php echo esc_url( $published_url ); ?>" 
				allow="autoplay; fullscreen; geolocation; encrypted-media" 
				allowfullscreen 
				webkitallowfullscreen 
				mozallowfullscreen
				style="<?php echo esc_attr( $style ); ?>">
				</iframe>
			<?php endif; ?>
			<!-- End of Brightcove In-Page Experience Player -->
		<?php

		$html = ob_get_clean();

		return apply_filters( 'brightcove_in_page_experience_html', $html, $atts['experience_id'] );
	}
}
