<?php
/**
 * The BC labels class
 *
 * Class BC_Labels
 */
class BC_Labels {
	/**
	 * Instance of the CMS API
	 *
	 * @var BC_CMS_API
	 */
	protected $cms_api;

	/**
	 * Construct function. Initializes instances
	 *
	 * BC_Labels constructor.
	 */
	public function __construct() {
		$this->cms_api = new BC_CMS_API();

		add_action( 'admin_post_brightcove-edit-label', array( $this, 'edit_label' ) );
		add_action( 'admin_post_brightcove-add-label', array( $this, 'add_label' ) );
		add_action( 'admin_post_brightcove-delete-label', array( $this, 'delete_label' ) );
		add_action( 'admin_notices', array( $this, 'notices' ) );
	}

	/**
	 * Displays a variety of notices for adding and editing labels
	 */
	public function notices() {
		if ( isset( $_GET['add_label'] ) && 1 === (int) $_GET['add_label'] ) {
			BC_Utility::admin_notice_messages(
				array(
					array(
						'message' => esc_html__( 'Label added', 'brightcove' ),
						'type'    => 'updated',
					),
				)
			);
		}

		if ( isset( $_GET['refresh_labels'] ) && 1 === (int) $_GET['refresh_labels'] ) {
			BC_Utility::admin_notice_messages(
				array(
					array(
						'message' => esc_html__( 'Labels refreshed', 'brightcove' ),
						'type'    => 'updated',
					),
				)
			);
		}

		if ( isset( $_GET['label_deleted'] ) && 1 === (int) $_GET['label_deleted'] ) {
			BC_Utility::admin_notice_messages(
				array(
					array(
						'message' => esc_html__( 'Label deleted', 'brightcove' ),
						'type'    => 'updated',
					),
				)
			);
		}

		if ( isset( $_GET['label_updated'] ) && 1 === (int) $_GET['label_updated'] ) {
			BC_Utility::admin_notice_messages(
				array(
					array(
						'message' => esc_html__( 'Label Updated', 'brightcove' ),
						'type'    => 'updated',
					),
				)
			);
		}

	}

	/**
	 * Fetch labels
	 *
	 * @param bool $refresh Whether to force fetching labels from the API.
	 * @return mixed The label list.
	 */
	public function fetch_all( $refresh = false ) {
		global $bc_accounts;

		$transient_key = BC_Utility::generate_transient_key( 'brightcove_get_labels_', $bc_accounts->get_account_id() );
		$results       = BC_Utility::get_cache_item( $transient_key );

		if ( ! $results ) {
			$results = $this->cms_api->get_account_labels();
			BC_Utility::set_cache_item( $transient_key, 'label_list', $results, 5 * MINUTE_IN_SECONDS );
		}

		if ( $refresh ) {
			BC_Utility::delete_cache_item( '*' );
			$transient_key = BC_Utility::generate_transient_key( 'brightcove_get_labels_', $bc_accounts->get_account_id() );
			$results       = $this->cms_api->get_account_labels();
			BC_Utility::set_cache_item( $transient_key, 'label_list', $results, 5 * MINUTE_IN_SECONDS );
		}

		return $results;
	}

	/**
	 * Edit an existing label
	 */
	public function edit_label() {
		if ( wp_verify_nonce( sanitize_key( wp_unslash( $_POST['brightcove-edit-label-nonce'] ) ), 'brightcove-edit-label' )
			&&
			isset( $_POST['label-path'] )
			&&
			isset( $_POST['label-update'] )
		) {
			$update_label = sanitize_text_field( $_POST['label-update'] );
			$path         = sanitize_text_field( $_POST['label-path'] );
			$this->cms_api->update_label( $update_label, $path );
			wp_safe_redirect( admin_url( 'admin.php?page=brightcove-labels&label_updated=1&refresh_labels=1' ) );
			exit;
		}
	}

	/**
	 * Adds a label
	 */
	public function add_label() {
		if ( wp_verify_nonce( sanitize_key( wp_unslash( $_POST['brightcove-add-label-nonce'] ) ), 'brightcove-add-label' )
			&&
			isset( $_POST['label-name'] )
		) {
			$label_name = sanitize_text_field( $_POST['label-name'] );
			$label_path = ! empty( $_POST['label-path'] ) ? $_POST['label-path'] : '';
			$this->cms_api->add_label( $label_name, $label_path );
			wp_safe_redirect( admin_url( 'admin.php?page=brightcove-labels&add_label=1&refresh_labels=1' ) );
			exit;
		}
	}

	/**
	 * Deletes an existing label
	 */
	public function delete_label() {
		if ( wp_verify_nonce( sanitize_key( wp_unslash( $_POST['brightcove-delete-label-nonce'] ) ), 'brightcove-delete-label' )
		) {
			$labels = array();
			foreach ( $_POST['delete_labels'] as $label ) {
				$labels[] = sanitize_text_field( $label );
			}
			$this->cms_api->delete_label( $labels );
			wp_redirect( admin_url( 'admin.php?page=brightcove-labels&label_deleted=1&refresh_labels=1' ) );
			exit;
		}
	}
}
