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
		add_action( 'pre_option_fc_enable_checkout_page_template', array( $this, 'maybe_disable_checkout_page_template' ), 10, 3 );
	}



	/**
	 * Maybe disable the checkout page template.
	 *
	 * @param   mixed   $pre_option       Pre-value for the option.
	 * @param   string  $option           Option name.
	 * @param   mixed   $default          The fallback value to return if the option does not exist.
	 */
	public function maybe_disable_checkout_page_template( $pre_option, $option, $default ) {
		// Bail if using the plugin's checkout page header
		if ( FluidCheckout_Steps::instance()->get_hide_site_header_footer_at_checkout() ) { return $pre_option; }
		
		return 'no';
	}

}

FluidCheckout_Oxygen::instance();
