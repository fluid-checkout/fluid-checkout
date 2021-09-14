<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Germanized for WooCommerce (by vendidero).
 */
class FluidCheckout_WooCommerceGermanized extends FluidCheckout {

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

		if ( function_exists( 'wc_gzd_get_hook_priority' ) ) {
			
			// Remove payment title heading
			remove_action( 'woocommerce_review_order_before_payment', 'woocommerce_gzd_template_checkout_payment_title', 10 );
			
			// Remove extraneous payment section from order summary
			remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 10 );

			// Remove checkout checkboxes
			remove_action( 'woocommerce_review_order_after_payment', 'woocommerce_gzd_template_render_checkout_checkboxes', 10 );
			remove_action( 'woocommerce_review_order_after_payment', 'woocommerce_gzd_template_checkout_set_terms_manually', wc_gzd_get_hook_priority( 'checkout_set_terms' ) );
			
			// Move checkout checkboxes
			add_action( 'woocommerce_checkout_before_order_review', 'woocommerce_gzd_template_render_checkout_checkboxes', 10 );
			add_action( 'woocommerce_checkout_before_order_review', 'woocommerce_gzd_template_checkout_set_terms_manually', 20 );
		
			// remove_action( 'woocommerce_checkout_order_review', 'woocommerce_gzd_template_order_submit', wc_gzd_get_hook_priority( 'checkout_order_submit' ) );
			// remove_action( 'woocommerce_checkout_order_review', 'woocommerce_gzd_template_order_submit', wc_gzd_get_hook_priority( 'checkout_order_submit' ) );
		}

	}

}

FluidCheckout_WooCommerceGermanized::instance();
