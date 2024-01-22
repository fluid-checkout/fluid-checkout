<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: PressMart (by PressLayouts).
 */
class FluidCheckout_ThemeCompat_PressMart extends FluidCheckout {

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
		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );
		
		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Checkout page hooks
		$this->checkout_hooks();
	}



	/*
	* Add or remove checkout page hooks.
	*/
	public function checkout_hooks() {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function change_fc_content_section_class( $class ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $class; }

		return $class . ' col-12';
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Bail if theme function is not available
		if ( ! function_exists( 'pressmart_get_option' ) ) { return $css_variables; }

		// Get theme colors
		$primary_button_color = pressmart_get_option( 'checkout-button-background', array(
			'regular' 	=> '#9e7856',
			'hover' 	=> '#ae8866',
		) );
		$primary_text_color = pressmart_get_option( 'checkout-button-color', array(
			'regular' 	=> '#ffffff',
			'hover' 	=> '#fcfcfc',
		) );
		$secondary_button_color = pressmart_get_option( 'button-background', array(
			'regular' 	=> '#059473',
			'hover' 	=> '#048567',
		) );
		$secondary_text_color = pressmart_get_option( 'button-color', array(
			'regular' 	=> '#ffffff',
			'hover' 	=> '#fcfcfc',
		) );

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				'--fluidcheckout--button--primary--border-color' => $primary_button_color['regular'],
				'--fluidcheckout--button--primary--background-color' => $primary_button_color['regular'],
				'--fluidcheckout--button--primary--text-color' => $primary_text_color['regular'],
				'--fluidcheckout--button--primary--border-color--hover' => $primary_button_color['hover'],
				'--fluidcheckout--button--primary--background-color--hover' => $primary_button_color['hover'],
				'--fluidcheckout--button--primary--text-color--hover' => $primary_text_color['hover'],

				'--fluidcheckout--button--secondary--border-color' => $secondary_button_color['regular'],
				'--fluidcheckout--button--secondary--background-color' => $secondary_button_color['regular'],
				'--fluidcheckout--button--secondary--text-color' => $secondary_text_color['regular'],
				'--fluidcheckout--button--secondary--border-color--hover' => $secondary_button_color['hover'],
				'--fluidcheckout--button--secondary--background-color--hover' => $secondary_button_color['hover'],
				'--fluidcheckout--button--secondary--text-color--hover' => $secondary_text_color['hover'],
			),
		);
		
		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_PressMart::instance();
