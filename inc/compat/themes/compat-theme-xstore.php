<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: XStore (By 8theme).
 */
class FluidCheckout_ThemeCompat_XStore extends FluidCheckout {

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
		// Body attributes
		add_filter( 'fc_checkout_body_custom_attributes', array( $this, 'add_body_attributes' ), 10 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
		add_filter( 'fc_content_section_class', array( $this, 'add_content_section_class' ), 10 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );

		// Theme feature: Advanced cart and checkout layout
		add_filter( 'theme_mod_cart_checkout_advanced_layout', '__return_false', 10 );
	}



	/**
	 * Add custom attributes to the body element.
	 *
	 * @param  array  $custom_attributes   Body attributes.
	 */
	public function add_body_attributes( $custom_attributes ) {
		// Bail if theme function is not available
		if ( ! function_exists( 'etheme_get_option' ) ) { return $custom_attributes; }

		// Add dark/light mode attribute
		$mode = etheme_get_option( 'dark_styles', 0 ) ? 'dark' : 'light'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited valid use case
		$custom_attributes[ 'data-mode' ] = $mode;

		return $custom_attributes;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Add CSS variables
		$new_css_variables = array(
			// Buttons
			':root body' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '2.642rem',
				'--fluidcheckout--field--padding-left' => '1.07em',
				'--fluidcheckout--field--border-color' => 'var( --et_inputs-border-color, var( --et_border-color ) )',

				// Primary button colors
				'--fluidcheckout--button--primary--border-color' => 'var( --et_btn-dark-br-color )',
				'--fluidcheckout--button--primary--background-color' => 'var( --et_btn-dark-bg-color )',
				'--fluidcheckout--button--primary--text-color' => 'var( --et_btn-dark-color )',
				'--fluidcheckout--button--primary--border-color--hover' => 'var( --et_btn-dark-br-color-hover )',
				'--fluidcheckout--button--primary--background-color--hover' => 'var( --et_btn-dark-bg-color-hover )',
				'--fluidcheckout--button--primary--text-color--hover' => 'var( --et_btn-dark-color-hover )',

				// Secondary button colors
				'--fluidcheckout--button--secondary--border-color' => 'var( --et_btn-br-color )',
				'--fluidcheckout--button--secondary--background-color' => 'var( --et_btn-bg-color )',
				'--fluidcheckout--button--secondary--text-color' => 'var( --et_btn-color )',
				'--fluidcheckout--button--secondary--border-color--hover' => 'var( --et_btn-br-color-hover )',
				'--fluidcheckout--button--secondary--background-color--hover' => 'var( --et_btn-bg-color-hover )',
				'--fluidcheckout--button--secondary--text-color--hover' => 'var( --et_btn-color-hover )',
			),

			// Dark mode
			':root body[data-mode="dark"]' => FluidCheckout_DesignTemplates::instance()->get_css_variables_dark_mode(),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function add_content_section_class( $class ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $class; }

		// Maybe add the container class
		$class = $class . ' et-container';

		return $class;
	}

}

FluidCheckout_ThemeCompat_XStore::instance();
