<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Razzi (by DrFuri).
 */
class FluidCheckout_ThemeCompat_Razzi extends FluidCheckout {

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
	}



	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Bail if theme Checkout template class is not available
		if ( ! class_exists( '\Razzi\WooCommerce\Template\Checkout' ) ) { return; }

		remove_action( 'woocommerce_before_checkout_form', array( \Razzi\WooCommerce\Template\Checkout::class, 'before_login_form' ), 10 );
		remove_action( 'woocommerce_before_checkout_form', array( \Razzi\WooCommerce\Template\Checkout::class, 'checkout_login_form' ), 10 );
		remove_action( 'woocommerce_before_checkout_form', array( \Razzi\WooCommerce\Template\Checkout::class, 'checkout_coupon_form' ), 10 );
		remove_action( 'woocommerce_before_checkout_form', array( \Razzi\WooCommerce\Template\Checkout::class, 'after_login_form' ), 10 );
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
				'--fluidcheckout--field--height' => '49.14px',
				'--fluidcheckout--field--padding-left' => '15px',
				'--fluidcheckout--field--border-color' => 'var(--rz-border-color)',
				'--fluidcheckout--field--background-color--accent' => 'var(--rz-background-color-dark)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Razzi::instance();
