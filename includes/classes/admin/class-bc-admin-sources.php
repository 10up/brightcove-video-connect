<?php

/**
 * Class BC_Admin_Sources
 */
class BC_Admin_Sources {

    /**
     * @var array Stores admin_notices messages
     */
    public $notices;

    public function __construct() {

        $this->notices = array();

        add_action( 'brightcove/admin/edit_source_page', array( $this, 'render' ) );
        add_action( 'admin_init', array( $this, 'save_account' ), 1 ); # Avoid a race condition where the account doesn't get saved properly
        add_action( 'admin_notices', array( $this, 'admin_notice_handler' ) );
    }

    /**
     * Renders the HTML form for adding or updating a source
     */
    public function render() {
        global $bc_accounts;

        $is_source_edit = false;
        if ( array_key_exists( 'account', $_GET ) ) {
            $hash     = sanitize_text_field( $_GET['account'] );
            $account  = $bc_accounts->get_account_by_hash( $hash );
            if ( ! $account ) {
                $error_message = esc_html__( 'This account could not be found', 'brightcove' );
                BC_Logging::log( sprintf( 'ACCOUNT: %s', $error_message ) );
                $this->notices[] = array( 'message' => $error_message, 'type' => 'error' );

                return new WP_Error( 'brightcove-account-sources-edit-not-found', $error_message );
            }
            $this->render_edit_html( $account );
        } else {
            $this->render_add_html();
        }
    }

    /**
     * Provides the handler for saving/updating source data
     *
     * @return bool/WP_Error
     */
    public function save_account() {

        global $bc_accounts;

        if( !isset( $_POST['brightcove-check_oauth'] ) ) {
            return false;
        }

        if ( ! current_user_can( 'brightcove_manipulate_accounts' ) ) {
            $error_message = esc_html__( 'You do not have permission to manage this account.', 'brightcove' );
            BC_Logging::log( sprintf( 'ACCOUNT: %s', $error_message ) );
            $this->notices[] = array( 'message' => $error_message, 'type' => 'error' );

            return new WP_Error( 'brightcove-account-manage-permissions', $error_message );
        }

        if ( ! wp_verify_nonce( $_POST['brightcove-check_oauth'], '_brightcove_check_oauth_for_source' ) ) {
            return false;
        }

        // Only go through the oAuth credential validation when we're adding a new account or editing the account's credentials (not default players etc)
        if ( 'create' === $_POST['source-action'] ) {

            $required_keys = array(
                'brightcove-check_oauth',
                'source-account-id',
                'source-client-id',
                'source-client-secret',
                'source-name'
            );

            foreach ( $required_keys as $key ) {
                if ( ! array_key_exists( $key, $_POST ) ) {
                    return false;
                }
            }

            $account_id    = BC_Utility::sanitize_id( $_POST['source-account-id'] );
            $client_id     = sanitize_text_field( $_POST['source-client-id'] );
            $client_secret = BC_Utility::get_sanitized_client_secret( $_POST['source-client-secret'] );
            $account_name  = sanitize_text_field( stripslashes_deep( $_POST['source-name'] ) );
            $set_default = ( isset( $_POST['source-default-account'] ) && 'on' === $_POST['source-default-account'] ) ? 'default' : '';

            $hash     = BC_Utility::get_hash_for_account( array(
                'account_id'    => $account_id,
                'client_id'     => $client_id,
                'client_secret' => $client_secret
            ) );
            $account  = $bc_accounts->get_account_by_hash( $hash );
            if ( $account ) {
                // Account already exists
                $error_message = esc_html__( 'The Brightcove credentials provided already exist.', 'brightcove' );
                BC_Logging::log( sprintf( 'BC ACCOUNTS: %s', $error_message ) );
                $this->notices[] = array( 'message' => $error_message, 'type' => 'error' );

                return new WP_Error( 'bc-account-exists-error', $error_message );
            }

            if ( ! $bc_accounts->add_account( $account_id, $client_id, $client_secret, $account_name, $set_default, false ) ) {
                $error_message = esc_html__( 'We could not authenticate your credentials with Brightcove', 'brightcove' );
                BC_Logging::log( sprintf( 'BC OAUTH ERROR: %s', $error_message ) );
                $this->notices[] = array( 'message' => $error_message, 'type' => 'error' );

                return new WP_Error( 'bc-oauth-error', $error_message );
            }

            BC_Utility::clear_cached_api_requests( 'all' );
            $bc_accounts->set_current_account_by_id( $account_id );
            $players = new BC_Players();
            $players->sync_players();
        }

        if( 'update' === $_POST['source-action'] ) {
            if ( isset( $_POST['source-default-account']) && 'on' === $_POST['source-default-account'] ) {
                update_option( '_brightcove_default_account', sanitize_text_field( $_POST['hash'] ) );
            }
        }

        // Deleting transient to allow syncing from the new account, otherwise we won't be able to sync it until this transient expires.
        delete_transient( 'brightcove_sync_videos' );
        $this->notices[] = array(
        					'message' => sprintf( '%s <a href="%s">%s</a>.',
	        					esc_html__( 'Congratulations! Your credentials have been authenticated. Return to', 'brightcove' ),
	        					admin_url( 'admin.php?page=brightcove-sources '),
	        					esc_html__( 'Settings', 'brightcove' )
	        					),
        					'type' => 'updated'
        					);

        return true;
    }

