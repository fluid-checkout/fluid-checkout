<?php
defined( 'ABSPATH' ) || exit;

/**
 * Plugin deactivation.
 */
class FluidCheckout_Deactivation {

	/**
	 * Run deactivation process.
	 */
	public static function on_deactivation() {
		// Copy WooCommerce and other non-FC profile overrides to wp_options before filters are removed
		if ( class_exists( 'FluidCheckout_Settings' ) ) {
			FluidCheckout_Settings::instance()->sync_active_profile_to_raw_options();
		}

		wp_cache_flush();
	}

}
