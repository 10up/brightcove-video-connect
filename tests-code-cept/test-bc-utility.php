<?php

class TestBC_Utility extends WP_UnitTestCase {
	


	public function test_sanitize_and_generate_meta_video_id() {
		$video_id = '2790007957001';				
		$this->assertEquals("ID_" . BC_Utility::sanitize_id( $video_id), $video_id );		
	}


    public function test_get_sanitized_client_secret() {
		$client_secret       =  't9SpWEl3l6BDXsYWg7FZODkajGHoyxmxrLnhunom6aB2u907dWPaVK7xj5_oWODP0zVMifZqXNheFoYXXhMLKQ';
        $this->assertEquals($client_secret, BC_Utility::get_sanitized_client_secret('t9SpWEl3l6BDXsYWg7FZODkajGHoyxmx rLnhunom6aB2u907dWPaVK7xj5-_oWODP0zVMifZqXNheFo_YXXhMLKQ'));
    }


	public function test_sanitize_id() {
		$video_id = '2790007aud957aYS001';
		$this->assertEquals(BC_Utility::sanitize_id( $video_id), '2790007957001');
	}

	public function test_sanitize_subscription_id() {
		$subscription_id = '898e,b2f6-556c-43b9-a34a-757f88eb00za3';
		$this->assertEquals(BC_Utility::sanitize_subscription_id( $subscription_id ), '898eb2f6-556c-43b9-a34a-757f88eb00a3');		
	}

	
}

