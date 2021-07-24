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
