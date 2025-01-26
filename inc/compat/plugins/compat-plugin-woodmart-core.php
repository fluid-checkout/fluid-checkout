<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Woodmart Core (by XTemos).
 */
class FluidCheckout_WoodmartCore extends FluidCheckout {

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
		// Clear session values when using 'Social authentication' feature from Woodmart
		// This is required since the plugin doesn't trigger the 'wp_login' action
		add_action( 'woocommerce_guest_session_to_user_id', array( FluidCheckout_Steps::instance(), 'unset_all_session_customer_persisted_data' ), 100 );
	}

}

FluidCheckout_WoodmartCore::instance();
