<?php

class BC_Players {

	protected $cms_api;
	protected $players_api;

	public function __construct() {

		$this->cms_api     = new BC_CMS_API();
		$this->players_api = new BC_Player_Management_API();

	}
}
