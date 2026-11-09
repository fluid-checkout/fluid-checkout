<?php
defined( 'ABSPATH' ) || exit;

/**
 * Apply migrations for the version of the database.
 */
class FluidCheckout_Migration_3_0_5 extends FluidCheckout {

	public const DB_VERSION = '3.0.5';

	/**
	 * Get the database version.
	 */
	public function get_db_version() {
		return self::DB_VERSION;
	}



	/**
	 * Get ordered migration steps for this version.
	 */
	public function get_migration_steps() {
		return array(
			array(
				'id'          => 'update_billing_phone_visibility',
				'description' => __( 'Update the billing phone field visibility option from the legacy value to match WooCommerce conventions.', 'fluid-checkout' ),
				'risk'        => FluidCheckout_AdminDBMigrations::RISK_LOW,
				'callback'    => array( $this, 'maybe_update_billing_field_visibility_option' ),
			),
		);
	}



	/**
	 * Update the billing phone field visibility option.
	 */
	public function maybe_update_billing_field_visibility_option() {
		// Define option key
		$option_key = 'woocommerce_checkout_phone_field';

		// Get current value
		// Needs to use `get_option` directly as `FluidCheckout_Settings::get_option()` wrapper function is not available yet
		$option_value = get_option( $option_key, 'required' );

		// Bail if current value is not set to `no`.
		if ( 'no' !== $option_value ) { return; }

		// Update option
		update_option( $option_key, 'hidden' );
	}

}

return FluidCheckout_Migration_3_0_5::instance();
