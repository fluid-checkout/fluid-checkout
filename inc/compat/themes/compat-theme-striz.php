<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Striz.
 */
class FluidCheckout_ThemeCompat_Striz extends FluidCheckout {

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
		// Bail if current theme is not striz
		if ( 'striz' !== get_template() ) { return; }

		// Very late hooks
		remove_action('woocommerce_checkout_before_customer_details', 'xstriz_checkout_before_customer_details_container', 1);
		remove_action('woocommerce_checkout_after_customer_details', 'xstriz_checkout_after_customer_details_container', 1);
		remove_action('woocommerce_checkout_after_order_review', 'xstriz_checkout_after_order_review_container', 1);
	}

}

FluidCheckout_ThemeCompat_Striz::instance();