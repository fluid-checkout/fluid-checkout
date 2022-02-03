<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Aora (by Thembay).
 */
class FluidCheckout_ThemeCompat_Aora extends FluidCheckout {

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
		remove_filter( 'woocommerce_cart_item_name', 'aora_woocommerce_cart_item_name', 10, 3 );
	}

}

FluidCheckout_ThemeCompat_Aora::instance();
