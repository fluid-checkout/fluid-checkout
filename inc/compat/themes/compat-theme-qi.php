<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Qi (by Qode Interactive).
 */
class FluidCheckout_ThemeCompat_Qi extends FluidCheckout {

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
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}



	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		remove_action( 'woocommerce_before_checkout_form', 'qi_add_main_woo_page_holder', 5 );
		remove_action( 'woocommerce_after_checkout_form', 'qi_add_main_woo_page_holder_end', 20 );
		add_action( 'woocommerce_before_checkout_form_cart_notices', 'qi_add_main_woo_page_holder', 1 );
		add_action( 'woocommerce_after_checkout_form', 'qi_add_main_woo_page_holder_end', 101 );
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
				'--fluidcheckout--field--height' => '58px',
				'--fluidcheckout--field--padding-left' => '20px',

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing--select' => '40px',
				'--fluidcheckout--validation-check--horizontal-spacing--select-alt' => '40px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Qi::instance();
