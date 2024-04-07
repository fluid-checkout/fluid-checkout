<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Hub (by Liquid Themes).
 */
class FluidCheckout_ThemeCompat_Hub extends FluidCheckout {

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
		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Theme's "Payment" section in order summary
		remove_action( 'woocommerce_checkout_order_review', 'liquid_heading_payment_method', 15 );
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
				'--fluidcheckout--field--height' => '45px',
				'--fluidcheckout--field--padding-left' => '25px',
				'--fluidcheckout--field--background-color--accent' => 'var(--color-primary)',

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing--select' => '40px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Hub::instance();
