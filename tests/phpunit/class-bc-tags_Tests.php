<?php

class BC_Tags_Tests extends TestCase {

	protected $testFiles = [
		'classes/class-bc-tags.php'
	];

	protected $key = '_brightcove_tags';
	protected $BC_Tags;

	public function setUp() {

		require_once( TEST_PATH . 'includes/classes/class-bc-tags.php' );

		$this->BC_Tags = new BC_Tags();
	}

	public function test_get_tags_return_tag_array() {

		$BC_Tags = $this->BC_Tags;

		\WP_Mock::wpFunction( 'get_option', array(
			'args'   => array( $this->key, array() ),
			'times'  => 1,
			'return' => array( 'aliens', 'animals', 'animation' )
		) );

		$this->assertEquals( array( 'aliens', 'animals', 'animation' ), $BC_Tags->get_tags() );
	}

	public function test_add_tags() {

		$BC_Tags = $this->BC_Tags;

		\WP_Mock::wpFunction( 'get_option', array(
			'args'   => array( $this->key, array() ),
			'times'  => 1,
			'return' => array( 'aliens', 'animals', 'animation' )
		) );


		\WP_Mock::wpFunction( 'update_option', array(
			'args'   => array( $this->key, array( 'aliens', 'animals', 'animation', 'brains', 'trains' ) ),
			'times'  => 1,
			'return' => true
		) );

		$BC_Tags->add_tags( array( 'brains', 'trains' ) );

		$this->assertConditionsMet();

	}
}
