<?php
namespace Page;

class bcPages
{
    // include url of current page
    public static $URL = 'admin.php';

	public static $sourceName         =  'WP1';
	public static $accountId          =  '4031511818001';
	public static $clientId           =  '4e7e8541-5741-4599-9879-034cbc62a321';
	public static $clientSecret       =  'ZffV5ddFlZfyawpJ0LmaYKOPEu-xHIRPAewPdZBJ7XBpZLwg7a9Re3-wZ_h-86nPSANSqU8LPbxsJAEx1iWUpg';
	
	public static $sourceNameField    =  '#source-name';
	public static $accountIdField     =  '#source-account-id';
	public static $clientIdField      =  '#source-client-id';
	public static $clientSecretField  =  '#source-client-secret';
	public static $defaultSourceField =  'input[name=source-default-account]';
	public static $loginButton        =  '#brightcove-edit-account-submit';
	public static $deleteSourceLink   =  ".delete a";	
	
    /**
     * @var AcceptanceTester
     */
    protected $tester;

    public function __construct(\AcceptanceTester $I)
    {
        $this->tester = $I;
    }

    /**
     * Basic route example for your current URL
     * You can append any additional parameter to URL
     * and use it in tests like: Page\Edit::route('/123-post');
     */
    public static function route($param)
    {
        return static::$URL.$param;
    }
	
	public function  addSource(){
		
        $I = $this->tester;
		
		$I->see('Add Source','h2');

        $I->fillField(self::$sourceNameField, self::$sourceName);
        $I->fillField(self::$accountIdField, self::$accountId);
        $I->fillField(self::$clientIdField, self::$clientId);
        $I->fillField(self::$clientSecretField, self::$clientSecret);
        $I->checkOption(self::$defaultSourceField);

		
        $I->click(self::$loginButton);

        return $this;		
	}
	
	public function removeSource(){
		$I = $this->tester;
		$I->click(self::$deleteSourceLink);
		
		return $this;
	}	


}
