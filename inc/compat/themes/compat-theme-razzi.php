<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Razzi (by DrFuri).
 */
class FluidCheckout_ThemeCompat_Razzi extends FluidCheckout {

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
		// Bail if theme Checkout template class is not available
		if ( ! class_exists( '\Razzi\WooCommerce\Template\Checkout' ) ) { return; }

		remove_action( 'woocommerce_before_checkout_form', array( \Razzi\WooCommerce\Template\Checkout::class, 'before_login_form' ), 10 );
		remove_action( 'woocommerce_before_checkout_form', array( \Razzi\WooCommerce\Template\Checkout::class, 'checkout_login_form' ), 10 );
		remove_action( 'woocommerce_before_checkout_form', array( \Razzi\WooCommerce\Template\Checkout::class, 'checkout_coupon_form' ), 10 );
		remove_action( 'woocommerce_before_checkout_form', array( \Razzi\WooCommerce\Template\Checkout::class, 'after_login_form' ), 10 );
	}

}

FluidCheckout_ThemeCompat_Razzi::instance();
