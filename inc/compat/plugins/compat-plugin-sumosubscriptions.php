<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: SUMO Subscriptions (by Funtastic Plugins).
 */
class FluidCheckout_SumoSubscriptions extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct( ) {
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Cart items
		add_filter( 'woocommerce_cart_item_price', array( $this, 'maybe_remove_woocommerce_product_price' ), 1, 3 );
	}



	/**
	 * Maybe remove the WooCommerce product price from the cart item for subscription products.
	 * 
	 * @param  string  $price          The product price.
	 * @param  array   $cart_item      The cart item.
	 * @param  string  $cart_item_key  The cart item key.
	 */
	public function maybe_remove_woocommerce_product_price( $price, $cart_item, $cart_item_key ) {
		// Bail if class not available
		if ( ! class_exists( 'SUMOSubs_Product' ) ) { return $price; }

		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return $price; }

		// Get product
		$product = $cart_item[ 'data' ];

		// Get subscription
		$maybe_subscription = new SUMOSubs_Product( $product );

		// Bail if not a subscription
		if ( ! $maybe_subscription->exists() || ! method_exists( $maybe_subscription, 'is_subscription' ) || ! $maybe_subscription->is_subscription() ) { return $price; }

		// Return empty string
		return '';
	}

}

FluidCheckout_SumoSubscriptions::instance();
