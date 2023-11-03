<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Botiga (by aThemes).
 */
class FluidCheckout_ThemeCompat_Botiga extends FluidCheckout {

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

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
	}



	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Remove theme elements
		remove_action( 'woocommerce_checkout_before_order_review_heading', 'botiga_wrap_order_review_before', 5 );
		remove_action( 'woocommerce_checkout_after_order_review', 'botiga_wrap_order_review_after', 15 );
	}

}

FluidCheckout_ThemeCompat_Botiga::instance();
