<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Avanam (by QantumThemes).
 */
class FluidCheckout_ThemeCompat_Avanam extends FluidCheckout {

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
		// add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		// add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}



	// /**
	//  * Change the sticky element relative ID.
	//  *
	//  * @param   array   $attributes    HTML element attributes.
	//  */
	// public function change_sticky_elements_relative_header( $attributes ) {
	// 	// Bail if using distraction free header and footer
	// 	if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

	// 	$attributes['data-sticky-relative-to'] = '.site-header-row-container-inner';

	// 	return $attributes;
	// }



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Get theme colors
		// $primary_color = get_theme_mod( 'avanam_color_accent', '#00ced0' );
		// $primary_color_hover = get_theme_mod( 'avanam_color_accent_hover', '#00fcff' );
		// $text_color = get_theme_mod( 'avanam_textcolor_on_buttons', '#fff' );

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// ! Check for theme variables, but looks good so far
				// Form field styles
				'--fluidcheckout--field--height' => '35px',
				'--fluidcheckout--field--padding-left' => '14px',
				'--fluidcheckout--field--font-size' => '15px',
				'--fluidcheckout--field--border-radius' => '3px',
				'--fluidcheckout--field--border-color' => 'var(--global-gray-400)',

				// // Primary button colors
				// '--fluidcheckout--button--primary--border-color' => $primary_color,
				// '--fluidcheckout--button--primary--background-color' => $primary_color,
				// '--fluidcheckout--button--primary--text-color' => $text_color,
				// '--fluidcheckout--button--primary--border-color--hover' => $primary_color_hover,
				// '--fluidcheckout--button--primary--background-color--hover' => $primary_color_hover,
				// '--fluidcheckout--button--primary--text-color--hover' => $text_color,

				// // Secondary button color
				// '--fluidcheckout--button--secondary--border-color' => $primary_color,
				// '--fluidcheckout--button--secondary--background-color' => $primary_color,
				// '--fluidcheckout--button--secondary--text-color' => $text_color,
				// '--fluidcheckout--button--secondary--border-color--hover' => $primary_color_hover,
				// '--fluidcheckout--button--secondary--background-color--hover' => $primary_color_hover,
				// '--fluidcheckout--button--secondary--text-color--hover' => $text_color,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Avanam::instance();
