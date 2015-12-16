<?php

class BC_Callbacks_Tests extends TestCase {

	protected $testFiles = [
		'classes/class-bc-callbacks.php'
	];

	public function mock_bc_callbacks() {

		$BC_Callbacks = $this->getMockBuilder( 'BC_Callbacks' )
		                     ->setMethods( null )
		                     ->getMock();


		return $BC_Callbacks;

	}


	private function test_set_time_limit() {
		$BC_Callbacks = $this->mock_bc_callbacks();

		$time = rand( 250, 350 );

		$reflection_class = new ReflectionClass( "BC_Callbacks" );

		//Then we need to get the method we wish to test and
		//make it accessible
		$method = $reflection_class->getMethod( "set_time_limit" );
		$method->setAccessible( true );
		$method->invoke( $BC_Callbacks, $time );

		$this->assertEquals( ini_get( 'max_execution_time' ), $time );

	}

	public function test_initial_sync() {
		$this->markTestIncomplete(); //instatiated class in method $videos = new BC_Videos();
	}

	public function test_video_notification() {
		$this->markTestIncomplete(); //presence of static methods
	}

	public function test_ingest_callback() {
		$this->markTestIncomplete(); //presence of static methods
	}

	public function trigger_background_fetch() {
		$BC_Videos = $this->getMockBuilder( 'BC_Videos' )
		                  ->setMethods( array( 'sync_videos' ) )
		                  ->getMock();
		$BC_Videos->expects( $this->once() )
		          ->method( 'sync_videos' );

		$BC_Playlists = $this->getMockBuilder( 'BC_Playlists' )
		                     ->setMethods( array( 'sync_playlists' ) )
		                     ->getMock();
		$BC_Playlists->expects( $this->once() )
		             ->method( 'sync_playlists' );

		$this->assertConditionsMet();

	}
}
