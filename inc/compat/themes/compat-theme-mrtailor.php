<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Mr. Tailor (by Get Bowtied).
*/
class FluidCheckout_ThemeCompat_MrTailor extends FluidCheckout {

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
		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '45px',
				'--fluidcheckout--field--padding-left' => '20px',
				'--fluidcheckout--field--border-radius' => '3px',

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing--select' => '32px',
				'--fluidcheckout--validation-check--horizontal-spacing--select-alt' => '32px',
				'--fluidcheckout--validation-check--horizontal-spacing--password' => '32px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_MrTailor::instance();
