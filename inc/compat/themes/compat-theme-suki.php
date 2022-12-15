<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Suki (by Suki WordPress Theme).
*/
class FluidCheckout_ThemeCompat_Suki extends FluidCheckout {

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
		// Remove dual column layout.
		if ( class_exists( 'Suki_Compatibility_WooCommerce' ) ) {
			remove_action( 'woocommerce_checkout_before_customer_details', array( Suki_Compatibility_WooCommerce::instance(), 'render_checkout_2_columns_wrapper' ), 1 );

			remove_action( 'woocommerce_checkout_before_customer_details', array(Suki_Compatibility_WooCommerce::instance(), 'render_checkout_2_columns_left_wrapper' ), 1 );
			remove_action( 'woocommerce_checkout_after_customer_details', array( Suki_Compatibility_WooCommerce::instance(), 'render_checkout_2_columns_left_wrapper_end' ), 999 );

			remove_action( 'woocommerce_checkout_before_order_review_heading', array( Suki_Compatibility_WooCommerce::instance(), 'render_checkout_2_columns_right_wrapper' ), 1 );
			remove_action( 'woocommerce_checkout_after_order_review', array( Suki_Compatibility_WooCommerce::instance(), 'render_checkout_2_columns_right_wrapper_end' ), 999 );
				
			remove_action( 'woocommerce_checkout_after_order_review', array( Suki_Compatibility_WooCommerce::instance(), 'render_checkout_2_columns_wrapper_end' ), 999 );

		}

	}
}

FluidCheckout_ThemeCompat_Suki::instance();
