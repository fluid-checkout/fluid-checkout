<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WP Armour Extended - Honeypot Anti Spam (by Dnesscarkey).
 */
class FluidCheckout_WPArmourExtended extends FluidCheckout {

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
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Honeypot hooks
		$this->honeypot_hooks();
	}

	/**
	 * Add or remove late hooks.
	 */
	public function honeypot_hooks() {
		// Bail if function is not available
		if ( ! function_exists( 'wpae_woocommerce_add_initiator_field' ) ) { return; }

		// Move honeypot field
		remove_action( 'woocommerce_checkout_billing', 'wpae_woocommerce_add_initiator_field', 10 );
		add_action( 'fc_checkout_before', 'wpae_woocommerce_add_initiator_field', 10 );
	}

}

FluidCheckout_WPArmourExtended::instance();
