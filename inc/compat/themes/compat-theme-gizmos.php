<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Gizmos (by Mikado Themes).
 */
class FluidCheckout_ThemeCompat_Gizmos extends FluidCheckout {

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
		remove_action( 'woocommerce_before_checkout_form', 'gizmos_add_main_woo_page_holder', 5 );
		remove_action( 'woocommerce_after_checkout_form', 'gizmos_add_main_woo_page_holder_end', 20 );
	}

}

FluidCheckout_ThemeCompat_Gizmos::instance();
