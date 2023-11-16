<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Goya (by Everthemes).
 */
class FluidCheckout_ThemeCompat_Goya extends FluidCheckout {

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
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function change_fc_content_section_class( $class ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $class; }

		return $class . ' container';
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '.header-sticky .header';

		return $attributes;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Get theme colors
		$primary_background_color = get_theme_mod( 'primary_buttons', '#282828' );
		$primary_text_color = get_theme_mod( 'primary_buttons_text_color', '#fff' );
		$secondary_background_color = get_theme_mod( 'second_buttons', '#282828' );
		
		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				'--fluidcheckout--button--primary--border-color' => $primary_background_color,
				'--fluidcheckout--button--primary--background-color' => $primary_background_color,
				'--fluidcheckout--button--primary--text-color' => $primary_text_color,
				'--fluidcheckout--button--primary--border-color--hover' => $primary_background_color,
				'--fluidcheckout--button--primary--background-color--hover' => $primary_background_color,
				'--fluidcheckout--button--primary--text-color--hover' => $primary_text_color,

				'--fluidcheckout--button--secondary--border-color' => 'currentColor',
				'--fluidcheckout--button--secondary--background-color' => 'transparent',
				'--fluidcheckout--button--secondary--text-color' => 'currentColor',
				'--fluidcheckout--button--secondary--border-color--hover' => $primary_background_color,
				'--fluidcheckout--button--secondary--background-color--hover' => 'transparent',
				'--fluidcheckout--button--secondary--text-color--hover' => $primary_background_color,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Goya::instance();
