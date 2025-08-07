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

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Buttons
		add_filter( 'fc_next_step_button_classes', array( $this, 'add_button_class' ), 10 );
		add_filter( 'fc_substep_save_button_classes', array( $this, 'add_button_class' ), 10 );
		add_filter( 'fc_coupon_code_apply_button_classes', array( $this, 'add_button_class' ), 10 );

		// Apply button colors and design styles
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );
		add_filter( 'fc_apply_button_design_styles', '__return_true', 10 );
	}



	/**
	 * Add button class from the theme.
	 * 
	 * @param  array  $classes  The button classes.
	 */
	public function add_button_class( $classes ) {
		// Add 'alt' class to apply theme styles
		if ( is_array( $classes ) ) {
			array_push( $classes, 'alt');
		} 
		else {
			$classes .= ' alt';
		}

		return $classes;
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
		$secondary_accent_alt_color = get_option( 'secondary_accent_alt_color', '#ffffff' );
		$body_text_color = get_option( 'body_color', '#222222' );
		$accent_color = get_option( 'accent_color', '#07c1b6' );
		
		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles using theme colors
				'--fluidcheckout--field--background-color' => $alt_bg_color,
				'--fluidcheckout--field--border-color' => $section_divide_color,
				'--fluidcheckout--field--height' => '38px',
				'--fluidcheckout--field--text-color' => $body_text_color,
				'--fluidcheckout--field--font-size' => '15px',
				'--fluidcheckout--field--padding-left' => '10px',

				// Button styles
				'--fluidcheckout--button--border-radius' => '4px',
				'--fluidcheckout--button--primary--background-color' => $secondary_accent_color,
				'--fluidcheckout--button--primary--text-color' => $secondary_accent_alt_color,
				'--fluidcheckout--button--primary--border-color' => $secondary_accent_color,
				'--fluidcheckout--button--primary--background-color--hover' => $accent_color,
				'--fluidcheckout--button--primary--text-color--hover' => $secondary_accent_alt_color,
				'--fluidcheckout--button--primary--border-color--hover' => $accent_color,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}



	/**
	 * Change the element used to position the progress bar and order summary when sticky.
	 * 
	 * @param  array  $attributes  The elements attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Get theme options
		$options = get_option('sf_neighborhood_options');

		// Bail if theme options are not available
		if ( ! $options || ! array_key_exists( 'enable_mini_header', $options ) ) { return $attributes; }

		// Bail if mini header option is disabled in the theme
		if ( ! $options[ 'enable_mini_header' ] ) { return $attributes; }

		$attributes[ 'data-sticky-relative-to' ] = '#mini-header';

		return $attributes;
	}

}

FluidCheckout_ThemeCompat_Neighborhood::instance();
