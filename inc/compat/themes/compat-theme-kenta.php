<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Kenta (by WP Moose).
 */
class FluidCheckout_ThemeCompat_Kenta extends FluidCheckout {

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
		// Remove theme hooks related to checkout page
		remove_action( 'wp', 'kenta_modify_template_hooks_after_init', 10 );
	}

}

FluidCheckout_ThemeCompat_Kenta::instance();