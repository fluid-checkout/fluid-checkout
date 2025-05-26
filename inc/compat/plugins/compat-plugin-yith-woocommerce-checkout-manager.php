<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: YITH WooCommerce Checkout Manager (by YITH).
 */
class FluidCheckout_YithWooCommerceCheckoutManager extends FluidCheckout {

	private static $thwcfd_public = null;

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
		// Checkout field args
		add_filter( 'fc_checkout_address_i18n_override_locale_required_attribute', '__return_true', 10 );
	}

}

FluidCheckout_YithWooCommerceCheckoutManager::instance();