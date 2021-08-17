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
	 * Add or remove late hooks.
	 */
	public function very_late_hooks() {
		// Remove shipping fields from the billing section added by the theme
		// @see themes/astra/inc/compatibility/woocommerce/class-astra-woocommerce.php:LN759
		remove_action( 'woocommerce_checkout_billing', array( WC()->checkout(), 'checkout_form_shipping' ) );
	}

}

FluidCheckout_ThemeCompat_Astra::instance();
