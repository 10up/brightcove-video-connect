<?php

class BC_Notifications {

	protected $cms_api;

	public function __construct() {
		$this->cms_api = new BC_CMS_API();
		$this->subscribe_if_not_subscribed();

	}

	public function get_option_key_for( $account_id ) {

		return '_notifications_subscribed_' . BC_Utility::sanitize_id( $account_id );

	}

	public function is_subscribed( $account_id ) {

		return false !== get_option( $this->get_option_key_for( $account_id ) );

	}

	public function remove_subscription( $hash ) {
		global $bc_accounts;
		$bc_accounts->set_current_account( $hash );
		delete_option( $this->get_option_key_for( $bc_accounts->get_account_id() ) );
		$subscriptions = $this->cms_api->get_subscriptions();
		if ( is_array( $subscriptions ) ) {

			foreach ( $subscriptions as $subscription ) {
				if (false !== strpos( $subscription['endpoint'], get_admin_url() ) ) {
					// Unsubscribe as we were subscribed
					$this->cms_api->remove_subscription($subscription['id']);
				}
			}
		}
		$bc_accounts->restore_default_account();
	}

	public function subscribe_if_not_subscribed() {
		global $bc_accounts;

		$accounts           = $bc_accounts->get_sanitized_all_accounts();
		$completed_accounts = array();

		foreach ( $accounts as $account => $account_data ) {

			// We may have multiple accounts for an account_id, prevent syncing that account more than once.
			if ( ! in_array( $account_data['account_id'], $completed_accounts ) ) {

				$completed_accounts[] = $account_data['account_id'];

				$bc_accounts->set_current_account( $account );

				$subscriptions = $this->cms_api->get_subscriptions();

				if ( is_array( $subscriptions ) ) {

					foreach ( $subscriptions as $subscription ) {

						if ( $bc_accounts->get_account_id() === $subscription['service_account'] &&
							isset( $subscription['id'] ) &&
							// Check that we're only deleting subscriptions relating to this WP instance.
							false !== strpos( $subscription['endpoint'], get_admin_url() )
						) {
							$this->cms_api->remove_subscription( $subscription['id'] );
						}
					}
				}

				$subscription_status = $this->cms_api->add_subscription();

				if ( is_wp_error( $subscription_status ) ) {

					$bc_accounts->restore_default_account();
					return false;
				}

				if ( isset( $subscription_status['id'] ) && $subscription_status['service_account'] === $bc_accounts->get_account_id() ) {

					$subscription_id = BC_Utility::sanitize_subscription_id( $subscription_status['id'] );
					update_option( $this->get_option_key_for( $bc_accounts->get_account_id() ), $subscription_id );

				}

				$bc_accounts->restore_default_account();

			}

		}

	}

}