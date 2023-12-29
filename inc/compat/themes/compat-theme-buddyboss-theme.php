<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: BuddyBoss theme.
 */
class FluidCheckout_ThemeCompat_BuddyBoss extends FluidCheckout {

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
		// Dequeue
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_dequeue_scripts' ), 100 );
	}



	/**
	 * Dequeue theme scripts unnecessary on checkout page and that interfere with Fluid Checkout scripts.
	 */
	public function maybe_dequeue_scripts() {
		// Bail if not on checkout page
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return; }
		
		wp_dequeue_script( 'buddyboss-theme-woocommerce-js' );
	}

}

FluidCheckout_ThemeCompat_BuddyBoss::instance();
