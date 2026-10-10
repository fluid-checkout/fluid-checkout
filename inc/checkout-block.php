<?php
defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce block editor support.
 */
class FluidCheckout_CheckoutBlock extends FluidCheckout {

	/**
	 * Hold cached values to improve performance.
	 */
	private $cached_values = array();



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
		// Conditionals
		add_filter( 'fc_is_checkout_page_or_fragment', array( $this, 'maybe_set_is_checkout_page_or_fragment' ), 10 );
		add_filter( 'woocommerce_is_checkout', array( $this, 'maybe_set_is_checkout_page_or_fragment' ), 10 );

		// Replace WooCommerce blocks
		// Needs to run right after the blocks are registered (priority 10).
		add_action( 'init', array( $this, 'maybe_replace_checkout_block' ), 11 );

		// Cart Redirect
		add_action( 'template_redirect', array( $this, 'maybe_redirect_empty_cart_checkout' ), 10 );
	}



	/**
	 * Undo hooks.
	 */
	public function undo_hooks() {
		// Conditionals
		remove_filter( 'fc_is_checkout_page_or_fragment', array( $this, 'maybe_set_is_checkout_page_or_fragment' ), 10 );
		remove_filter( 'woocommerce_is_checkout', array( $this, 'maybe_set_is_checkout_page_or_fragment' ), 10 );

		// Replace WooCommerce blocks
		// Needs to run right after the blocks are registered (priority 10).
		remove_action( 'init', array( $this, 'maybe_replace_checkout_block' ), 11 );
	}



	/**
	 * Maybe redirect to the cart page when visiting the checkout with an empty cart.
	 */
	public function maybe_redirect_empty_cart_checkout() {
		global $wp;

		// When on the checkout with an empty cart, redirect to cart page.
		// Intentionally using `is_checkout()` as it then allows to use multiple checkout pages.
		if ( ( is_checkout() || is_page( wc_get_page_id( 'checkout' ) ) ) && wc_get_page_id( 'checkout' ) !== wc_get_page_id( 'cart' ) && WC()->cart->is_empty() && empty( $wp->query_vars['order-pay'] ) && ! isset( $wp->query_vars['order-received'] ) && ! is_customize_preview() && apply_filters( 'woocommerce_checkout_redirect_empty_cart', true ) ) {
			wp_safe_redirect( wc_get_cart_url() );
			exit;
		}
	}



	/**
	 * Maybe set the current request as a checkout page or fragment when the checkout block is present.
	 */
	public function maybe_set_is_checkout_page_or_fragment( $is_checkout_page_or_fragment ) {
		// Get values from cache
		$cache_handle = 'is_checkout_page';
		$return_value = $is_checkout_page_or_fragment;

		// Try to return value from cache
		if ( array_key_exists( $cache_handle, $this->cached_values ) ) {
			// Return value from cache
			return $this->cached_values[ $cache_handle ];
		}
		// Check whether page has the checkout block in the contents
		else if ( function_exists( 'has_block' ) && has_block( 'woocommerce/checkout' ) ) {
			$return_value = true;

			// Set cache
			// once detected a request to the checkout page.
			$this->cached_values[ $cache_handle ] = $return_value;
		}

		return $return_value;
	}



	/**
	 * Replace checkout block.
	 */
	public function maybe_replace_checkout_block() {
		// Bail if block functions are not available.
		if ( ! function_exists( 'unregister_block_type' ) ) { return; }

		// Remove default checkout block.
		$block_name = 'woocommerce/checkout';

		// Maybe deregister block.
		if ( WP_Block_Type_Registry::get_instance()->is_registered( $block_name ) ) {
			unregister_block_type( $block_name );
		}

		// Register block replacement.
		register_block_type( self::$directory_path . 'build/woocommerce/checkout' );
	}

}

FluidCheckout_CheckoutBlock::instance();
