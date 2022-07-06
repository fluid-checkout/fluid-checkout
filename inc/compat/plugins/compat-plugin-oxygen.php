<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Oxygen (by Soflyy).
 */
class FluidCheckout_Oxygen extends FluidCheckout {

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
		add_action( 'pre_option_fc_enable_checkout_page_template', array( $this, 'disable_checkout_page_template' ), 10, 3 );
	}



	/**
	 * [disable_checkout_page_template description]
	 *
	 * @param   mixed   $pre_option       Pre-value for the option.
	 * @param   string  $option           Option name.
	 * @param   mixed   $default          The fallback value to return if the option does not exist.
	 */
	public function disable_checkout_page_template( $pre_option, $option, $default ) {
		return 'no';
	}

}

FluidCheckout_Oxygen::instance();
