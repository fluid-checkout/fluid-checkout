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
		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );
	}



	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Bail if not at checkout page, and not an AJAX request to update checkout fragment
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Remove container with ID `qodef-woo-page` added by the plugin
		remove_action( 'woocommerce_before_checkout_form', 'aperitif_core_add_main_woo_page_holder', 5 );
		remove_action( 'woocommerce_after_checkout_form', 'aperitif_core_add_main_woo_page_holder_end', 20 );

		// Re-add Woocommerce stylesheet
		remove_filter( 'woocommerce_enqueue_styles', '__return_false' );
	}

}

FluidCheckout_AperitifCore::instance(); 
