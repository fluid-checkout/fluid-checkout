<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Hello Elementor (by Elementor Team).
 */
class FluidCheckout_ThemeCompat_HelloElementor extends FluidCheckout {

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
				'--fluidcheckout--field--height' => '38.29px',
				'--fluidcheckout--field--padding-left' => '16px',
				'--fluidcheckout--field--border-radius' => '3px',
				'--fluidcheckout--field--border-color' => '#666',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_HelloElementor::instance();
