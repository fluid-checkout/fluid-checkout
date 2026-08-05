<?php
defined( 'ABSPATH' ) || exit;

/**
 * Apply migrations for the version of the database.
 */
class FluidCheckout_Migration_4_3_0 extends FluidCheckout {

	public const DB_VERSION = '4.3.0';

	/**
	 * Get the database version.
	 */
	public function get_db_version() {
		return self::DB_VERSION;
	}



	/**
	 * Get the list of changes applied by this migration.
	 */
	public function get_description() {
		return array(
			__( 'Copy the order summary background color setting to the new Split design secondary column background color option.', 'fluid-checkout' ),
		);
	}



	/**
	 * Initialize hooks.
	 */
	public function migrate() {
		// Apply migrations
		$this->maybe_copy_order_summary_background_color_to_secondary_column();
	}



	/**
	 * Copy the order summary background color to the Split secondary column background color option.
	 */
	public function maybe_copy_order_summary_background_color_to_secondary_column() {
		// Define option keys
		$order_summary_option_key = 'fc_checkout_order_review_highlight_color';
		$secondary_column_option_key = 'fc_checkout_secondary_column_background_color';

		// Get current values
		// Needs to use `get_option` directly as `FluidCheckout_Settings::get_option()` wrapper function is not available yet
		$order_summary_color = get_option( $order_summary_option_key, null );
		$secondary_column_color = get_option( $secondary_column_option_key, null );

		// Bail if order summary background color is empty
		if ( empty( $order_summary_color ) ) { return; }

		// Bail if Split secondary column background color is already set
		if ( ! empty( $secondary_column_color ) ) { return; }

		// Copy order summary background color to the Split secondary column option
		update_option( $secondary_column_option_key, $order_summary_color );
	}

}

return FluidCheckout_Migration_4_3_0::instance();
