<?php

class BC_Accounts_Tests extends TestCase {


	protected $testFiles = [
		'classes/class-bc-accounts.php'
	];

	protected $test_account = array(
		'account_id'    => '4229317772001',
		'account_name'  => 'WP6',
		'client_id'     => '924385b2-6978-4b95-acc0-5b261d20e18b',
		'client_secret' => 't9SpWEl3l6BDXsYWg7FZODkajGHoyxmxrLnhunom6aB2u907dWPaVK7xj5_oWODP0zVMifZqXNheFoYXXhMLKQ',
		'set_default'   => 'default',
		'hash'          => 'e894aba0421d8ee3'
	);


	public function mock_bc_account() {

		$bc_accounts = $this->getMockBuilder( 'BC_Accounts' )
		                    ->setMethods( array( '__construct', 'get_account_by_hash' ) )
		                    ->disableOriginalConstructor()
		                    ->getMock();

		$bc_accounts->method( 'get_account_by_hash' )
		            ->willReturn( $this->test_account );

		$bc_accounts->set_current_account( $this->test_account['hash'] );

		return $bc_accounts;

	}

	public function test_get_account_id_return_false() {

		$bc_accounts = $this->getMockBuilder( 'BC_Accounts' )
		                    ->getMock();

		$this->assertEquals( $bc_accounts->get_account_id(), false );
	}

	public function test_get_account_id_return_account_id() {

		$bc_accounts = $this->mock_bc_account();
		$this->assertEquals( $bc_accounts->get_account_id(), $this->test_account['account_id'] );
	}

	public function test_get_client_id_return_client_id() {
		$bc_accounts = $this->mock_bc_account();
		$this->assertEquals( $bc_accounts->get_client_id(), $this->test_account['client_id'] );
	}

	public function test_get_client_id_return_false() {

		$bc_accounts = $this->getMockBuilder( 'BC_Accounts' )
		                    ->getMock();

		$this->assertEquals( $bc_accounts->get_client_id(), false );
	}

	public function test_get_client_secret_return_client_secret() {

		$bc_accounts = $this->mock_bc_account();

		$this->assertEquals( $bc_accounts->get_client_secret(), $this->test_account['client_secret'] );
	}

	public function test_get_client_secret_return_false() {

		$bc_accounts = $this->getMockBuilder( 'BC_Accounts' )
		                    ->getMock();

		$this->assertEquals( $bc_accounts->get_client_secret(), false );
	}


	public function test_get_account_name_return_account_name() {
		$bc_accounts = $this->mock_bc_account();
		$this->assertEquals( $bc_accounts->get_account_name(), $this->test_account['account_name'] );
	}

	public function test_get_account_name_return_false() {

		$bc_accounts = $this->getMockBuilder( 'BC_Accounts' )
		                    ->getMock();

		$this->assertEquals( $bc_accounts->get_account_name(), false );
	}

	public function test_get_account_hash_return_account_hash() {
		$bc_accounts = $this->mock_bc_account();
		$this->assertEquals( $bc_accounts->get_account_hash(), $this->test_account['hash'] );
	}

	public function test_get_account_hash_return_false() {

		$bc_accounts = $this->getMockBuilder( 'BC_Accounts' )
		                    ->getMock();

		$this->assertEquals( $bc_accounts->get_account_hash(), false );
	}

	public function test_is_initial_sync_complete_return_1() {

		$bc_accounts = $this->mock_bc_account();

		\WP_Mock::wpFunction( 'get_option', array(
			'args'   => array( '_brightcove_sync_complete', array() ),
			'times'  => 1,
			'return' => array( $this->test_account['account_id'] => true )
		) );

		$this->assertEquals( true, $bc_accounts->is_initial_sync_complete( $this->test_account['account_id'] ) );

	}

	public function test_is_initial_sync_complete_return_false() {

		$bc_accounts = $this->mock_bc_account();

		\WP_Mock::wpFunction( 'get_option', array(
			'args'   => array( '_brightcove_sync_complete', array() ),
			'times'  => 1,
			'return' => array()
		) );

		$this->assertEquals( false, $bc_accounts->is_initial_sync_complete( $this->test_account['account_id'] ) );

	}

	public function test_get_sync_type_return_full() {

		require_once( TEST_PATH . 'includes/classes/class-bc-utility.php' );

		$bc_accounts = $this->mock_bc_account();

		\WP_Mock::wpFunction( 'get_option', array(
			'args'   => array( '_brightcove_sync_type_' . $this->test_account['account_id'], 'full' ),
			'times'  => 1,
			'return' => 'full'
		) );

		\WP_Mock::wpPassthruFunction( 'sanitize_text_field' );

		$this->assertEquals( 'full', $bc_accounts->get_sync_type( $this->test_account['account_id'] ) );

	}

	public function test_set_sync_type_full_return_true() {

		$bc_accounts = $this->mock_bc_account();

		\WP_Mock::wpFunction( 'update_option', array(
			'args'   => array( '_brightcove_sync_type_', 'full' ),
			'times'  => 1,
			'return' => true
		) );

		$this->assertEquals( true, $bc_accounts->set_sync_type( $this->test_account['account_id'], 'full' ) );

	}

	public function test_set_sync_type_300hrs_return_false() {

		$bc_accounts = $this->mock_bc_account();

		$this->assertEquals( false, $bc_accounts->set_sync_type( $this->test_account['account_id'], '', 300 ) );

	}

