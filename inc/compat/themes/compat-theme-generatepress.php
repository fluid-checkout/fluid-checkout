<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: GeneratePress (by Tom Usborne).
 */
class FluidCheckout_ThemeCompat_GeneratePress extends FluidCheckout {

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
		// Get theme settings
		$theme_settings = get_option( 'generate_settings', array() );

		// Default colors
		$border_color = '#ccc';
		$background_color = '#fafafa';

		// Fetch border color from theme settings if exists
		if ( ! empty( $theme_settings['form_border_color'] ) ) {
			$border_color = $theme_settings['form_border_color'];
		}

		// Fetch background color from theme settings if exists
		if ( ! empty( $theme_settings['form_background_color'] ) ) {
			$background_color = $theme_settings['form_background_color'];
		}

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '46.64px',
				'--fluidcheckout--field--padding-left' => '15px',
				'--fluidcheckout--field--border-color' => $border_color,
				'--fluidcheckout--field--background-color' => $background_color,
				'--fluidcheckout--field--background-color--accent' => 'var(--accent)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_GeneratePress::instance();
