<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Wawp Country Code & Chat Widget for WooCommerce (Automation Web Platform) 5.1+.
 */
class FluidCheckout_AutomationWebPlatform extends FluidCheckout {

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
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_replace_country_code_script' ), 20 );
	}



	/**
	 * Replace Wawp country-code script with the FC fork on checkout.
	 */
	public function maybe_replace_country_code_script() {
		// Bail if the upstream script is not registered or enqueued (Wawp 5.1+).
		if ( ! wp_script_is( 'wawp-country-code-script', 'registered' ) && ! wp_script_is( 'wawp-country-code-script', 'enqueued' ) ) {
			return;
		}

		wp_deregister_script( 'wawp-country-code-script' );
		wp_enqueue_script( 'wawp-country-code-script', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/automation-web-platform/block-checkout-intl-tel' ), array( 'wawp-intl-tel-input-js', 'jquery' ), null, true );
	}

}

FluidCheckout_AutomationWebPlatform::instance();
