<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Rehub theme (by Wpsoul).
 */
class FluidCheckout_ThemeCompat_RehubTheme extends FluidCheckout {

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
		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// Order review section layout
		remove_action( 'woocommerce_checkout_before_order_review_heading', 'rehub_woo_order_checkout', 10 );
		remove_action( 'woocommerce_checkout_after_order_review', 'rehub_woo_after_order_checkout', 10 );
	}

}

FluidCheckout_ThemeCompat_RehubTheme::instance();
