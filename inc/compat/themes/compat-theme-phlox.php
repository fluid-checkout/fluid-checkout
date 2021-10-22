<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Phlox (by averta).
 */
class FluidCheckout_ThemeCompat_Phlox extends FluidCheckout {

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
		// Bail if not on checkout or cart page or doing AJAX call
		if ( ! function_exists( 'is_checkout' ) || ( ! is_checkout() && ! is_cart() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) ) { return; }

		// Bail if use of theme header is enabled
		if ( 'yes' !== get_option( 'fc_hide_site_header_footer_at_checkout', 'yes' ) ) { return; }
		
		wp_dequeue_script( 'auxin-plugins' );
		wp_dequeue_script( 'auxin-scripts' );
	}

}

FluidCheckout_ThemeCompat_Phlox::instance();
