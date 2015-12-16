<?php

/**
 * Class BC_Admin_Sources Test
 */
class BC_Admin_Sources_Test extends TestCase {

	protected $testFiles = [
		'classes/admin/class-bc-admin-sources.php'
	];

	public $notices;

	protected $test_account = array(
		'account_id'    => '4229317772001',
		'account_name'  => 'WP6',
		'client_id'     => '924385b2-6978-4b95-acc0-5b261d20e18b',
		'client_secret' => 't9SpWEl3l6BDXsYWg7FZODkajGHoyxmxrLnhunom6aB2u907dWPaVK7xj5_oWODP0zVMifZqXNheFoYXXhMLKQ',
		'set_default'   => 'default',
		'hash'          => 'e894aba0421d8ee3'
	);

	public function mock_bc_admin_sources() {

		$BC_Admin_Sources = $this->getMockBuilder( 'BC_Admin_Sources' )
		                         ->setMethods( null )
		                         ->getMock();


		return $BC_Admin_Sources;

	}

	public function test__construct() {
		$this->markTestIncomplete(); //filter and action hooks in constructor
	}

	public function test_render_return_wp_error() {
		$this->markTestIncomplete(); //filter and action hooks in constructor
	}

	public function test_render() {
		global $bc_accounts;
		$_GET['account'] = $this->test_account['hash'];

		$bc_accounts = $this->getMockBuilder( 'BC_Accounts' )
		                    ->setMethods( array( 'get_account_by_hash' ) )
		                    ->disableOriginalConstructor()
		                    ->getMock();

		$bc_accounts->method( 'get_account_by_hash' )
		            ->willReturn( $this->test_account );

		\WP_Mock::wpPassthruFunction( 'sanitize_text_field', array( 'times' => 1 ) );

		$BC_Admin_Sources = $this->getMockBuilder( 'BC_Admin_Sources' )
		                         ->setMethods( array( 'render_edit_html' ) )
		                         ->getMock();

		$BC_Admin_Sources->expects( $this->once() )
		                 ->method( 'render_edit_html' )
		                 ->will( $this->returnCallback( array( $this, 'print_output' ) ) );

		$this->expectOutputString( 'output printed' );

		$BC_Admin_Sources->render();


		$this->assertConditionsMet();

	}


	public function test_render_return_void_output_render_add_html() {
		global $bc_accounts;

		$BC_Admin_Sources = $this->getMockBuilder( 'BC_Admin_Sources' )
		                         ->setMethods( array( 'render_add_html' ) )
		                         ->getMock();

		$BC_Admin_Sources->expects( $this->once() )
		                 ->method( 'render_add_html' )
		                 ->will( $this->returnCallback( array( $this, 'print_output' ) ) );

		$this->expectOutputString( 'output printed' );

		$BC_Admin_Sources->render();


		$this->assertConditionsMet();
	}

	public function print_output() {
		echo 'output printed';
	}


	public function test_save_account_return_false_no_brightcove_oauth() {
		$BC_Admin_Sources = $this->mock_bc_admin_sources();

		$this->assertFalse( $BC_Admin_Sources->save_account() );

	}

	public function test_save_account_return_wp_error() {

		$_POST['brightcove-check_oauth'] = 1;

		$BC_Admin_Sources = $this->mock_bc_admin_sources();

		$WP_Error = $this->getMockBuilder( 'WP_Error' )
		                 ->getMock();

		\WP_Mock::wpFunction( 'current_user_can', array(
			'args'   => array( 'brightcove_manipulate_accounts' ),
			'times'  => 1,
			'return' => false
		) );

		$this->assertInstanceOf( 'WP_Error', $BC_Admin_Sources->save_account() );
	}

