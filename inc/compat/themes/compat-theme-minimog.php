<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Minimog (by ThemeMove).
 */
class FluidCheckout_ThemeCompat_Minimog extends FluidCheckout {

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
		// Scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'deregister_woocommerce_scripts' ), 20 );
		add_action( 'wp_enqueue_scripts', array( FluidCheckout_Enqueue::instance(), 'maybe_replace_woocommerce_scripts' ), 20 );

		// Remove checkout payment info heading
		if ( class_exists( 'Minimog\Woo\Checkout' ) ) {
			remove_action( 'woocommerce_checkout_after_order_review',array( Minimog\Woo\Checkout::instance(), 'template_checkout_payment_title' ),10 );
		}
	}



	/**
	 * Remove WooCommerce scripts.
	 */
	public function deregister_woocommerce_scripts() {
		wp_deregister_script( 'woocommerce' );
		wp_deregister_script( 'wc-country-select' );
		wp_deregister_script( 'wc-address-i18n' );
		wp_deregister_script( 'wc-checkout' );
	} 
}

FluidCheckout_ThemeCompat_Minimog::instance();
