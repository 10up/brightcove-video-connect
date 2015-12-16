<?php

class BC_SetUpTest extends WP_UnitTestCase {
	
	public static $account_name        =  'WP1';
	public static $account_id          =  '4229317772001';
	public static $client_id           =  '924385b2-6978-4b95-acc0-5b261d20e18b';
	public static $client_secret       =  't9SpWEl3l6BDXsYWg7FZODkajGHoyxmxrLnhunom6aB2u907dWPaVK7xj5_oWODP0zVMifZqXNheFoYXXhMLKQ';
	public static $set_default         =  'default';
			
	
    function test_add_account(){
        	
		global $bc_accounts;

        $invAccount_id = 'xxx';
	    $this->assertFalse($bc_accounts->add_account( $invAccount_id, self::$client_id, self::$client_secret, self::$account_name ));
		
        $invClient_id = 'xxx';
		$this->assertFalse($bc_accounts->add_account( self::$account_id, $invClient_id, self::$client_secret, self::$account_name ));
       
        $invClient_secret = 'xxx';
		$this->assertFalse($bc_accounts->add_account( self::$account_id, self::$client_id, $invClient_secret, self::$account_name ));
		
		$this->assertTrue($bc_accounts->add_account( self::$account_id, self::$client_id, self::$client_secret, self::$account_name ));
	
    }
	

	
	
}

