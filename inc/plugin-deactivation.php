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
		// Copy the active settings profile back to individual options before filters are removed
		if ( class_exists( 'FluidCheckout_Settings' ) ) {
			FluidCheckout_Settings::instance()->sync_active_profile_to_raw_options();
		}

		wp_cache_flush();
	}

}
