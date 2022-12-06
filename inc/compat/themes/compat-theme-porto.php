<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Porto (by Brainstorm Force).
 */
class FluidCheckout_ThemeCompat_Porto extends FluidCheckout {

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
		add_filter( 'fc_add_container_class', '__return_false' );
	}

}

FluidCheckout_ThemeCompat_Porto::instance();
