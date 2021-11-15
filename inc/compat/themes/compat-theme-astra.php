<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Astra (by Brainstorm Force).
 */
class FluidCheckout_ThemeCompat_Astra extends FluidCheckout {

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
		// Bail if not on checkout or cart page or doing AJAX call
		if ( ! function_exists( 'is_checkout' ) || ( ! is_checkout() && ! is_cart() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) ) { return; }

		// Remove shipping fields from the billing section added by the theme
		// @see themes/astra/inc/compatibility/woocommerce/class-astra-woocommerce.php:LN759
		remove_action( 'woocommerce_checkout_billing', array( WC()->checkout(), 'checkout_form_shipping' ) );
	}

}

FluidCheckout_ThemeCompat_Astra::instance();
