<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Aperitif Core (by Qode Themes).
 */
class FluidCheckout_AperitifCore extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct( ) {
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );
	}



	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Remove container with ID `qodef-woo-page` added by the plugin
		remove_action( 'woocommerce_before_checkout_form', 'aperitif_core_add_main_woo_page_holder', 5 );
		remove_action( 'woocommerce_after_checkout_form', 'aperitif_core_add_main_woo_page_holder_end', 20 );
	}

}

FluidCheckout_AperitifCore::instance(); 
