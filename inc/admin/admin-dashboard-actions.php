<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin notices.
 */
class FluidCheckout_AdminDashboardActions extends FluidCheckout {

	/**
	 * Plugin prefix for the admin notices options.
	 */
	private static $plugin_prefix = 'fc';



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
		add_action( 'admin_init', array( $this, 'activate_plugin' ), 10 );
	}



	/**
	 * Activate plugins.
	 */
	public function activate_plugin() {
		// Bail if nonce is invalid
		if ( ! array_key_exists( '_wpnonce', $_GET ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ '_wpnonce' ] ) ), 'fc-activate-plugin' ) ) { return; }

		// Bail if user does not have necessary permissions
		if ( ! current_user_can( 'install_plugins' ) ) { return; }

		// Bail if not activating plugin
		if ( ! array_key_exists( self::$plugin_prefix . '_action', $_GET ) || 'activate_plugin' !== sanitize_text_field( wp_unslash( $_GET[ self::$plugin_prefix . '_action' ] ) ) || empty( sanitize_text_field( wp_unslash( $_GET[ 'plugin' ] ) ) ) ) { return; }

		// Activate plugin
		$plugin = sanitize_text_field( wp_unslash( $_GET[ 'plugin' ] ) );
		activate_plugin( $plugin );

		// Redirect
		$redirect_url = sanitize_text_field( wp_unslash( $_GET[ 'redirect_url' ] ) );
		$redirect_url = ! empty( $redirect_url ) ? esc_url( $redirect_url ) : admin_url( 'admin.php?page=wc-settings&tab=fc_checkout' );
		wp_safe_redirect( $redirect_url );
	}
	
}

FluidCheckout_AdminDashboardActions::instance();
