<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Motta (by Uixthemes).
 */
class FluidCheckout_ThemeCompat_Motta extends FluidCheckout {

	/**
	 * Class name for the theme which this compatibility class is related to.
	 */
	public const CLASS_NAME = '\Motta\WooCommerce\Checkout';



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
		$general_class_name = 'Motta\WooCommerce\General';

		// Bail if class methods are not available
		if ( ! method_exists( self::CLASS_NAME, 'instance' ) || ! method_exists( $general_class_name, 'instance' ) ) { return $is_verified; }

		// Get class objects
		$class_object = call_user_func( array( self::CLASS_NAME, 'instance' ) );
		$general_class_object = call_user_func( array( $general_class_name, 'instance' ) );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

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
				'--fluidcheckout--field--font-weight' => '500',
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

}

FluidCheckout_ThemeCompat_Motta::instance();
