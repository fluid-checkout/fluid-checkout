<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: CartFlows (by CartFlows).
 */
class FluidCheckout_Cartflows extends FluidCheckout {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Register hooks.
	 */
	public function hooks() {
		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Remove CartFlows filters that modify the checkout fields.
		$this->remove_checkout_field_hooks();
	}

	/**
	 * Remove CartFlows filters that modify the checkout fields.
	 */
	public function remove_checkout_field_hooks() {
		if ( ! class_exists( 'Cartflows_Checkout_Markup' ) ) {
			return;
		}

		$checkout_markup = Cartflows_Checkout_Markup::get_instance();

		// ! Double check if disabling these wont have any side effects
		remove_action( 'wp', array( $checkout_markup, 'shortcode_load_data' ), 999 );
		remove_shortcode( 'cartflows_checkout' );
		remove_filter( 'woocommerce_cart_item_name', array( $checkout_markup, 'modify_order_review_item_summary' ), 10 );
		remove_action( 'woocommerce_before_calculate_totals', array( $checkout_markup, 'custom_price_to_cart_item' ), 9999 );
		remove_filter( 'woocommerce_update_order_review_fragments', array( $checkout_markup, 'add_updated_cart_price' ), 10 );
		remove_action( 'cartflows_checkout_scripts', array( $checkout_markup, 'load_google_places_library' ), 10 );
	}
}

FluidCheckout_Cartflows::instance();

