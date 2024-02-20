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

		// Site header sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 30 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 30 );
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



	/**
	 * Change the relative selector for sticky elements.
	 *
	 * @param   array  $attributes  The element HTML attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = 'header .sc_layouts_row_fixed_on';

		return $attributes;
	}

}

FluidCheckout_ThemeCompat_Iona::instance();