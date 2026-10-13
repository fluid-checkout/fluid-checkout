<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce Buy One Get One Free (by Oscar Gare).
 */
class FluidCheckout_WooCommerceBuyOneGetOneFree extends FluidCheckout {

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
		// Product appearance
		add_filter( 'wc_bogof_cart_template_item_price', array( $this, 'change_product_price_display' ), 100, 2 );
	}


	/**
	 * Filter the "Free!" text displayed for free items.
	 */
	public function change_product_price_display( $price, $cart_item ) {
		// Bail if not on cart or checkout page
		if ( ! FluidCheckout_Steps::instance()->is_cart_page_or_fragment() && ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return $price; }

		// Bail if cart item price is not free
		if ( $cart_item['data']->get_price() > 0 ) { return $price; }

		// Add cart item flag class
		$price = str_replace( '<span>', '<span class="cart-item__notification-message cart-item__notification-message--success">', $price );

		return $price;
	}

}

FluidCheckout_WooCommerceBuyOneGetOneFree::instance();
