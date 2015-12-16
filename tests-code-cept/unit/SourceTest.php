<?php

class SourceTest extends \PHPUnit_Framework_TestCase
{
	
	use \Codeception\Specify;
	
    protected function setUp()
    {
    }

    protected function tearDown()
    {
    }

    public function testValidation()
    {
        	
		$bc_accounts = new BC_Accounts();
		
		$account_name        =  'WP1';
		$account_id          =  '4031511818001';
		$client_id           =  '4e7e8541-5741-4599-9879-034cbc62a321';
		$client_secret       =  'ZffV5ddFlZfyawpJ0LmaYKOPEu-xHIRPAewPdZBJ7XBpZLwg7a9Re3-wZ_h-86nPSANSqU8LPbxsJAEx1iWUpg';
	
		$user = User::create();

        $this->specify("Wrong ID", function() {
			
            $account_id = '40315118180XJS';
			$this->assertFalse($this->is_valid_account( $account_id, $client_id, $client_secret, $account_name ));
        });
				
		
    }
	
}
