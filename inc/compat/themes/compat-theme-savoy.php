<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Savoy (by NordicMade).
 */
class FluidCheckout_ThemeCompat_Savoy extends FluidCheckout {

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
		global $nm_theme_options;

		// Defaul color value
		$dropdown_background_color = "#282828";

		if ( isset( $nm_theme_options['dropdown_menu_background_color'] ) && ! empty( $nm_theme_options['dropdown_menu_background_color'] ) ) {
			$dropdown_background_color = $nm_theme_options['dropdown_menu_background_color'];
		}

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '39.54px',
				'--fluidcheckout--field--padding-left' => '10px',
				'--fluidcheckout--field--border-color' => '#e1e1e1',
				'--fluidcheckout--field--font-size' => '14px',
				'--fluidcheckout--field--background-color--accent' => $dropdown_background_color,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Savoy::instance();
