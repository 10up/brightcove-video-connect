<?php

class BC_Admin_User_Profile {

	public function __construct() {

		add_action( 'edit_user_profile', array( $this, 'brightcove_profile_ui' ) );
		add_action( 'show_user_profile', array( $this, 'brightcove_profile_ui' ) );
		add_action( 'admin_init', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_init', array( $this, 'update_profile' ) );
	}

	public function enqueue_styles() {
		wp_enqueue_style( 'brightcove-video-connect' );
	}

	public function brightcove_profile_ui( $user ) {

		global $bc_accounts;

		$accounts = $bc_accounts->get_sanitized_all_accounts();

		$default_account = BC_Utility::get_user_meta( $user->ID, '_brightcove_default_account_' . get_current_blog_id(), true );
		if( ! $default_account ) {
			// If for some reason a user doesn't have a default account, fall back on the site default account
			$default_account = get_option( '_brightcove_default_account' );
		}
		?>
		<h3><img class="profile-brightcove-logo" src="<?php echo esc_url( BRIGHTCOVE_URL . 'images/menu-icon.svg' ) ?>" /><?php esc_html_e( 'Brightcove Preferences', 'brightcove' ) ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Default Source', 'brightcove' ) ?></th>
				<td>
					<select name="bc-user-default-source">
						<?php
						foreach( $accounts as $hash => $account ) {
							echo sprintf( '<option value="%1$s" ' . selected( $default_account, $hash ) . '>%2$s</option>', esc_attr( $hash ), esc_html( $account['account_name'] ) );
						}
						?>
					</select>
				</td>
			</tr>
		</table>
	<?php
		wp_nonce_field( 'bc_profile_nonce', '_bc_profile_nonce' );
	}

	public function update_profile() {
		global $bc_accounts;

		if( !isset( $_POST['_bc_profile_nonce' ] ) ) {
			return false;
		}

		if( ! wp_verify_nonce( $_POST['_bc_profile_nonce'], 'bc_profile_nonce' ) ) {
			return false;
		}

		$hash = BC_Utility::sanitize_payload_item( $_POST['bc-user-default-source'] );
		$user_id = BC_Utility::sanitize_id( $_POST['user_id'] );
		$accounts = $bc_accounts->get_sanitized_all_accounts();
		if( ! isset( $accounts[ $hash ] ) ) {
			BC_Utility::admin_notice_messages( array( array( 'message' => esc_html__( 'The specified Source does not exist.', 'brightcove' ), 'type' => 'error' ) ) );
			return false;
		}

		BC_Utility::update_user_meta( $user_id, '_brightcove_default_account_' . get_current_blog_id(), $hash );
		return true;
	}
}
