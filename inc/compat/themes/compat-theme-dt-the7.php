<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: The7 (by Dream-Theme).
 */
class FluidCheckout_ThemeCompat_DTThe7 extends FluidCheckout {

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
		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
	}

}

FluidCheckout_ThemeCompat_DTThe7::instance();
