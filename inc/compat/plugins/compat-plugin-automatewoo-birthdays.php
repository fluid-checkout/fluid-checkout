<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: AutomateWoo - Birthdays Add-on.
 */
class FluidCheckout_AutomateWooBirthdays extends FluidCheckout {

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
		// Use filter to redirect birthday field placement to Fluid Checkout hooks (prevents duplicate fields)
		add_filter( 'automatewoo/birthdays/checkout_field_placement', array( $this, 'maybe_change_checkout_field_placement' ), 10, 1 );
	}

	/**
	 * Change the checkout field placement hook to Fluid Checkout's billing step hook when placement is "After billing details".
	 *
	 * @param string|false $hook The hook name where the birthday field should be output.
	 * @return string|false The hook name.
	 */
	public function maybe_change_checkout_field_placement( $hook ) {
		// Bail if AutomateWoo Birthdays is not available
		if ( ! function_exists( 'AW_Birthdays' ) ) { return $hook; }

		// Bail if not target placement
		if ( 'after_billing_details' !== AW_Birthdays()->options()->checkout_field_placement() ) { return $hook; }

		// Return modified hook
		return 'fc_checkout_after_step_billing_fields';
	}
}

FluidCheckout_AutomateWooBirthdays::instance();
