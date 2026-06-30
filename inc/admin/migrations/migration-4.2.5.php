<?php
defined( 'ABSPATH' ) || exit;

/**
 * Apply migrations for the version of the database.
 */
class FluidCheckout_Migration_4_2_5 extends FluidCheckout {

	public const DB_VERSION = '4.2.5';

	/**
	 * Get the database version.
	 */
	public function get_db_version() {
		return self::DB_VERSION;
	}



	/**
	 * Initialize hooks.
	 */
	public function migrate() {
		// Apply migrations
		$this->maybe_update_shipping_phone_field_visibility_option();
	}



	/**
	 * Update the shipping phone field visibility option value to match WooCommerce conventions.
	 */
	public function maybe_update_shipping_phone_field_visibility_option() {
		// Define option key
		$option_key = 'fc_shipping_phone_field_visibility';

		// Get current value
		// Needs to use `get_option` directly as `FluidCheckout_Settings::get_option()` wrapper function is not available yet
		$option_value = get_option( $option_key, 'hidden' );

		// Bail if current value is not set to legacy value `no`.
		if ( 'no' !== $option_value ) { return; }

		// Update option
		update_option( $option_key, 'hidden' );
	}

}

return FluidCheckout_Migration_4_2_5::instance();
