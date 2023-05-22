<?php
defined( 'ABSPATH' ) || exit;

/**
 * Manage the design templates.
 */
class FluidCheckout_DesignTemplates extends FluidCheckout {

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
		// General
		add_filter( 'body_class', array( $this, 'add_body_class_dark_mode' ), 10 );

		// CSS variables
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_dark_mode_css_variables' ), 10 );
	}



	/**
	 * Undo hooks.
	 */
	public function undo_hooks() {
		// General
		remove_filter( 'body_class', array( $this, 'add_body_class_dark_mode' ), 10 );

		// CSS variables
		remove_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_dark_mode_css_variables' ), 10 );
	}



	/**
	 * Add page body class for feature detection.
	 *
	 * @param array $classes Classes for the body element.
	 */
	public function add_body_class_dark_mode( $classes ) {
		// Bail if not on affected pages.
		if (
			! function_exists( 'is_checkout' )
			|| (
				! is_checkout() // Checkout page
				&& ! is_wc_endpoint_url( 'add-payment-method' ) // Add payment method page
				&& ! is_wc_endpoint_url( 'edit-address' ) // Edit address page
			)
		) { return $classes; }

		// Bail if dark mode is not enabled
		if ( 'yes' !== get_option( 'fc_enable_dark_mode_styles', 'no' ) ) { return $classes; }

		// Add dark mode class
		$classes[] = 'has-fc-dark-mode';

		return $classes;
	}



	/**
	 * Get the design template option arguments.
	 *
	 * @return  array  Design templates arguments.
	 */
	public function get_design_template_options() {
		return array(
			'classic'     => array( 'label' => __( 'Classic', 'fluid-checkout' ) ),
			'modern'      => array( 'label' => __( 'Modern', 'fluid-checkout' ), 'disabled' => true ),
			'minimalist'  => array( 'label' => __( 'Minimalist', 'fluid-checkout' ), 'disabled' => true ),
		);
	}

	/**
	 * Return the list of values accepted for design templates.
	 *
	 * @return  array  List of values accepted for design templates.
	 */
	public function get_allowed_design_templates() {
		return array_keys( $this->get_design_template_options() );
	}




	/**
	 * Get CSS variables styles.
	 */
	public function get_dark_mode_css_variables_styles() {
		// Define CSS variables
		$css_variables = "
			:root body {
				--fluidcheckout--color--black: #fff;
				--fluidcheckout--color--darker-grey: #f3f3f3;
				--fluidcheckout--color--dark-grey: #d8d8d8;
				--fluidcheckout--color--grey: #7b7575;
				--fluidcheckout--color--light-grey: #323234;
				--fluidcheckout--color--lighter-grey: #191b24;
				--fluidcheckout--color--white: #000;

				--fluidcheckout--color--success: #00cc66;
				--fluidcheckout--color--error: #ec5b5b;
				--fluidcheckout--color--alert: #ff781f;
				--fluidcheckout--color--info: #2184fd;

				--fluidcheckout--shadow-color--darker: rgba( 255, 255, 255, .30 );
				--fluidcheckout--shadow-color--dark: rgba( 255, 255, 255, .15 );
				--fluidcheckout--shadow-color--light: rgba( 0, 0, 0, .15 );
			}
			";

		return $css_variables;
	}

	/**
	 * Enqueue inline CSS variables.
	 */
	public function enqueue_dark_mode_css_variables( $handle = 'fc-checkout-steps' ) {
		// Enqueue inline style
		wp_add_inline_style( $handle, $this->get_dark_mode_css_variables_styles() );
	}

	/**
	 * Maybe enqueue inline CSS variables.
	 */
	public function maybe_enqueue_dark_mode_css_variables() {
		// Bail if not on affected pages.
		if (
			! function_exists( 'is_checkout' )
			|| (
				! is_checkout() // Checkout page
				&& ! is_wc_endpoint_url( 'add-payment-method' ) // Add payment method page
				&& ! is_wc_endpoint_url( 'edit-address' ) // Edit address page
			)
		) { return; }

		// Bail if dark mode is not enabled
		if ( 'yes' !== get_option( 'fc_enable_dark_mode_styles', 'no' ) ) { return; }

		$this->enqueue_dark_mode_css_variables();
	}

}

FluidCheckout_DesignTemplates::instance();
