<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Neighborhood.
 */
class FluidCheckout_ThemeCompat_Neighborhood extends FluidCheckout {

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
		// Sticky elements


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
				'--fluidcheckout--field--background-color' => '#f7f7f7',
				'--fluidcheckout--field--border-color' => '#e4e4e4',
				'--fluidcheckout--field--height' => '38px',
				'--fluidcheckout--field--font-color' => '#222',
				'--fluidcheckout--field--font-size' => '14px',
				'--fluidcheckout--field--padding-left' => '10px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Neighborhood::instance();
