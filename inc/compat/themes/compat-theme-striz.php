<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Striz (by Opal Team).
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
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );
	}



	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		remove_action( 'woocommerce_checkout_before_customer_details', 'xstriz_checkout_before_customer_details_container', 1 );
		remove_action( 'woocommerce_checkout_after_customer_details', 'xstriz_checkout_after_customer_details_container', 1 );
		remove_action( 'woocommerce_checkout_after_order_review', 'xstriz_checkout_after_order_review_container', 1 );
		remove_action( 'woocommerce_checkout_order_review', 'xstriz_woocommerce_order_review_heading', 1 );
	}

}

FluidCheckout_ThemeCompat_Striz::instance();
