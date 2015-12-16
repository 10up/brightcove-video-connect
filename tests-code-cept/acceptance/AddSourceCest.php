<?php
//use \AcceptanceTester;

class AddSourceCest
{
    public function _before(AcceptanceTester $I)
    {
		
    }

    public function _after(AcceptanceTester $I)
    {
    }

    // tests
    public function addSource(\Step\Acceptance\Admin $I , \Page\bcPages $BC)
    {
		$I->admin_login();
		
		$I->wantTo('Add a source');		
		$I->amOnPage($BC::route('?page=page-brightcove-edit-source'));
		$BC->addSource();	

		$I->see('Congratulations! Your credentials have been authenticated.', '.brightcove-settings-updated');
    }
	
    // tests
    public function deleteSource(\Step\Acceptance\Admin $I , \Page\bcPages $BC)
    {
				
		$I->wantTo('Delete Source');		
		$I->amOnPage($BC::route('?page=page-brightcove-edit-source'));
		$BC->removeSource();	

		$I->see('Source Deleted.', '.brightcove-settings-updated');
    }	
}
