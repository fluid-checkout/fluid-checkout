<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Twenty Twenty-Two (by The WordPress Team).
 */
class FluidCheckout_ThemeCompat_TwentyTwentyTwo extends FluidCheckout {

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

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Buttons
		add_filter( 'fc_next_step_button_classes', array( $this, 'add_button_class' ), 10 );
		add_filter( 'fc_substep_save_button_classes', array( $this, 'add_button_class' ), 10 );
		add_filter( 'fc_coupon_code_apply_button_classes', array( $this, 'add_button_class' ), 10 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Order summary
		$this->order_summary_hooks();
	}

	/**
	 * Add or remove hooks for the checkout order summary.
	 */
	public function order_summary_hooks() {
		// Bail if not on the checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

          // Bail if theme classes or functions not available
		if ( ! class_exists( 'WC_Twenty_Twenty_Two' ) ) { return; }

		// Remove hooks
		remove_action( 'woocommerce_checkout_before_order_review_heading', array( 'WC_Twenty_Twenty_Two', 'before_order_review' ) );
		remove_action( 'woocommerce_checkout_after_order_review', array( 'WC_Twenty_Twenty_Two', 'after_order_review' ) );
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
				'--fluidcheckout--field--height' => '47.7969px',
				'--fluidcheckout--field--padding-left' => '17.6px',
				'--fluidcheckout--field--border-radius' => '4px',
				'--fluidcheckout--field--border-color' => 'var(--wc-form-border-color, #212121)',
				'--fluidcheckout--field--text-color' => 'inherit',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}



	/**
	 * Add button class from the theme.
	 * 
	 * @param  array  $classes  The button classes.
	 */
	public function add_button_class( $classes ) {
		// Add 'button alt wp-element-button' class to apply theme styles
		if ( is_array( $classes ) ) {
			array_push( $classes, 'button alt wp-element-button' );
		} 
		else {
			$classes .= ' button alt wp-element-button';
		}

		return $classes;
	}
}

FluidCheckout_ThemeCompat_TwentyTwentyTwo::instance();
