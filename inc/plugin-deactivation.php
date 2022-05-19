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
		wp_cache_flush();
	}

}
