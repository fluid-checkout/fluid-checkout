<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: TI WooCommerce Wishlist Premium (by TemplateInvaders).
 */
class FluidCheckout_TIWooCommerceWishlistPremium extends FluidCheckout {

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
		// Cart item elements
		add_filter( 'tinvwl_wishlist_reference_string', array( $this, 'add_cart_item_element_class_to_reference_string' ), 10 );
	}



	/**
	 * Add cart item element class to wishlist reference string.
	 */
	public function add_cart_item_element_class_to_reference_string( $reference_string ) {
		return str_replace( '<p>', '<p class="cart-item__element">', $reference_string );
	}

}

FluidCheckout_TIWooCommerceWishlistPremium::instance(); 
