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
		add_action( 'woocommerce_before_checkout_form', array( $this, 'maybe_add_birthday_field_hook' ), 20 );
	}

	/**
	 * Add the birthday field to fc_checkout_after_step_billing_fields when
	 * AutomateWoo Birthdays placement is set to "After billing details".
	 */
	public function maybe_add_birthday_field_hook() {
		// Bail if AutomateWoo Birthdays is not available
		if ( ! function_exists( 'AW_Birthdays' ) ) {
			return;
		}

		// Bail if placement is not "after billing details"
		if ( 'after_billing_details' !== AW_Birthdays()->options()->checkout_field_placement() ) {
			return;
		}

		add_action( 'fc_checkout_after_step_billing_fields', array( $this, 'output_birthday_field' ), 10 );
	}

	/**
	 * Output the AutomateWoo birthday field.
	 */
	public function output_birthday_field() {
		if ( ! function_exists( 'AW_Birthdays' ) ) {
			return;
		}

		\AutomateWoo\Birthdays\Frontend::add_birthday_field_to_checkout_form();
	}
}

FluidCheckout_AutomateWooBirthdays::instance();
