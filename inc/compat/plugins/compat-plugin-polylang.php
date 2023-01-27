<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Polylang.
 */
class FluidCheckout_Polylang extends FluidCheckout {

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
		add_filter( 'fc_checkout_header_logo_home_url', array( $this, 'update_home_url' ), 10, 2 );
	}



	/**
	 * Get home url depend on current lang.
	 */
	public function update_home_url( $home_url ) {
		// Bail if polylang function is not available
		if ( ! function_exists( 'PLL' ) ) { return $home_url; }

		return PLL()->links->get_home_url();
	}

}

FluidCheckout_Polylang::instance();
