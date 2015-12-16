<?php
namespace Step\Acceptance;

use \Page\wp_login as wp_login;

class Admin extends \AcceptanceTester
{

	
    public function admin_login()
    {
		$I = $this;
		
		$wp_login = new wp_login( $I );
		$wp_login->admin_login();
		$this->after_login();
		
    }

    public function author_login(\Page\Login $wp_login)
    { 
		$wp_login->author_login();
		$this-after_login();
    }
	
    public function editor_login(\Page\Login $wp_login)
    {
        $wp_login->editor_login();
		$this->after_login();
    }	

    public function contributor_login(\Page\Login $wp_login)
    {
        $wp_login->contributor_login();
		$this->after_login();
    }
	
    public function subscriber_login(\Page\Login $wp_login)
    {
        $wp_login::subscriber_login();
		$this->after_login();
    }
	
    public function after_login()
    {
		$I = $this;
		$I->see('Dashboard', 'h1');
    }

}