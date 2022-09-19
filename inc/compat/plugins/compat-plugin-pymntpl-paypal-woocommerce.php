<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Payment Plugins for PayPal WooCommerce (by Payment Plugins).
 */
class FluidCheckout_PymntplPayPalWooCommerce extends FluidCheckout {

	public $smart_button_module;

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
		add_action( 'woocommerce_review_order_after_submit', array( $this, 'output_always_replace_fragment_hidden_field' ), 10 );
	}



	/**
	 * Output hidden field to tell checkout script to always replace the place order fragment.
	 */
	public function output_always_replace_fragment_hidden_field() {
		// Bail if Pay Later message is not being displayed in the place order section
		// var_dump( 'passed 1' );
		// var_dump( get_option( 'woocommerce_ppcp_paylater_message_checkout_enabled' ) );
		// var_dump( get_option( 'woocommerce_ppcp_paylater_message_checkout_location' ) );
		// die();

		if ( ! wc_string_to_bool( get_option( 'woocommerce_ppcp_paylater_message_checkout_enabled' ) ) || 'shop_table' !== get_option( 'woocommerce_ppcp_paylater_message_checkout_location' ) ) { return; }

		echo '<input type="hidden" class="fc-fragment-always-replace" />';
	}

}

FluidCheckout_PymntplPayPalWooCommerce::instance();
