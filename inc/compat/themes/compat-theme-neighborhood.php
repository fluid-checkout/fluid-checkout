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
		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Get theme color options
		$alt_bg_color = get_option( 'alt_bg_color', '#f7f7f7' );
		$section_divide_color = get_option( 'section_divide_color', '#e4e4e4' );
		$secondary_accent_color = get_option( 'secondary_accent_color', '#2e2e36' );
		
		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles using theme colors
				'--fluidcheckout--field--background-color' => $alt_bg_color,
				'--fluidcheckout--field--border-color' => $section_divide_color,
				'--fluidcheckout--field--height' => '38px',
				'--fluidcheckout--field--text-color' => $secondary_accent_color,
				'--fluidcheckout--field--font-size' => '16px',
				'--fluidcheckout--field--padding-left' => '10px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Neighborhood::instance();