	public function test_set_sync_type_negative_hrs_return_false() {

		$bc_accounts = $this->mock_bc_account();
		$this->assertEquals( false, $bc_accounts->set_sync_type( $this->test_account['account_id'], '', - 1 ) );

	}

	public function test_set_initial_sync_status_return_true() {

		$bc_accounts = $this->mock_bc_account();

		\WP_Mock::wpFunction( 'get_option', array(
			'args'   => array( '_brightcove_sync_complete', array() ),
			'times'  => 1,
			'return' => array()
		) );

		\WP_Mock::wpFunction( 'update_option', array(
			'args'   => array( '_brightcove_sync_complete', array( $this->test_account['account_id'] => true ) ),
			'times'  => 1,
			'return' => true
		) );

		$this->assertEquals( true, $bc_accounts->set_initial_sync_status( $this->test_account['account_id'], true ) );

	}


	public function test_get_initial_sync_status_return_false() {

		$bc_accounts = $this->mock_bc_account();

		\WP_Mock::wpFunction( 'get_option', array(
			'args'   => array( '_brightcove_sync_complete', array() ),
			'times'  => 1,
			'return' => array( $this->test_account['account_id'] => true ),
		) );


		$this->assertEquals( false, $bc_accounts->get_initial_sync_status() );
	}

	public function test_get_initial_sync_status_return_true() {

		$bc_accounts = $this->mock_bc_account();

		\WP_Mock::wpFunction( 'get_option', array(
			'args'   => array( '_brightcove_sync_complete', array() ),
			'times'  => 1,
			'return' => array( $this->test_account['account_id'] => false ),
		) );


		$this->assertEquals( array( $this->test_account['account_id'] ), $bc_accounts->get_initial_sync_status() );
	}

	public function test_add_account_return_false() {

		\WP_Mock::wpFunction( 'get_transient', array(
			'args'   => 'brightcove_cli_account_creation',
			'times'  => 1,
			'return' => '',
		) );

		$bc_accounts = $this->getMockBuilder( 'BC_Accounts' )
		                    ->setMethods( array( '__construct', 'get_account_by_hash', 'is_valid_account' ) )
		                    ->disableOriginalConstructor()
		                    ->getMock();

		$bc_accounts->method( 'get_account_by_hash' )
		            ->willReturn( $this->test_account );

		$bc_accounts->set_current_account( $this->test_account['hash'] );

		$bc_accounts->method( 'is_valid_account' )
		            ->willReturn( array() );

		$account_id    = $this->test_account['account_id'];
		$client_id     = $this->test_account['client_id'];
		$client_secret = $this->test_account['client_secret'];
		$account_name  = $this->test_account['account_name'];
		$set_default   = $this->test_account['set_default'];


		$this->assertEquals( false, $bc_accounts->add_account( $account_id, $client_id, $client_secret, $account_name, $set_default, true ) );

	}

	public function add_account_return_wp_error() {

		\WP_Mock::wpFunction( 'get_transient', array(
			'args'   => 'brightcove_cli_account_creation',
			'times'  => 1,
			'return' => '',
		) );

		$return = new WP_Error( 'brightcove-invalid-account', 'Account credentials are invalid ' );


		$bc_accounts = $this->getMockBuilder( 'BC_Accounts' )
		                    ->setMethods( array( 'is_valid_account' ) )
		                    ->getMock();

		$bc_accounts->method( 'is_valid_account' )
		            ->willReturn( $return );

		$account_id    = $this->test_account['account_id'];
		$client_id     = $this->test_account['client_id'];
		$client_secret = $this->test_account['client_secret'];
		$account_name  = $this->test_account['account_name'];
		$set_default   = $this->test_account['set_default'];

		$this->assertEquals( false, $bc_accounts->add_account( $account_id, $client_id, $client_secret, $account_name, $set_default, true ) );

	}

	public function add_account_return_true( $account_id, $client_id, $client_secret, $account_name = 'New Account', $set_default = '', $allow_update = true ) {


		\WP_Mock::wpFunction( 'get_transient', array(
			'args'   => 'brightcove_cli_account_creation',
			'times'  => 1,
			'return' => '',
		) );

		$bc_accounts = $this->getMockBuilder( 'BC_Accounts' )
		                    ->setMethods( array( 'is_valid_account', 'get_account_details_for_site' ) )
		                    ->getMock();

		$bc_accounts->method( 'is_valid_account' )
		            ->willReturn( true );

		$bc_accounts->method( 'get_account_details_for_site' )
		            ->willReturn( $this->test_account['account_id'] );

		$bc_accounts->method( 'is_initial_sync_complete' )
		            ->willReturn( true );

		$bc_accounts->method( 'set_current_account' )
		            ->willReturn( true );

		$bc_accounts->method( 'get_all_accounts' )
		            ->willReturn( array( $this->test_account['hash'] => $this->test_account ) );

		$bc_accounts->method( 'trigger_sync_via_callback' )
		            ->willReturn( null );

		$bc_accounts->method( 'restore_default_account' )
		            ->willReturn( null );

		$this->getMockBuilder( 'BC_Notifications' )
		     ->setMethods( null )
		     ->getMock();


		$this->assertEquals( true, $bc_accounts->add_account( $account_id, $client_id, $client_secret, $account_name, $set_default, true ) );
	}

}

