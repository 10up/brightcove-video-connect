<?php

class BC_Permissions_Tests extends TestCase {

	protected $testFiles = [
		'classes/class-bc-permissions.php'
	];

	public function test_add_capabilities() {
		global $wp_roles;
		$wp_roles = array();

		$Admin_Role = $this->getMockBuilder( 'WP_Role' )
		                   ->setMethods( array( 'add_cap' ) )
		                   ->getMock();

		$Editor_Role = $this->getMockBuilder( 'WP_Role' )
		                    ->setMethods( array( 'add_cap' ) )
		                    ->getMock();


		$Admin_Role->expects( $this->exactly( 6 ) )
		           ->method( 'add_cap' )
		           ->withConsecutive(
			           array( $this->equalTo( 'brightcove_manipulate_accounts' ) ),
			           array( $this->equalTo( 'brightcove_set_site_default_account' ) ),
			           array( $this->equalTo( 'brightcove_set_user_default_account' ) ),
			           array( $this->equalTo( 'brightcove_get_user_default_account' ) ),
			           array( $this->equalTo( 'brightcove_manipulate_playlists' ) ),
			           array( $this->equalTo( 'brightcove_manipulate_videos' ) )
		           );


		$Editor_Role->expects( $this->exactly( 2 ) )
		            ->method( 'add_cap' )
		            ->withConsecutive(
			            array( $this->equalTo( 'brightcove_manipulate_playlists' ) ),
			            array( $this->equalTo( 'brightcove_manipulate_videos' ) )
		            );


		\WP_Mock::wpFunction( 'get_role', array(
			'args'   => array( 'administrator' ),
			'times'  => 1,
			'return' => $Admin_Role
		) );

		\WP_Mock::wpFunction( 'get_role', array(
			'args'   => array( 'editor' ),
			'times'  => 1,
			'return' => $Editor_Role
		) );

		//act this will also test the private function add_capabilities
		$BC_Permissions = $this->getMockBuilder( 'BC_Permissions' )
		                       ->setMethods( null )
		                       ->getMock();

		$this->assertConditionsMet();
	}

	public function test_add_capabilities_vip() {
		$this->markTestIncomplete(); //runs function that is not part of the class or WP

	}

}
