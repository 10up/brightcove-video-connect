<?php

class test_BC_Admin_Menu extends TestCase {

	protected $testFiles = [
		'classes/admin/class-bc-admin-menu.php',
		'classes/class-bc-accounts.php'
	];

	public function mock_bc_admin_menu() {

		$BC_Admin_Menu = $this->getMockBuilder( 'BC_Admin_Menu' )
		                      ->setMethods( null )
		                      ->getMock();


		return $BC_Admin_Menu;

	}

	public function btest__construct() {
		$BC_Admin_Menu = $this->mock_bc_admin_menu();

		\WP_Mock::expectActionAdded( 'admin_menu', array( 'BC_Admin_Menu', 'register_admin_menu' ) );
		\WP_Mock::expectActionAdded( 'admin_head', array( 'BC_Admin_Menu', 'redirect_top_page_menu_js' ) );

		$this->mock_bc_admin_menu();

	}


	public function test_register_admin_menu() {
		$this->markTestIncomplete(); //presence of static methods
	}

	public static function get_playlists_page_uri_component() {
		return 'page-brightcove-playlists';
	}

	public static function get_videos_page_uri_component() {
		return 'page-brightcove-videos';
	}

	/**
	 * Provides hook for Settings panel to hook into
	 */
	public function render_settings_page() {
		do_action( 'brightcove/admin/settings_page' );
	}

	public function render_videos_page() {
		do_action( 'brightcove/admin/videos_page' );
	}

	public function render_playlists_page() {
		do_action( 'brightcove/admin/playlists_page' );
	}

	/**
	 * Provides hook for Add/Edit source panel to hook into
	 */
	public function render_edit_source_page() {
		do_action( 'brightcove/admin/edit_source_page' );
	}

	public function test_redirect_top_page_menu_js() {

		global $bc_accounts;

		$bc_accounts = $this->getMockBuilder( 'BC_Accounts' )
		                    ->setMethods( array( 'get_account_details_for_user' ) )
		                    ->disableOriginalConstructor()
		                    ->getMock();

		$bc_accounts->method( 'get_account_details_for_user' )
		            ->willReturn( false );


		\WP_Mock::wpFunction( 'get_current_user_id', array(
			'args'   => array(),
			'times'  => 1,
			'return' => 1
		) );

		\WP_Mock::wpFunction( 'admin_url', array(
			'args'   => array( 'admin.php?page=brightcove-sources' ),
			'times'  => 1,
			'return' => 'http://pseudo_url.domain'
		) );

		$OriginalOutput =
			"<script>
				jQuery(document).ready(function($){
					$('#toplevel_page_brightcove a').attr( 'href', 'http://pseudo_url.domain' );
				});
			</script>";

		$BC_Admin_Menu = $this->mock_bc_admin_menu();

		$expectedOutput = $BC_Admin_Menu->redirect_top_page_menu_js();

		$this->expectOutputString( $OriginalOutput, $expectedOutput );

	}

	public function test_redirect_top_page_menu_js_do_nothing() {

		global $bc_accounts;

		$test_account  = array(
			'account_id'    => '4229317772001',
			'account_name'  => 'WP6',
			'client_id'     => '924385b2-6978-4b95-acc0-5b261d20e18b',
			'client_secret' => 't9SpWEl3l6BDXsYWg7FZODkajGHoyxmxrLnhunom6aB2u907dWPaVK7xj5_oWODP0zVMifZqXNheFoYXXhMLKQ',
			'set_default'   => 'default',
			'hash'          => 'e894aba0421d8ee3'
		);
		$BC_Admin_Menu = $this->mock_bc_admin_menu();

		$bc_accounts = $this->getMockBuilder( 'BC_Accounts' )
		                    ->setMethods( array( 'get_account_details_for_user' ) )
		                    ->disableOriginalConstructor()
		                    ->getMock();

		$bc_accounts->method( 'get_account_details_for_user' )
		            ->willReturn( $test_account );

		// Verify
		$this->assertConditionsMet();


	}
}
