<?php

class BC_Playlist_Shortcode_Tests extends TestCase {

	protected $testFiles = [
		'classes/class-bc-playlist-shortcode.php'
	];

	public function mock_bc_playlist_shortcode() {

		$BC_Playlist_Shortcode = $this->getMockBuilder( 'BC_Playlist_Shortcode' )
		                              ->setMethods( null )
		                              ->getMock();

		return $BC_Playlist_Shortcode;

	}

	public function test_shortcode() {

		$BC_Playlist_Shortcode = $this->mock_bc_playlist_shortcode();


		\WP_Mock::wpFunction( 'add_shortcode', array(
			'args'  => array( 'bc_playlist', array( 'BC_Playlist_Shortcode', 'bc_playlist' ) ),
			'times' => 1,
		) );

		//act
		$BC_Playlist_Shortcode->shortcode();

		$this->assertConditionsMet();
	}

	public function test_bc_playlist_return_false() {

		$BC_Playlist_Shortcode = $this->mock_bc_playlist_shortcode();

		$defaults = array(
			'playlist_id' => '',
			'account_id'  => '',
			'height'      => 250,
			'width'       => 500, // Nothing shows up playlist-wise when width is under 640px
		);

		$atts = array(
			'playlist_id' => false,
			'account_id'  => '',
			'height'      => 250,
			'width'       => 500, // Nothing shows up playlist-wise when width is under 640px
		);


		\WP_Mock::wpFunction( 'shortcode_atts', array(
			'args'   => array( $defaults, $atts, 'bc_playlist' ),
			'times'  => 1,
			'return' => $atts
		) );


		$this->assertEquals( false, $BC_Playlist_Shortcode->bc_playlist( $atts ) );

	}

	public function test_bc_playlist_return_empty() {

		$BC_Playlist_Shortcode = $this->mock_bc_playlist_shortcode();

		$defaults = array(
			'playlist_id' => '',
			'account_id'  => '',
			'height'      => 250,
			'width'       => 500, // Nothing shows up playlist-wise when width is under 640px
		);

		$atts                = $defaults;
		$atts['playlist_id'] = '5229317772001';
		$atts['account_id']  = '4229317772001';


		\WP_Mock::wpFunction( 'shortcode_atts', array(
			'args'   => array( $defaults, $atts, 'bc_playlist' ),
			'times'  => 1,
			'return' => $atts
		) );

		\WP_Mock::wpFunction( 'get_option', array(
			'args'   => array( '_bc_player_playlist_ids_' . $atts['account_id'] ),
			'times'  => 1,
			'return' => false
		) );

		$this->assertEquals( '', $BC_Playlist_Shortcode->bc_playlist( $atts ) );

	}

	public function test_bc_playlist_return_html() {
		$BC_Playlist_Shortcode = $this->mock_bc_playlist_shortcode();

		$defaults = array(
			'playlist_id' => '',
			'account_id'  => '',
			'height'      => 250,
			'width'       => 500, // Nothing shows up playlist-wise when width is under 640px
		);

		$atts = array(
			'playlist_id' => '5229317772001',
			'account_id'  => '4229317772001',
			'height'      => 250,
			'width'       => 500, // Nothing shows up playlist-wise when width is under 640px
		);


		\WP_Mock::wpFunction( 'shortcode_atts', array(
			'args'   => array( $defaults, $atts, 'bc_playlist' ),
			'times'  => 1,
			'return' => $atts
		) );

		\WP_Mock::wpFunction( 'get_option', array(
			'args'   => array( '_bc_player_playlist_ids_' . $atts['account_id'] ),
			'times'  => '',
			'return' => array( 'pleyakey' )
		) );

		\WP_Mock::onFilter( 'brightcove_player_key' )
		        ->with( 'This is unfiltered' )
		        ->reply( 'pleyakey' );

		\WP_Mock::wpFunction( 'esc_attr', array(
			'times'      => 3,
			'return_arg' => 0
		) );


		$html = sprintf( '<iframe style="width: ' . intval( $atts['width'] ) . 'px; height: ' . intval( $atts['height'] ) . 'px;" src="//players.brightcove.net/%1$s/%2$s_default/index.html?playlistId=%3$s" height="%4$s" width="%5$s" allowfullscreen webkitallowfullscreen mozallowfullscreen></iframe>', '', 'pleyakey', '', $atts['height'], $atts['width'] );

		$this->assertEquals( $html, $BC_Playlist_Shortcode->bc_playlist( $atts ) );
	}

}