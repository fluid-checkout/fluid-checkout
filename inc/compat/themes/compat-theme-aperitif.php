<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Aperitif (by Elated Themes).
 */
class FluidCheckout_ThemeCompat_Aperitif extends FluidCheckout {

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
		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

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
				'--fluidcheckout--field--height' => '54.3px',
				'--fluidcheckout--field--padding-left' => '20px',

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing' => '15px',
				'--fluidcheckout--validation-check--horizontal-spacing--select-alt' => '40px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Aperitif::instance();