	public function test_save_account_return_false_invalid_nonce() {

		$_POST['brightcove-check_oauth'] = 1;

		$BC_Admin_Sources = $this->mock_bc_admin_sources();

		$WP_Error = $this->getMockBuilder( 'WP_Error' )
		                 ->getMock();

		\WP_Mock::wpFunction( 'current_user_can', array(
			'args'   => array( 'brightcove_manipulate_accounts' ),
			'times'  => 1,
			'return' => true
		) );

		\WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'args'   => array( $_POST['brightcove-check_oauth'], '_brightcove_check_oauth_for_source' ),
			'times'  => 1,
			'return' => false
		) );

		$this->assertFalse( $BC_Admin_Sources->save_account() );

	}

	public function test_save_account_return_false_invalid_post_key() {

		$_POST['brightcove-check_oauth'] = 1;

		$BC_Admin_Sources = $this->mock_bc_admin_sources();

		$WP_Error = $this->getMockBuilder( 'WP_Error' )
		                 ->getMock();

		\WP_Mock::wpFunction( 'current_user_can', array(
			'args'   => array( 'brightcove_manipulate_accounts' ),
			'times'  => 1,
			'return' => true
		) );

		\WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'args'   => array( $_POST['brightcove-check_oauth'], '_brightcove_check_oauth_for_source' ),
			'times'  => 1,
			'return' => true
		) );

		$_POST['source-action'] = 'create';

		$this->assertFalse( $BC_Admin_Sources->save_account() );

	}

	public function test_save_account() {
		$this->markTestIncomplete();  // the test stops at where static methods begin being used		
	}

	public function test_admin_notice_handler_return_false() {
		$BC_Admin_Sources = $this->mock_bc_admin_sources();
		$this->assertFalse( $BC_Admin_Sources->admin_notice_handler() );
	}

	public function test_admin_notice_handler() {
		$this->markTestIncomplete(); // static method present
	}

	public function test_render_add_html() {

		$BC_Admin_Sources = $this->mock_bc_admin_sources();

		\WP_Mock::wpFunction( 'esc_html_e', array(
			'times'  => 14,
			'return' => '',
		) );

		\WP_Mock::wpFunction( 'esc_html__', array(
			'times'  => 2,
			'return' => '',
		) );

		\WP_Mock::wpFunction( 'plugins_url', array(
			'times'  => 1,
			'return' => 'http://test.com/plugin-url',
		) );

		\WP_Mock::wpFunction( 'wp_nonce_field', array(
			'args'   => array( '_brightcove_check_oauth_for_source', 'brightcove-check_oauth', false, true ),
			'times'  => 1,
			'return' => '',
		) );


		$original = $this->render_add_html_print_out();
		$original = preg_replace( '/\s+/', '', $original );

		ob_start();
		$BC_Admin_Sources->render_add_html();
		$expected = ob_get_contents();
		ob_end_clean();
		$expected = preg_replace( '/\s+/', '', $expected );

		$this->assertEquals( $original, $expected );

	}

	public function render_add_html_print_out() {
		ob_start();
		?>

		<div class="wrap">
			<h2><?php
				printf( '<img src="%s" class="bc-page-icon"/>', 'http://test.com/plugin-url' );
				?> </h2>

			<form action="" method="post">
				<table class="form-table brightcove-add-source-name">
					<tbody>
					<tr class="brightcove-account-row">
						<th scope="row"></th>
						<td>
							<input type="text" name="source-name" id="source-name"
							       placeholder=""
							       class="regular-text" required="required"/>

							<p class="description"></p>
						</td>
					</tr>
					</tbody>
				</table>

				<h3></h3>

				<p class="description">
					<br>
					<?php echo sprintf( '%s <a href="https://studio.brightcove.com/products/videocloud/admin/oauthsettings">%s</a>.',
						'',
						''
					);
					?>
				</p>
				<table class="form-table brightcove-add-source-details">
					<tbody>
					<tr class="brightcove-account-row">
						<th scope="row"></th>
						<td>
							<input type="text" name="source-account-id" id="source-account-id" class="regular-text"
							       required="required"/>
						</td>
					</tr>
					<tr class="brightcove-account-row">
						<th scope="row"></th>
						<td>
							<input type="password" name="source-client-id" id="source-client-id" class="regular-text"
							       required="required">

							<p class="description"></p>
						</td>
					</tr>
					<tr class="brightcove-account-row">
						<th scope="row"></th>
						<td>
							<input type="password" name="source-client-secret" id="source-client-secret"
							       class="regular-text" required="required">

							<p class="description"></p>
						</td>
					</tr>

					<tr class="brightcove-account-row">
						<th scope="row"></th>
						<td>
							<input type="checkbox"
							       name="source-default-account">&nbsp;

						</td>
					</tr>
					</tbody>
				</table>


				<p class="submit">
					<input type="hidden" name="source-action" value="create"/>
					<input type="submit" name="brightcove-edit-account-submit" id="brightcove-edit-account-submit"
					       class="button button-primary" value="">
				</p>
			</form>
		</div>
		<?php
		$string = ob_get_contents();
		ob_end_clean();

		return $string;
	}

	public function test_render_edit_html() {
		$this->markTestIncomplete();
	}


}
