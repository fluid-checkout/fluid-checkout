<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Motta (by Uixthemes).
 */
class FluidCheckout_ThemeCompat_Motta extends FluidCheckout {

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
		// Set class names from the theme
		$checkout_class_name = '\Motta\WooCommerce\Checkout';
		$general_class_name = 'Motta\WooCommerce\General';

		// Bail if class methods are not available
		if ( ! method_exists( $checkout_class_name, 'instance' ) || ! method_exists( $general_class_name, 'instance' ) ) { return; }

		// Late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );

		// Get class objects
		$class_object = call_user_func( array( $checkout_class_name, 'instance' ) );
		$general_class_object = call_user_func( array( $general_class_name, 'instance' ) );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Product thumbnails
		remove_filter( 'woocommerce_cart_item_name', array( $class_object, 'review_product_name_html' ), 10, 3);

		// Quantity controls
		remove_action( 'woocommerce_before_quantity_input_field', array( $general_class_object, 'quantity_icon_decrease' ), 10 );
		remove_action( 'woocommerce_after_quantity_input_field', array( $general_class_object, 'quantity_icon_increase' ), 10 );

		// Theme elements before checkout form
		remove_action( 'woocommerce_before_checkout_form', array( $class_object, 'before_login_form' ), 10 );
		remove_action( 'woocommerce_before_checkout_form', array( $class_object, 'login_form' ), 10 );
		remove_action( 'woocommerce_before_checkout_form', array( $class_object, 'coupon_form' ), 10 );
		remove_action( 'woocommerce_before_checkout_form', array( $class_object, 'after_login_form' ), 10 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// CSS variables on edit address page
		add_action( 'fc_css_variables', array( $this, 'add_css_variables_edit_address' ), 20 );
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Set class name from the theme
		$class_name = '\Motta\Helper';

		// Bail if required class method is not available
		if ( ! method_exists( $class_name, 'get_option' ) ) { return $attributes; }

		// Bail if sticky header is not enabled
		$sticky_header = call_user_func( array( $class_name, 'get_option' ), 'header_sticky' );
		if ( ! $sticky_header || 'none' === $sticky_header ) { return $attributes; }

		$attributes[ 'data-sticky-relative-to' ] = '.header-sticky';

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
				'--fluidcheckout--field--height' => '60px',
				'--fluidcheckout--field--padding-left' => '22px',
				'--fluidcheckout--field--font-size' => 'var(--mt-input__font-size)',
				'--fluidcheckout--field--border-color' => '#dadfe3',
				'--fluidcheckout--field--border-width' => 'var(--mt-input__border-width)',
				'--fluidcheckout--field--border-radius' => 'var(--mt-border__radius)',
				'--fluidcheckout--field--background-color--accent' => '#1d2128',

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing' => '20px',
				'--fluidcheckout--validation-check--horizontal-spacing--select-alt' => '45px',
				'--fluidcheckout--validation-check--horizontal-spacing--password' => '20px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

	/**
	 * Add CSS variables to the edit address page.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables_edit_address( $css_variables ) {
		// Bail if not on account address edit page
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_wc_endpoint_url( 'edit-address' ) ) { return $css_variables; }

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '44px', 
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Motta::instance();
