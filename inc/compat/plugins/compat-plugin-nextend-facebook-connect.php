<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Nextend Social Login (by Nextend).
 */
class FluidCheckout_NextendSocialLogin extends FluidCheckout {

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
		
	}

}

FluidCheckout_NextendSocialLogin::instance();
