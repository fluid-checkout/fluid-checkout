<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Avada Builder (by ThemeFusion).
 */
class FluidCheckout_AvadaBuilder extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct( ) {
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );
		add_filter( 'fc_apply_button_design_styles', '__return_true', 10 );
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Define default color
		$danger_accent_color = '#12b878';

		if ( function_exists( 'awb_get_fusion_settings' ) ) {
			// Get Avada Builder settings
			$plugin_settings = awb_get_fusion_settings();

			// Retrieve danger accent color from the settings
			$danger_accent_color = $plugin_settings->get( 'danger_accent_color' );
		}

		// Add CSS variables
		$new_css_variables = array(
			':root body' => array(
				// Primary button colors
				'--fluidcheckout--button--primary--border-color' => 'var(--button_gradient_top_color)',
				'--fluidcheckout--button--primary--background-color' => 'var(--button_gradient_top_color)',
				'--fluidcheckout--button--primary--text-color' => 'var(--button_accent_color)',
				'--fluidcheckout--button--primary--border-color--hover' => 'var(--button_gradient_top_color_hover)',
				'--fluidcheckout--button--primary--background-color--hover' => 'var(--button_gradient_top_color_hover)',
				'--fluidcheckout--button--primary--text-color--hover' => 'var(--button_accent_hover_color, var(--button_accent_color))',

				// Button design styles
				'--fluidcheckout--button--height' => '50px',
				'--fluidcheckout--button--font-size' => 'var(--button_font_size, 14px)',
				'--fluidcheckout--button--font-weight' => 'var(--button_typography-font-weight)',
				'--fluidcheckout--button--border-width' => 'var(--button_border_width-top, 0)',
				'--fluidcheckout--button--border-radius' => 'var(--button-border-radius-top-left, 0) var(--button-border-radius-top-right, 0) var(--button-border-radius-bottom-right, 0) var(--button-border-radius-bottom-left, 0)',

				// Custom variables
				'--fluidcheckout--avada-builder--danger-accent-color' => $danger_accent_color,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_AvadaBuilder::instance();
