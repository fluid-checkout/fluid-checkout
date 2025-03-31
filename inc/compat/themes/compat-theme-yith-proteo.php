<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: YITH Proteo (by YITH).
 */
class FluidCheckout_ThemeCompat_YithProteo extends FluidCheckout {

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
				'--fluidcheckout--field--height' => '40.4px',
				'--fluidcheckout--field--padding-left' => '10px',
				'--fluidcheckout--field--font-size' => 'var(--proteo-forms_input_font_size)',
				'--fluidcheckout--field--border-color' => 'var(--proteo-forms_input_border_color, #cccccc)',
				'--fluidcheckout--field--border-width' => 'var(--proteo-forms_input_border_width)',
				'--fluidcheckout--field--border-radius' => 'var(--proteo-forms_input_borde_radius, 0)',
				'--fluidcheckout--field--background-color--accent' => 'transparent',
				'--fluidcheckout--field--text-color--accent' => 'var(--proteo-main_color_shade, #448a85)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_YithProteo::instance();