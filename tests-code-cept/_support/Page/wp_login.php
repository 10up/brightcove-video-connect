<?php
namespace Page;

class WP_Login
{
    // include url of current page
    public static $URL = 'brightcove/wp-login.php';
	public static $usernameField    =  '#loginform #user_login';
	public static $passwordField    =  '#loginform input[name=pwd]';
	public static $loginButton      =  '#loginform input[type=submit]';
	public static $admin_user       =  'admin';
	public static $admin_pass       =  'password';
	public static $editor_user      =  'editor';
	public static $editor_pass      =  'password';
	public static $author_user      =  'author';
	public static $author_pass      =  'password';
	public static $contributor_user =  'subscriber';
	public static $contributor_pass =  'password';	
	public static $subscriber_user  =  'subscriber';
	public static $subscriber_pass  =  'password';
	
    /**
     * @var AcceptanceTester
     */
    protected $tester;

    public function __construct(\Step\Acceptance\Admin $I)
    {
        $this->tester = $I;
    }

    /**
     * Basic route example for your current URL
     * You can append any additional parameter to URL
     * and use it in tests like: Page\Edit::route('/123-post');
     */
    public function route($param)
    {
        return static::$URL.$param;
    }
	
	public function admin_login(){
		$this->login( self::$admin_user, self::$admin_pass );
	}
	
	public function editor_login(){
		$this->login( self::$editor_user, self::$editor_pass );
	}	
	
	public function author_login(){
		$this->login( self::$author_user, self::$author_pass );
	}
	
	public function contributor_login(){
		$this->login( self::$contributor_user, self::$contributor_pass );
	}
	
	public function subscriber_login(){
		$this->login( self::$subscriber_user, self::$subscriber_pass );
	}	
	
    public function login( $user, $pass )
    {
        $I = $this->tester;
		
		$I->amOnPage(self::$URL);
		$I->fillField(self::$usernameField, $user);
		$I->fillField(self::$passwordField, $pass);
		$I->click(self::$loginButton);
		
		return $this;
    }	


}
