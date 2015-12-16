<?php

class BC_Admin_Settings_Page_Test extends TestCase {

	protected $testFiles = [
		'classes/admin/class-bc-admin-settings-page.php'
	];

	public function mock_bc_admin_settings_page() {

		$BC_Admin_Settings_Page = $this->getMockBuilder( 'BC_Admin_Settings_Page' )
		                               ->setMethods( null )
		                               ->getMock();


		return $BC_Admin_Settings_Page;

	}

	public function test__construct() {
		$this->markTestIncomplete(); //filter and action hooks in constructor
	}

	public function test_delete_source() {
		$this->markTestIncomplete(); //presence of static functions
	}

	public function test_delete_source_return_false_no_nonce() {
		$BC_Admin_Settings_Page = $this->mock_bc_admin_settings_page();
		$this->assertFalse( $BC_Admin_Settings_Page->delete_source() );
	}

	public function delete_source_return_false_invalid_nonce() {
		$_GET['_wpnonce'] = 'somenonce';
		$_GET['account']  = '4229317772001';

		\WP_Mock::wpFunction( 'wp_verify_nonce', array(
			'args'   => array( $_GET['_wpnonce'], 'bc_delete_source_id_' . $_GET['account'] ),
			'times'  => 1,
			'return' => false
		) );

		$this->assertFalse( $BC_Admin_Settings_Page->delete_source() );

	}


	/**
	 * Generates an HTML table with all configured sources
	 */
	public function test_render() {
		$BC_Admin_Settings_Page = $this->mock_bc_admin_settings_page();

		\WP_Mock::wpPassthruFunction( 'esc_url', array( 'times' => 2 ) );


		\WP_Mock::wpFunction( 'esc_html_e', array(
			'times'  => 5,
			'return' => ''// since we can't return an echo we'd rather return an empty string
		) );

		\WP_Mock::wpFunction( 'esc_html__', array(
			'times'  => 1,
			'return' => ''// since we can't return an echo we'd rather return an empty string
		) );


		\WP_Mock::wpFunction( 'admin_url', array(
			'times'  => 1,
			'return' => 'http://domain.com/admin.php?page=page-brightcove-edit-source'
			// since we can't return an echo we'd rather return an empty string
		) );

		$BC_Admin_Settings_Page = $this->getMockBuilder( 'BC_Admin_Settings_Page' )
		                               ->setMethods( array( 'render_source_rows' ) )
		                               ->getMock();

		$BC_Admin_Settings_Page->expects( $this->once() )
		                       ->method( 'render_source_rows' )
		                       ->willReturn( '' );

		if ( ! defined( 'BRIGHTCOVE_URL' ) ) {
			define( 'BRIGHTCOVE_URL', '/' );
		}

		$OriginalOutput =

			'<div class="wrap">

			<h2><img class="bc-page-icon" src="' . BRIGHTCOVE_URL . 'images/admin/menu-icon.svg"> </h2>

			<h3 class="title"></h3>

			<table class="wp-list-table widefat">
				<thead>
				<tr>
					<th></th>
					<th></th>
					<th></th>
				</tr>
				</thead>
				<tbody>
				</tbody>
			</table>

			<p>
				<a href="http://domain.com/admin.php?page=page-brightcove-edit-source" class="button action"></a>
			</p>

		</div>';

		$expectedOutput = $BC_Admin_Settings_Page->render();

		$this->expectOutputString( $OriginalOutput, $expectedOutput );

		$this->assertConditionsMet();

	}

