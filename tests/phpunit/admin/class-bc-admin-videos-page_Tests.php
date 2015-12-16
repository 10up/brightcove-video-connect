<?php

class BC_Admin_Videos_Page_Test extends TestCase {

	protected $testFiles = [
		'classes/admin/class-bc-admin-videos-page.php'
	];


	public function mock_bc_admin_videos_page() {

		$BC_Admin_Videos_Page = $this->getMockBuilder( 'BC_Admin_Videos_Page' )
		                             ->setMethods( null )
		                             ->getMock();

		return $BC_Admin_Videos_Page;

	}


	public function test__construct() {
		$this->markTestIncomplete(); //actions in constructor
	}


	public function test_render() {
		$BC_Admin_Videos_Page = $this->mock_bc_admin_videos_page();

		\WP_Mock::wpPassthruFunction( 'esc_url', array( 'times' => 1 ) );

		\WP_Mock::wpFunction( 'esc_html_e', array(
			'times'  => 1,
			'return' => ''
		) );

		if ( ! defined( 'BRIGHTCOVE_URL' ) ) {
			define( 'BRIGHTCOVE_URL', 'http://bc/test/url' );
		}


		ob_start();

		?>
		<span class="wrap">
			<h2>
				<img class="bc-page-icon" src="<?php echo BRIGHTCOVE_URL . 'images/admin/menu-icon.svg'; ?>">
				<a class="brightcove-add-new-video add-new-h2" href="#">Add New</a>
			</h2>
		</span>
		<div class="brightcove-media-videos"></div>
		<?php

		$original = ob_get_contents();
		ob_end_clean();

		ob_start();
		$BC_Admin_Videos_Page->render();
		$expected = ob_get_contents();
		ob_end_clean();

		$original = preg_replace( '/\s+/', '', $original );
		$expected = preg_replace( '/\s+/', '', $expected );
		$this->assertEquals( $original, $expected );

	}

	public function test_verify_source_configuration_return_false() {

		$BC_Admin_Videos_Page = $this->mock_bc_admin_videos_page();

		$current_screen     = new stdClass;
		$current_screen->id = 'not-what-we-want';

		\WP_Mock::wpFunction( 'get_current_screen', array(
			'times'  => 1,
			'return' => $current_screen
		) );

		$this->assertFalse( $BC_Admin_Videos_Page->verify_source_configuration() );

	}

	public function test_verify_source_configuration_with_redirect() {

		global $bc_accounts;

		$BC_Admin_Videos_Page = $this->mock_bc_admin_videos_page();

		$bc_accounts = $this->getMockBuilder( 'BC_Accounts' )
		                    ->setMethods( array( 'get_account_details_for_user' ) )
		                    ->disableOriginalConstructor()
		                    ->getMock();

		$bc_accounts->expects( $this->once() )
		            ->method( 'get_account_details_for_user' )
		            ->willReturn( false );

		$BC_Admin_Videos_Page = $this->mock_bc_admin_videos_page();

		$current_screen     = new stdClass;
		$current_screen->id = 'brightcove_page_page-brightcove-videos';

		\WP_Mock::wpFunction( 'get_current_screen', array(
			'times'  => 1,
			'return' => $current_screen
		) );

		\WP_Mock::wpFunction( 'wp_safe_redirect', array(
			'times'  => 1,
			'return' => false
		) );

		$BC_Admin_Videos_Page->verify_source_configuration();

		$this->assertConditionsMet();

	}

	public function test_verify_source_configuration() {

		global $bc_accounts;

		$BC_Admin_Videos_Page = $this->mock_bc_admin_videos_page();

		$bc_accounts = $this->getMockBuilder( 'BC_Accounts' )
		                    ->setMethods( array( 'get_account_details_for_user' ) )
		                    ->disableOriginalConstructor()
		                    ->getMock();

		$bc_accounts->expects( $this->once() )
		            ->method( 'get_account_details_for_user' )
		            ->willReturn( true );

		$BC_Admin_Videos_Page = $this->mock_bc_admin_videos_page();

		$current_screen     = new stdClass;
		$current_screen->id = 'brightcove_page_page-brightcove-videos';

		\WP_Mock::wpFunction( 'get_current_screen', array(
			'times'  => 1,
			'return' => $current_screen
		) );

		$BC_Admin_Videos_Page->verify_source_configuration();

		$this->assertConditionsMet();

	}

}
