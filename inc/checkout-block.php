<?php
defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce block editor support.
 */
class FluidCheckout_CheckoutBlock extends FluidCheckout {

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
	 * Maybe set the current request as a checkout page or fragment when the checkout block is present.
	 */
	public function maybe_set_is_checkout_page_or_fragment( $is_checkout_page_or_fragment ) {
		// Bail if block functions are not available.
		if ( ! function_exists( 'has_block' ) ) { return $is_checkout_page_or_fragment; }

		// Bail if current page does not have the WooCommerce Checkout block in the contents
		if ( ! has_block( 'woocommerce/checkout' ) ) { return $is_checkout_page_or_fragment; }

		return true;
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
