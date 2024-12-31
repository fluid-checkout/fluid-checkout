<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Loobek (by Theme Sky Team).
 */
class FluidCheckout_ThemeCompat_Loobek extends FluidCheckout {

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

		// Coupon bar
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 20 );
		remove_action( 'woocommerce_before_checkout_form', 'loobek_before_checkout_form_start', 1 );
		remove_action( 'woocommerce_before_checkout_form', 'loobek_before_checkout_form_end', 999 );

		// Quantity buttons
		remove_action( 'woocommerce_before_quantity_input_field', 'loobek_before_quantity_input_field', 1 );
		remove_action( 'woocommerce_after_quantity_input_field', 'loobek_after_quantity_input_field', 99 );
	}



	/**
	 * Change the element used to position the progress bar and order summary when sticky.
	 * 
	 * @param  array  $attributes  The elements attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if theme functions isn't available
		if ( ! function_exists( 'loobek_get_theme_options' ) || ! function_exists( 'loobek_get_theme_options' ) ) { return $attributes; }

		// Get option from theme
		$is_sticky = loobek_get_theme_options( 'ts_enable_sticky_header' );

		// Bail if theme's conditions for sticky header are not met
		if ( ! $is_sticky ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '.ts-header.is-sticky';

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
				'--fluidcheckout--field--height' => '45.14px',
				'--fluidcheckout--field--padding-left' => '20px',
				'--fluidcheckout--field--font-size' => 'var(--loobek-main-font-size)',
				'--fluidcheckout--field--text-color--accent' => 'var(--loobek-primary-color)',
				'--fluidcheckout--field--border-color' => 'var(--loobek-input-border-color, #000)',
				'--fluidcheckout--field--border-width' => '1px',
				'--fluidcheckout--field--background-color--accent' => 'rgba(153,153,153,0.1)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Loobek::instance();
