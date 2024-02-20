<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Iona (by ThemeREX).
 */
class FluidCheckout_ThemeCompat_Iona extends FluidCheckout {

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

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Button colors from the theme
		$theme_hover_color = '#ecb928';
		$theme_hover_text_color = '#ffffff';

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				'--fluidcheckout--button--primary--border-color--hover' => $theme_hover_color,
				'--fluidcheckout--button--primary--background-color--hover' => $theme_hover_color,
				'--fluidcheckout--button--primary--text-color--hover' => $theme_hover_text_color,

				'--fluidcheckout--button--secondary--border-color--hover' => $theme_hover_color,
				'--fluidcheckout--button--secondary--background-color--hover' => $theme_hover_color,
				'--fluidcheckout--button--secondary--text-color--hover' => $theme_hover_text_color,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Iona::instance();
