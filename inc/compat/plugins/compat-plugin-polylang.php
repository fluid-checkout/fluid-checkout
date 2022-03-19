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
		// Bail when no polylang function
		if ( ! function_exists( 'PLL' ) ) { return; }

		add_filter( 'fc_checkout_header_logo_home_url', array( $this, 'update_home_url' ), 10, 2 );
	}



	/**
	 * Get home url depend on current lang.
	 *
	 * @return string
	 */
	public function update_home_url() {
		return PLL()->links->get_home_url();
	}

}

FluidCheckout_Polylang::instance();
