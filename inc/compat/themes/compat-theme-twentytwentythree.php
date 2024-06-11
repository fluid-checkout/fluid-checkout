<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Twenty Twenty-Three (by The WordPress Team).
 */
class FluidCheckout_ThemeCompat_TwentyTwentyThree extends FluidCheckout {

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
		// Order summary
		$this->order_summary_hooks();
	}

	/**
	 * Add or remove hooks for the checkout order summary.
	 */
	public function order_summary_hooks() {
		// Bail if not on the checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

          // Bail if theme classes or functions not available
		if ( ! class_exists( 'WC_Twenty_Twenty_Three' ) ) { return; }

		// Remove hooks
		remove_action( 'woocommerce_checkout_before_order_review_heading', array( 'WC_Twenty_Twenty_Three', 'before_order_review' ) );
		remove_action( 'woocommerce_checkout_after_order_review', array( 'WC_Twenty_Twenty_Three', 'after_order_review' ) );
	}
}

FluidCheckout_ThemeCompat_TwentyTwentyThree::instance();
