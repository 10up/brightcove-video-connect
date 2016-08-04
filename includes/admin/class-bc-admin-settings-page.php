<?php

class BC_Admin_Settings_Page {

	public function __construct() {

		add_action( 'brightcove/admin/settings_page', array( $this, 'render' ) );
		add_action( 'admin_init', array( $this, 'delete_source' ) );

	}

	public function delete_source() {

		global $bc_accounts;

		if ( ! isset( $_GET['_wpnonce'] ) ) {
			return false;
		}

		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'bc_delete_source_id_' . $_GET['account'] ) ) {
			return false;
		}

		$delete_account = $bc_accounts->delete_account( sanitize_text_field( $_GET['account'] ) );
		if ( is_wp_error( $delete_account ) ) {
			BC_Utility::admin_notice_messages( array( array( 'message' => $delete_account->get_error_message(), 'type' => 'error' ) ) );
		}

		BC_Utility::admin_notice_messages( array( array( 'message' => esc_html__( 'Source Deleted', 'brightcove' ), 'type' => 'updated' ) ) );

		return true;

	}

	/**
	 * Generates an HTML table with all configured sources
	 */
	public function render() {

		?>

		<div class="wrap">

			<h2>
				<img class="bc-page-icon" src="<?php echo esc_url( BRIGHTCOVE_URL . 'images/menu-icon.svg' ); ?>"> <?php esc_html_e( 'Brightcove Settings', 'brightcove' ); ?>
			</h2>

			<h3 class="title"><?php esc_html__( 'Sources', 'brightcove' ); ?></h3>

			<table class="wp-list-table widefat">
				<thead>
				<tr>
					<th><?php esc_html_e( 'Source Name', 'brightcove' ) ?></th>
					<th><?php esc_html_e( 'Account ID', 'brightcove' ) ?></th>
					<th><?php esc_html_e( 'Client ID', 'brightcove' ) ?></th>
				</tr>
				</thead>
				<tbody>
				<?php
				echo $this->render_source_rows();
				?>
				</tbody>
			</table>

			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=page-brightcove-edit-source' ) ); ?>" class="button action"><?php esc_html_e( 'Add Brightcove Account', 'brightcove' ); ?></a>
			</p>

		</div>

		<?php
	}

	/**
	 * Creates a filterable list of actions that can be performed on each source. By default, the only action link is an edit link
	 *
	 * @param $source_id
	 *
	 * @return string
	 */
	public function action_links( $hash ) {

		$actions = array();

		if ( current_user_can( 'brightcove_manipulate_accounts' ) ) {
			$actions['edit']   = sprintf( '<a href="%1$s" class="brightcove-action-links brightcove-action-edit-source" title="%2$s">%2$s</a>', admin_url( sprintf( 'admin.php?page=page-brightcove-edit-source&account=%s', $hash ) ), esc_html__( 'Edit Source', 'brightcove' ) );
			$actions['delete'] = sprintf( '<a href="%1$s" class="brightcove-action-links brightcove-action-delete-source" title="%2$s" data-alert-message="%3$s">%2$s</a>',
			                              esc_url(
				                              admin_url( sprintf( 'admin.php?page=brightcove-sources&action=delete&account=%1$s&_wpnonce=%2$s', $hash, wp_create_nonce( 'bc_delete_source_id_' . $hash ) ) )
			                              ),
			                              esc_html__( 'Delete Source', 'brightcove' ),
			                              esc_html__( 'By deleting this source, WordPress will no longer have access to associated videos and playlists. Your content remains in Brightcove.', 'brightcove' )
			);
		}

		/**
		 * Filter the available actions for each source on the Brightcove admin settings page.
		 *
		 * Enables adding or removing source actions on the settings screen.
		 *
		 * @param array $actions {
		 *      The array of available actions.
		 *
		 * 		@param string $action      The name of the action.
		 * 		@param string $action_link The link for the action.
		 * }
		 */
		$actions = apply_filters( 'brightcove_account_actions', $actions );
		$html    = '<div class="row-actions">';
		$links   = array();
		foreach ( $actions as $action => $action_link ) {
			$links[] = sprintf( '<span class="%1$s">%2$s</span>', esc_attr( $action ), $action_link );
		}
		$html .= implode( ' | ', $links );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Renders All sources into table rows
	 *
	 * @return string
	 */
	public function render_source_rows() {

		global $bc_accounts;

		$sources = $bc_accounts->get_sanitized_all_accounts();

		$html = '';
		if ( ! $sources ) {
			$html .= $this->render_no_source_row();
		} else {
			foreach ( $sources as $hash => $source ) {
				$html .= $this->render_source_row( $hash, $source );
			}
		}

		return $html;
	}

	/**
	 * Renders a row in the the source table and populates with relevant information about that particular source
	 *
	 * @param $source object
	 *
	 * @return string
	 */
	public function render_source_row( $hash, $source ) {

		$default_account      = get_option( '_brightcove_default_account' );
		$default_account_text = ( $default_account === $hash ) ? '<strong> &mdash; ' . esc_html__( 'Default', 'brightcove' ) . '</strong>' : false;

		$html = sprintf( '<tr class="source source-%s">', esc_attr( $source['account_id'] ) );
		$html .= '<th>';
		$html .= '<strong>' . esc_html( $source['account_name'] ) . '</strong>' . $default_account_text; // escaped above
		$html .= $this->action_links( $hash );
		$html .= '</th>';
		$html .= '<td>';
		$html .= esc_html( $source['account_id'] );
		$html .= '</td>';
		$html .= '<td>';
		$html .= esc_html( $source['client_id'] );
		$html .= '</td>';

		$html .= '</tr>';

		return $html;
	}

	/**
	 * Renders a table row when no sources are available/configured
	 *
	 * @return string
	 */
	public function render_no_source_row() {

		$html = '<tr class="source no-sources">';
		$html .= '<td colspan="3">' . esc_html__( 'There are no sources defined. Add one below', 'brightcove' ) . '</td>';
		$html .= '</tr>';

		return $html;
	}
}
