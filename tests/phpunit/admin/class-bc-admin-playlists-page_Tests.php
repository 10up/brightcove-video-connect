<?php

class BC_Admin_Playlists_Page_Test extends TestCase {

	protected $testFiles = [
		'classes/admin/class-bc-admin-playlists-page.php'
	];

	public function mock_bc_admin_playlist_page() {

		$BC_Admin_Playlists_Page = $this->getMockBuilder( 'BC_Admin_Playlists_Page' )
		                                ->setMethods( null )
		                                ->getMock();


		return $BC_Admin_Playlists_Page;

	}

	public function test__construct() {
		$this->markTestIncomplete(); //filter and action hooks in constructor
	}

	public function test_render() {

		$BC_Admin_Playlists_Page = $this->mock_bc_admin_playlist_page();
		\WP_Mock::wpPassthruFunction( 'esc_url', array( 'times' => 1 ) );

		if ( ! defined( 'BRIGHTCOVE_URL' ) ) {
			define( 'BRIGHTCOVE_URL', '/' );
		}


		\WP_Mock::wpFunction( 'esc_html_e', array(
			'args'   => array( 'Brightcove Playlists', 'brightcove' ),
			'times'  => 1,
			'return' => 'Brightcove Playlists'
		) );

		$OriginalOutput =

			'<span class="wrap">
			<h2><img class="bc-page-icon" src="' . BRIGHTCOVE_URL . 'images/admin/menu-icon.svg"> </h2>
		</span>
		<div class="brightcove-media-playlists"></div>';


		$expectedOutput = $BC_Admin_Playlists_Page->render();

		$this->expectOutputString( $OriginalOutput, $expectedOutput );

		$this->assertConditionsMet();


	}
}