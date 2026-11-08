<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin dashboard actions for add-ons.
 */
class FluidCheckout_AdminDashboardActions extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		add_action( 'wp_ajax_fc_activate_plugin', array( $this, 'ajax_activate_plugin' ), 10 );
	}



	/**
	 * Whether the current user can run dashboard add-on actions.
	 */
	public function current_user_can_manage_addons() {
		return current_user_can( 'manage_woocommerce' ) || current_user_can( 'install_plugins' );
	}



	/**
	 * Verify an AJAX dashboard action request.
	 *
	 * @param string $nonce_action Nonce action name.
	 */
	public function verify_ajax_request( $nonce_action ) {
		if ( ! $this->current_user_can_manage_addons() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'fluid-checkout' ) ), 403 );
		}

		check_ajax_referer( $nonce_action, 'nonce' );
	}



	/**
	 * AJAX: activate a locally installed plugin.
	 */
	public function ajax_activate_plugin() {
		$this->verify_ajax_request( 'fc-activate-plugin' );

		if ( ! current_user_can( 'install_plugins' ) && ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to activate plugins.', 'fluid-checkout' ) ), 403 );
		}

		$plugin = sanitize_text_field( wp_unslash( $_POST['plugin'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Bail if plugin file is missing
		if ( empty( $plugin ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing plugin.', 'fluid-checkout' ) ) );
		}

		$result = activate_plugin( $plugin );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Plugin activated.', 'fluid-checkout' ),
				'reload'  => true,
			)
		);
	}

}

FluidCheckout_AdminDashboardActions::instance();
