<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Digits (by UnitedOver).
 */
class FluidCheckout_Digits extends FluidCheckout {

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
		// Login form
		add_filter( 'fc_checkout_login_fields_unique_id', '__return_empty_string', 10 );
	}

}

FluidCheckout_Digits::instance();
