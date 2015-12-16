<?php

class BC_Notifications_Tests extends TestCase {


	protected $testFiles = [

		'classes/class-bc-utility.php',
		'classes/class-bc-notifications.php',
		'classes/api/class-bc-api.php',
		'classes/api/class-bc-cms-api.php'
	];

	protected $test_account = array(
		'account_id'    => '4229317772001',
		'account_name'  => 'WP6',
		'client_id'     => '924385b2-6978-4b95-acc0-5b261d20e18b',
		'client_secret' => 't9SpWEl3l6BDXsYWg7FZODkajGHoyxmxrLnhunom6aB2u907dWPaVK7xj5_oWODP0zVMifZqXNheFoYXXhMLKQ',
		'set_default'   => 'default',
		'hash'          => 'e894aba0421d8ee3'
	);


	public function mock_bc_notifications() {

		$BC_Notifications = $this->getMockBuilder( 'BC_Notifications' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( array( 'subscribe_if_not_subscribed', 'get_option_key_for' ) )
		                         ->getMock();

		$BC_Notifications->method( 'get_option_key_for' )
		                 ->will( $this->returnCallback( array( $this, 'get_option_key_for' ) ) );

		return $BC_Notifications;

	}

	public function get_option_key_for( $value ) {
		return '_notifications_subscribed_' . $value;
	}

	public function test_get_option_key_for() {
		$this->markTestIncomplete(); //static method in test
	}

	public function test_is_subscribed() {
		$BC_Notifications = $this->mock_bc_notifications();

		\WP_Mock::wpFunction( 'get_option', array(
			'args'   => array( '_notifications_subscribed_' . $this->test_account['account_id'] ),
			'times'  => 1,
			'return' => array( 'aliens', 'animals', 'animation' )
		) );

		$this->assertEquals( true, $BC_Notifications->is_subscribed( $this->test_account['account_id'] ) );

	}

	public function test_remove_subscription() {
		$this->markTestIncomplete(); //instatiated used property in __construct with new class thats used in this method $this->cms_api = new BC_CMS_API();
	}

	public function test_subscribe_if_not_subscribed() {
		$this->markTestIncomplete(); //same as above
	}

}