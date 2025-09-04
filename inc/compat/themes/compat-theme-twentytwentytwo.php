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
