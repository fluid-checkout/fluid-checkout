<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Qi (by Qode Interactive).
 */
class FluidCheckout_ThemeCompat_Qi extends FluidCheckout {

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
		remove_action( 'woocommerce_before_checkout_form', 'qi_add_main_woo_page_holder', 5 );
		remove_action( 'woocommerce_after_checkout_form', 'qi_add_main_woo_page_holder_end', 20 );
		add_action( 'woocommerce_before_checkout_form_cart_notices', 'qi_add_main_woo_page_holder', 1 );
		add_action( 'woocommerce_after_checkout_form', 'qi_add_main_woo_page_holder_end', 101 );
	}

}

FluidCheckout_ThemeCompat_Qi::instance();
