<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Talemy (by ThemeSpirit).
 */
class FluidCheckout_ThemeCompat_Talemy extends FluidCheckout {

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
		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );
	}



	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Fixes contact change button not being displayed.
		$this->remove_action_for_class( 'woocommerce_checkout_before_customer_details', array( 'Talemy_WooCommerce', 'customer_details_start' ), 0 );

		// Fixes the product summary not being displayed.
		$this->remove_action_for_class( 'woocommerce_checkout_after_customer_details', array( 'Talemy_WooCommerce', 'customer_details_end' ), 3000 );

	}

}

FluidCheckout_ThemeCompat_Talemy::instance();