    /**
     * Method to display source update or error messages
     *
     * @return bool
     */
    public function admin_notice_handler() {

        if ( empty( $this->notices ) ) {
            return false;
        }

        BC_Utility::admin_notice_messages( $this->notices );
    }

    public function render_add_html() {

        ?>
        <div class="wrap">
            <h2><?php
                printf( '<img src="%s" class="bc-page-icon"/>', plugins_url( 'images/admin/menu-icon.svg', dirname( dirname( __DIR__ ) ) ) );
                ?> <?php esc_html_e( 'Add Source', 'brightcove' ) ?></h2>

            <form action="" method="post">
                <table class="form-table brightcove-add-source-name">
                    <tbody>
                    <tr class="brightcove-account-row">
                        <th scope="row"><?php esc_html_e( 'Source Name', 'brightcove' ) ?></th>
                        <td>
                            <input type="text" name="source-name" id="source-name"
                                   placeholder="<?php esc_html_e( 'My Brightcove Account Name', 'brightcove' ) ?>"
                                   class="regular-text" required="required"/>

                            <p class="description"><?php esc_html_e( 'This is how the source will be identified in WordPress', 'brightcove' ) ?></p>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <h3><?php esc_html_e( 'Credentials', 'brightcove' ) ?></h3>

                <p class="description">
                    <?php esc_html_e( 'Each token has a set of API permissions defined when registering a new client.', 'brightcove' ) ?><br>
                    <?php echo sprintf( '%s <a href="https://studio.brightcove.com/products/videocloud/admin/oauthsettings">%s</a>.',
                    		esc_html__( 'You can check the permissions and find out more about the settings below in', 'brightcove' ),
                    		esc_html__( 'Video Cloud Studio', 'brightcove' )
                    		);
                    ?>
                </p>
                <table class="form-table brightcove-add-source-details">
                    <tbody>
                    <tr class="brightcove-account-row">
                        <th scope="row"><?php esc_html_e( 'Account ID', 'brightcove' ) ?></th>
                        <td>
                            <input type="text" name="source-account-id" id="source-account-id" class="regular-text"
                                   required="required"/>
                        </td>
                    </tr>
                    <tr class="brightcove-account-row">
                        <th scope="row"><?php esc_html_e( 'Client ID', 'brightcove' ) ?></th>
                        <td>
                            <input type="password" name="source-client-id" id="source-client-id" class="regular-text"
                                   required="required">
                            <p class="description"><?php esc_html_e( 'A unique identifier for a client generated by Brightcove', 'brightcove' ) ?></p>
                        </td>
                    </tr>
                    <tr class="brightcove-account-row">
                        <th scope="row"><?php esc_html_e( 'Client Secret', 'brightcove' ) ?></th>
                        <td>
                            <input type="password" name="source-client-secret" id="source-client-secret"
                                   class="regular-text" required="required">
                            <p class="description"><?php esc_html_e( 'A unique identifier generated by Brightcove, used with a client id. Serves as a password to authenticate a client', 'brightcove' ) ?></p>
                        </td>
                    </tr>

                    <tr class="brightcove-account-row">
                        <th scope="row"><?php esc_html_e( 'Default Source', 'brightcove' ) ?></th>
                        <td>
                            <input type="checkbox"
                                   name="source-default-account" >&nbsp;
                            <?php esc_html_e( 'Make this the default source for new users', 'brightcove' ); ?>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <?php
                wp_nonce_field( '_brightcove_check_oauth_for_source', 'brightcove-check_oauth', false, true );
                ?>
                <p class="submit">
                    <input type="hidden" name="source-action" value="create"/>
                    <input type="submit" name="brightcove-edit-account-submit" id="brightcove-edit-account-submit"
                           class="button button-primary" value="<?php esc_html_e( 'Check Credentials', 'brightcove' ) ?>">
                </p>
            </form>
        </div>
    <?php
    }

