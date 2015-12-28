<?php

class BC_Tags {

	private $key = '_brightcove_tags';

	public function __construct() {
	}

	public function get_tags() {
		return get_option( $this->key, array() );
	}

	public function add_tags( $new_tags ) {
		$existing_tags = $this->get_tags();
		$merged_tags = array_unique( array_merge( $existing_tags, $new_tags ) );
		if ( count($merged_tags) > count($existing_tags) ) {
			sort( $merged_tags );
			update_option( $this->key, $merged_tags );
		}
	}
}