	public function test_action_links() {
		$hash                   = 'hash';
		$BC_Admin_Settings_Page = $this->mock_bc_admin_settings_page();

		\WP_Mock::wpFunction( 'current_user_can', array(
			'args'   => array( 'brightcove_manipulate_accounts' ),
			'times'  => 1,
			'return' => true
		) );

		\WP_Mock::wpFunction( 'esc_html__', array(
			'times'  => 3,
			'return' => ''
		) );

		\WP_Mock::wpFunction( 'admin_url', array(
			'args'   => array( sprintf( 'admin.php?page=page-brightcove-edit-source&account=%s', $hash ) ),
			'times'  => 1,
			'return' => 'http://testdomain.com/wp-admin/parameters'
		) );

		\WP_Mock::wpFunction( 'admin_url', array(
			'args'   => array( sprintf( 'admin.php?page=brightcove-sources&action=delete&account=%1$s&_wpnonce=%2$s', $hash, 'nonce' ) ),
			'times'  => 1,
			'return' => 'http://testdomain.com/wp-admin/parameters'
		) );

		\WP_Mock::wpFunction( 'wp_create_nonce', array(
			'args'   => array( 'bc_delete_source_id_' . $hash ),
			'times'  => 1,
			'return' => 'nonce'
		) );


		\WP_Mock::wpPassthruFunction( 'esc_attr', array( 'times' => 2 ) );


		$OriginalOutput =
			'<div class="row-actions"><span class="edit"><a href="http://testdomain.com/wp-admin/parameters" class="brightcove-action-links brightcove-action-edit-source" title=""></a></span> | <span class="delete"><a href="" class="brightcove-action-links brightcove-action-delete-source" title="" data-alert-message=""></a></span>';
		$OriginalOutput .= '</div>';

		$expectedOutput = $BC_Admin_Settings_Page->action_links( $hash );

		$this->assertSame( $OriginalOutput, $expectedOutput );

		$this->assertConditionsMet();

	}


	public function test_render_source_rows_return_no_sources() {

		global $bc_accounts;

		$bc_accounts = $this->getMockBuilder( 'BC_Accounts' )
		                    ->setMethods( array( 'get_sanitized_all_accounts' ) )
		                    ->disableOriginalConstructor()
		                    ->getMock();

		$bc_accounts->method( 'get_sanitized_all_accounts' )
		            ->willReturn( false );

		$BC_Admin_Settings_Page = $this->getMockBuilder( 'BC_Admin_Settings_Page' )
		                               ->setMethods( array( 'render_no_source_row' ) )
		                               ->getMock();

		$BC_Admin_Settings_Page->expects( $this->once() )
		                       ->method( 'render_no_source_row' )
		                       ->willReturn( '<html>render no source html</html>' );

		$originalOutput = '<html>render no source html</html>';
		$expectedOutput = $BC_Admin_Settings_Page->render_source_rows();

		$this->assertEquals( $originalOutput, $expectedOutput );
	}

	public function test_render_source_rows() {

		$html = '<tr class="source no-sources">';
		$html .= '<td colspan="3">' . esc_html__( 'There are no sources defined. Add one below', 'brightcove' ) . '</td>';
		$html .= '</tr>';

		$sanitized_accounts = array(
			'e894aba0421d8ee3' => array(
				'account_id'   => '4229317772001',
				'account_name' => 'WP6',
				'client_id'    => '924385b2-6978-4b95-acc0-5b261d20e18b',
				'set_default'  => 'default'
			)
		);

		global $bc_accounts;

		$bc_accounts = $this->getMockBuilder( 'BC_Accounts' )
		                    ->setMethods( array( 'get_sanitized_all_accounts' ) )
		                    ->disableOriginalConstructor()
		                    ->getMock();

		$bc_accounts->method( 'get_sanitized_all_accounts' )
		            ->willReturn( $sanitized_accounts );

		$BC_Admin_Settings_Page = $this->getMockBuilder( 'BC_Admin_Settings_Page' )
		                               ->setMethods( array( 'render_source_row' ) )
		                               ->getMock();

		$BC_Admin_Settings_Page->expects( $this->once() )
		                       ->method( 'render_source_row' )
		                       ->willReturn( '<html>render source row html</html>' );

		$originalOutput = '<html>render source row html</html>';
		$expectedOutput = $BC_Admin_Settings_Page->render_source_rows();

		$this->assertEquals( $originalOutput, $expectedOutput );
	}

