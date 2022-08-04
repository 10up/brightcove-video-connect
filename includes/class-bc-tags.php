<?php
/**
 * BC_Tags class file.
 *
 * @package Brightcove_Video_Connect
 */

/**
 * BC_Tags class.
 */
class BC_Tags {

	/**
	 * Key to identify tags
	 *
	 * @var string
	 */
	private $key = '_brightcove_tags';

	/**
	 * Gets tags from option
	 *
	 * @return false|mixed|void
	 */
	public function get_tags() {
		return get_option( $this->key, array() );
	}

	/**
	 * Adds tags to our option
	 *
	 * @param array $new_tags new tags to add
	 */
	public function add_tags( $new_tags ) {
		$existing_tags = $this->get_tags();
		$merged_tags   = array_unique( array_merge( $existing_tags, $new_tags ) );
		if ( count( $merged_tags ) > count( $existing_tags ) ) {
			sort( $merged_tags );
			update_option( $this->key, $merged_tags );
		}
	}
}
