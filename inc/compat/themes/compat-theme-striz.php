<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Striz (by Opal Team).
 */
class FluidCheckout_ThemeCompat_Striz extends FluidCheckout {

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
		remove_action( 'woocommerce_checkout_before_customer_details', 'xstriz_checkout_before_customer_details_container', 1 );
		remove_action( 'woocommerce_checkout_after_customer_details', 'xstriz_checkout_after_customer_details_container', 1 );
		remove_action( 'woocommerce_checkout_after_order_review', 'xstriz_checkout_after_order_review_container', 1 );
		remove_action( 'woocommerce_checkout_order_review', 'xstriz_woocommerce_order_review_heading', 1 );
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
				'--fluidcheckout--field--height' => '44.97px',
				'--fluidcheckout--field--padding-left' => '12px',
				'--fluidcheckout--field--border-color' => '#e1e1e1',
				'--fluidcheckout--field--font-size' => '.875rem',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Striz::instance();
