<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Synex (by Gramentheme).
 */
class FluidCheckout_ThemeCompat_Synex extends FluidCheckout {

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

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// CSS variables
		add_filter( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );
		add_filter( 'fc_apply_button_design_styles', '__return_true', 10 );
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array  $attributes  HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '.header-section';

		return $attributes;
	}



	/**
	 * Add CSS variables.
	 *
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				// Theme sets global `input { color: var(--white) }`, which makes field text unreadable on light backgrounds.
				'--fluidcheckout--field--text-color' => 'var( --header, #15171A )',
				// Match Select2/TomSelect height, padding and font-size to text inputs (theme body is 18px).
				'--fluidcheckout--field--height' => '42px',
				'--fluidcheckout--field--padding-left' => '9px',
				'--fluidcheckout--field--font-size' => '18px',
				// Match WooCommerce form fields.
				'--fluidcheckout--field--border-radius' => 'var( --wc-form-border-radius, 4px )',
				'--fluidcheckout--field--border-color' => 'var( --wc-form-border-color, rgba(32, 7, 7, 0.8) )',
				'--fluidcheckout--field--background-color--accent' => 'var( --theme, #007BFF )',

				// Button design styles
				'--fluidcheckout--button--height' => '42px',
				'--fluidcheckout--button--border-radius' => '8px',
				'--fluidcheckout--button--primary--background-color' => 'var( --theme, #007BFF )',
				'--fluidcheckout--button--primary--border-color' => 'var( --theme, #007BFF )',
				'--fluidcheckout--button--primary--text-color' => '#fff',
				// Match theme `.theme-btn-main:hover .theme-btn { background-color: var(--header) }`.
				'--fluidcheckout--button--primary--background-color--hover' => 'var( --header, #15171A )',
				'--fluidcheckout--button--primary--border-color--hover' => 'var( --header, #15171A )',
				'--fluidcheckout--button--primary--text-color--hover' => '#fff',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Synex::instance();
