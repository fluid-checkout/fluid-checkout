<?php
defined( 'ABSPATH' ) || exit;

/**
 * Plugin activation.
 */
class FluidCheckout_Activation {

	/**
	 * Run activation process.
	 */
	public static function on_activation() {
		self::set_first_activation_time_option();

		// Drop a leftover schedule without changing the saved choice
		if ( class_exists( 'FC_Telemetry_Client' ) && FC_Telemetry_Client::is_disabled_by_constant() ) {
			FC_Telemetry_Client::unschedule_telemetry_cron( FluidCheckout::get_telemetry_api_url() );
		}

		wp_cache_flush();
	}



	/**
	 * Set the activation time option.
	 */
	public static function set_first_activation_time_option() {
		// Save activation time option
		$get_activation_time = strtotime( 'now' );
		add_option( 'fc_plugin_activation_time', $get_activation_time );
	}

}
