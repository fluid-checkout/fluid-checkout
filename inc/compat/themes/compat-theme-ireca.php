<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Ireca (by Envato).
 */
class FluidCheckout_ThemeCompat_Ireca extends FluidCheckout {

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
		// add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}



	/**
	 * Change the element used to position the progress bar and order summary when sticky.
	 * 
	 * @param  array  $attributes  The elements attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// For Ireca theme, use the .ovamenu_shrink element for sticky positioning
		$attributes['data-sticky-relative-to'] = '.ovamenu_shrink';

		return $attributes;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Get Ireca theme's main color
		$main_color = get_theme_mod( 'main_color', '#e9a31b' );
		
		// Add CSS variables for Ireca theme
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '50px',
				'--fluidcheckout--field--padding-left' => '15px',
				'--fluidcheckout--field--border-radius' => '5px',
				'--fluidcheckout--field--background-color--accent' => $main_color,

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing--select' => '20px',
				'--fluidcheckout--validation-check--horizontal-spacing--select-alt' => '32px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Ireca::instance();