	public function test_render_source_row() {

		$source = array(
			'account_id'    => '4229317772001',
			'account_name'  => 'WP6',
			'client_id'     => '924385b2-6978-4b95-acc0-5b261d20e18b',
			'client_secret' => 't9SpWEl3l6BDXsYWg7FZODkajGHoyxmxrLnhunom6aB2u907dWPaVK7xj5_oWODP0zVMifZqXNheFoYXXhMLKQ',
			'set_default'   => 'default',
		);
		$hash   = 'e894aba0421d8ee3';


		$BC_Admin_Settings_Page = $this->getMockBuilder( 'BC_Admin_Settings_Page' )
		                               ->setMethods( array( 'action_links' ) )
		                               ->getMock();

		$BC_Admin_Settings_Page->expects( $this->once() )
		                       ->method( 'action_links' )
		                       ->willReturn( '' );

		\WP_Mock::wpFunction( 'get_option', array(
			'args'   => array( '_brightcove_default_account' ),
			'times'  => 1,
			'return' => 'e894aba0421d8ee3'
		) );

		\WP_Mock::wpFunction( 'esc_html', array(
			'times'      => 3,
			'return_arg' => 0
		) );

		\WP_Mock::wpFunction( 'esc_html__', array(
			'times'      => 1,
			'return_arg' => 0
		) );

		\WP_Mock::wpPassthruFunction( 'esc_attr', array( 'times' => 1 ) );


		$default_account      = 'e894aba0421d8ee3';
		$default_account_text = '<strong> &mdash; Default</strong>';

		$html = sprintf( '<tr class="source source-%s">', $source['account_id'] );
		$html .= '<th>';
		$html .= '<strong>' . $source['account_name'] . '</strong>' . $default_account_text; // escaped above
		$html .= '</th>';
		$html .= '<td>';
		$html .= $source['account_id'];
		$html .= '</td>';
		$html .= '<td>';
		$html .= $source['client_id'];
		$html .= '</td>';

		$html .= '</tr>';

		$originalString = $html;
		$expectedString = $BC_Admin_Settings_Page->render_source_row( $hash, $source );
		$this->assertEquals( $originalString, $expectedString );

	}

	public function test_render_source_row_default_account_false() {

		$source = array(
			'account_id'    => '4229317772001',
			'account_name'  => 'WP6',
			'client_id'     => '924385b2-6978-4b95-acc0-5b261d20e18b',
			'client_secret' => 't9SpWEl3l6BDXsYWg7FZODkajGHoyxmxrLnhunom6aB2u907dWPaVK7xj5_oWODP0zVMifZqXNheFoYXXhMLKQ',
			'set_default'   => 'default',
		);
		$hash   = 'e894aba0421d8ee3';


		$BC_Admin_Settings_Page = $this->getMockBuilder( 'BC_Admin_Settings_Page' )
		                               ->setMethods( array( 'action_links' ) )
		                               ->getMock();

		$BC_Admin_Settings_Page->expects( $this->once() )
		                       ->method( 'action_links' )
		                       ->willReturn( '' );

		\WP_Mock::wpFunction( 'get_option', array(
			'args'   => array( '_brightcove_default_account' ),
			'times'  => 1,
			'return' => 'e794aba0421d8ee3'
		) );

		\WP_Mock::wpFunction( 'esc_html', array(
			'times'      => 3,
			'return_arg' => 0
		) );

		\WP_Mock::wpPassthruFunction( 'esc_attr', array( 'times' => 1 ) );


		$default_account      = 'e794aba0421d8ee3';
		$default_account_text = false;

		$html = sprintf( '<tr class="source source-%s">', $source['account_id'] );
		$html .= '<th>';
		$html .= '<strong>' . $source['account_name'] . '</strong>' . $default_account_text; // escaped above
		$html .= '</th>';
		$html .= '<td>';
		$html .= $source['account_id'];
		$html .= '</td>';
		$html .= '<td>';
		$html .= $source['client_id'];
		$html .= '</td>';

		$html .= '</tr>';

		$originalString = $html;
		$expectedString = $BC_Admin_Settings_Page->render_source_row( $hash, $source );
		$this->assertEquals( $originalString, $expectedString );
	}


	public function test_render_no_source_row() {

		$BC_Admin_Settings_Page = $this->mock_bc_admin_settings_page();

		\WP_Mock::wpFunction( 'esc_html__', array(
			'times'      => 1,
			'return_arg' => 0
		) );

		$html = '<tr class="source no-sources">';
		$html .= '<td colspan="3">There are no sources defined. Add one below</td>';
		$html .= '</tr>';

		$originalString = $html;
		$expectedString = $BC_Admin_Settings_Page->render_no_source_row();
		$this->assertEquals( $originalString, $expectedString );
	}
}
