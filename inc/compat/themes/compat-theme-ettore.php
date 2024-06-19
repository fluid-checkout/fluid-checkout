<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Ettore (by Mikado Themes - Qode Themes).
 */
class FluidCheckout_Ettore extends FluidCheckout {

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
		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );
		add_filter( 'fc_apply_button_design_styles', '__return_true', 10 );

		add_filter( 'fc_next_step_button_classes', array( $this, 'add_button_class' ), 10 );
		add_filter( 'fc_substep_save_button_classes', array( $this, 'add_button_class' ), 10 );
		add_filter( 'fc_coupon_code_apply_button_classes', array( $this, 'add_button_class' ), 10 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
	}



	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Remove container with ID `qodef-woo-page` added by the plugin
		remove_action( 'woocommerce_before_checkout_form', 'ettore_add_main_woo_page_holder', 5 );
		remove_action( 'woocommerce_after_checkout_form', 'ettore_add_main_woo_page_holder_end', 20 );

		// Re-add Woocommerce stylesheet
		remove_filter( 'woocommerce_enqueue_styles', '__return_false' );
	}



	/**
	 * Add button class from the theme.
	 * 
	 * @param  array  $classes  The button classes.
	 */
	public function add_button_class( $classes ) {
		// Add 'qodef-theme-button' class to apply theme styles and 'qodef-m-checkout-link' to add hover animation
		if ( is_array( $classes ) ) {
			array_push( $classes, 'qodef-theme-button', 'qodef-m-checkout-link' );
		} 
		else {
			$classes .= ' qodef-theme-button qodef-m-checkout-link';
		}

		return $classes;
	}



	/**

	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Theme color values
		$button_text_color = 'var(--qode-button-color, #fff)';
		$button_background_color = 'var(--qode-button-bg-color, #000)';

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '50.14px',
				'--fluidcheckout--field--border-color' => '#ccc',
				'--fluidcheckout--field--padding-left' => '20px',
				'--fluidcheckout--field--font-size' => '14px',
				'--fluidcheckout--field--background-color--accent' => 'var(--qode-main-color, #13161a)',

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing--select-alt' => '35px',

				// Primary button color
				'--fluidcheckout--button--primary--border-color' => $button_background_color,
				'--fluidcheckout--button--primary--background-color' => $button_background_color,
				'--fluidcheckout--button--primary--text-color' => $button_text_color,
				'--fluidcheckout--button--primary--border-color--hover' => $button_background_color,
				'--fluidcheckout--button--primary--background-color--hover' => $button_background_color,
				'--fluidcheckout--button--primary--text-color--hover' => $button_text_color,

				// Secondary button color
				'--fluidcheckout--button--secondary--border-color' => $button_background_color,
				'--fluidcheckout--button--secondary--background-color' => $button_background_color,
				'--fluidcheckout--button--secondary--text-color' => $button_text_color,
				'--fluidcheckout--button--secondary--border-color--hover' => 'var(--qode-button-border-color, var(--qode-main-color))',
				'--fluidcheckout--button--secondary--background-color--hover' => 'transparent',
				'--fluidcheckout--button--secondary--text-color--hover' => 'var(--qode-button-outlined-color, var(--qode-main-color))',

				// Button design styles
				'--fluidcheckout--button--height' => '51.14px',
				'--fluidcheckout--button--font-size' => '12px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_Ettore::instance();
