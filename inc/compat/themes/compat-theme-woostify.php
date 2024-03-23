<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Woostify (by Woostify).
 */
class FluidCheckout_ThemeCompat_Woostify extends FluidCheckout {

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
		// Default theme color
		$accent_color = '#2b2b2b';

		// Get color value if theme settings function is available
		if ( function_exists( 'woostify_options' ) ) {
			$theme_options = woostify_options();
			$accent_color = $theme_options['button_background_color'];
		}

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '50px',
				'--fluidcheckout--field--padding-left' => '14px',
				'--fluidcheckout--field--border-radius' => '2px',
				'--fluidcheckout--field--border-color' => '#ccc',
				'--fluidcheckout--field--background-color--accent' => $accent_color,

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing--password' => '32px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Woostify::instance();
