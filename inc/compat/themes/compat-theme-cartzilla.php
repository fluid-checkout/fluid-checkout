<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Cartzilla.
 */
class FluidCheckout_ThemeCompat_Cartzilla extends FluidCheckout {

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

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );

		// Buttons
		add_filter( 'fc_next_step_button_classes', array( $this, 'add_button_class' ), 10 );
		add_filter( 'fc_substep_save_button_classes', array( $this, 'add_button_class' ), 10 );
		add_filter( 'fc_coupon_code_apply_button_classes', array( $this, 'add_button_class' ), 10 );
		add_filter( 'fc_place_order_button_classes', array( $this, 'add_button_class' ), 10 );
		add_filter( 'fc_checkout_login_button_classes', array( $this, 'add_button_class' ), 10 );
		add_filter( 'fc_add_payment_method_button_classes', array( $this, 'add_button_class' ), 10 );

		// Inputs
		add_filter( 'fc_checkout_login_input_classes', array( $this, 'add_input_class' ), 10 );

		// Remove duplicate coupon code from checkout page
		remove_action( 'woocommerce_after_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

		// Coupon code error message dismiss button
		add_filter( 'fc_coupon_code_error_message_dismiss_button_enabled', '__return_false' );
	}



	/**
	 * Change the element used to position the progress bar and order summary when sticky.
	 * 
	 * @param  array  $attributes  The elements attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '.site-header';

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
				'--fluidcheckout--field--height' => '44px',
				'--fluidcheckout--field--padding-left' => '16px',
				'--fluidcheckout--field--font-size' => '16px',
				'--fluidcheckout--field--border-radius' => '5px',
				'--fluidcheckout--field--border-color' => 'var(--border-color)',
				'--fluidcheckout--field--background-color--accent' => 'var(--background-color--accent)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function change_fc_content_section_class( $class ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $class; }

		return $class . ' container';
	}



	/**
	 * Add button class from the theme.
	 * 
	 * @param  array  $classes  The button classes.
	 */
	public function add_button_class( $classes ) {
		// Add 'btn btn-primary' class to apply theme styles
		if ( is_array( $classes ) ) {
			array_push( $classes, 'btn btn-primary' );
		} 
		else {
			$classes .= ' btn btn-primary';
		}

		return $classes;
	}



	/**
	 * Add input class from the theme.
	 * 
	 * @param  array  $classes  The input classes.
	 */
	public function add_input_class( $classes ) {
		// Add 'form-control' class to apply theme styles
		if ( is_array( $classes ) ) {
			array_push( $classes, 'form-control' );
		} 
		else {
			$classes .= ' form-control';
		}

		return $classes;
	}
}

FluidCheckout_ThemeCompat_Cartzilla::instance();
