<?php

class BC_Admin_User_Profile_Test extends TestCase {

	protected $testFiles = [
		'classes/admin/class-bc-admin-user-profile.php'
	];

	public function test__construct() {
		$this->markTestIncomplete(); //actions in constructor          
	}

	public function test_enqueue_styles() {

		$BC_Admin_User_Profile = $this->getMockBuilder( 'BC_Admin_User_Profile' )
		                              ->setMethods( null )
		                              ->getMock();

		\WP_Mock::wpFunction( 'wp_enqueue_style', array(
			'args'   => array( 'brightcove-video-connect' ),
			'times'  => 1,
			'return' => array()
		) );

		$BC_Admin_User_Profile->enqueue_styles();

		$this->assertConditionsMet();


	}

	public function test_brightcove_profile_ui() {
		$this->markTestIncomplete(); //static methods used
	}

	public function test_update_profile() {
		$this->markTestIncomplete(); //static methods used
	}
}
