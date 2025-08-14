<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Sahel (by Elated-Themes).
 */
class FluidCheckout_ThemeCompat_Sahel extends FluidCheckout {

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
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}



	/**
	 * Change the element used to position the progress bar and order summary when sticky.
	 * 
	 * @param  array  $attributes  The elements attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '.eltdf-fixed-wrapper';

		return $attributes;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Get the theme's main color
		$first_main_color = '';
		if ( function_exists( 'sahel_elated_options' ) ) {
			$first_main_color = sahel_elated_options()->getOptionValue( 'first_color' );
		}

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '55.4427px',
				'--fluidcheckout--field--padding-left' => '5px',
				'--fluidcheckout--field--border-radius' => '0',
				'--fluidcheckout--field--border' => '0px',
				'--fluidcheckout--field--border-color' => 'transparent',
				'--fluidcheckout--field--font-size' => '11px',

				// Theme main color
				'--theme--first-color' => $first_main_color ? $first_main_color : '#000', // For border bottom color of select2 fields
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Sahel::instance();