    public function render_edit_html( $account ) {

        global $bc_accounts;

        ?>
        <div class="wrap">

            <h2><?php
                printf( '<img src="%s" class="brightcove-admin-icon"/>', plugins_url( 'images/admin/menu-icon.svg', dirname( dirname( __DIR__ ) ) ) );
                ?> <?php esc_html_e( 'Edit Source', 'brightcove' ) ?></h2>

            <form action="" method="post">
                <table class="form-table brightcove-add-source-name">
                    <tbody>
                    <tr class="brightcove-account-row">
                        <th scope="row"><?php esc_html_e( 'Source Name', 'brightcove' ) ?></th>
                        <td>
                            <?php echo esc_html( $account['account_name'] ); ?>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <table class="form-table brightcove-add-source-details">
                    <tbody>
                    <tr class="brightcove-account-row">
                        <th scope="row"><?php esc_html_e( 'Account ID', 'brightcove' ) ?></th>
                        <td>
                            <?php echo esc_html( $account['account_id'] ) ?>
                        </td>
                    </tr>
                </table>

                <table class="form-table">
                    <tr class="brightcove-account-row">
                        <th scope="row"><?php esc_html_e( 'Default Source', 'brightcove' ) ?></th>
                        <td>
                            <input type="checkbox"
                                   name="source-default-account" <?php checked( get_option( '_brightcove_default_account' ), $account['hash'] ) ?> >&nbsp;
                            <?php esc_html_e( 'Make this the default source for new users', 'brightcove' ); ?>
                        </td>
                    </tr>
                    <!-- Pending Unblocking on Player API setting Default Player
					<tr class="brightcove-account-row">
						<th scope="row"><?php esc_html_e( 'Default Player', 'brightcove' ) ?></th>
						<td>
							<?php
                    $bc_accounts->get_account_details_for_user();
                    $player_api = new BC_Player_Management_API();
                    $players    = $player_api->player_list();
                    ?>
							<select name="sources-players">
								<?php
                    foreach ( $players['items'] as $player ) {
                        printf( '<option value="%1$s" %3$s>%2$s</option>', esc_attr( $player['id'] ), esc_html( $player['name'] ), selected( 'default', $player['id'] ) );
                    }
                    ?>
							</select>
						</td>
					</tr>-->
                </table>

                <?php
                wp_nonce_field( '_brightcove_check_oauth_for_source', 'brightcove-check_oauth', false, true );
                ?>
                <p class="submit">
                    <input type="hidden" name="hash" value="<?php echo esc_attr( $account['hash'] ) ?>">
                    <input type="hidden" name="source-action" value="update"/>
                    <input type="submit" name="brightcove-edit-account-submit" id="brightcove-edit-account-submit"
                           class="button button-primary" value="<?php esc_html_e( 'Save Changes', 'brightcove' ) ?>">
                </p>
            </form>
        </div>
    <?php
    }

}
