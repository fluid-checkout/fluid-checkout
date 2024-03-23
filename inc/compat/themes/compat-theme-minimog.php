<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Minimog (by ThemeMove).
 */
class FluidCheckout_ThemeCompat_Minimog extends FluidCheckout {

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

		// Scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_deregister_woocommerce_scripts' ), 20 );
		add_action( 'wp_enqueue_scripts', array( FluidCheckout_Enqueue::instance(), 'maybe_replace_woocommerce_scripts' ), 21 );

		// Remove checkout payment info heading
		if ( class_exists( 'Minimog\Woo\Checkout' ) ) {
			remove_action( 'woocommerce_checkout_after_order_review',array( Minimog\Woo\Checkout::instance(), 'template_checkout_payment_title' ),10 );
		}
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
				'--fluidcheckout--field--padding-left' => '15px',
				'--fluidcheckout--field--border-radius' => '3px',
				'--fluidcheckout--field--background-color--accent' => 'var(--minimog-color-secondary)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}



	/**
	 * Maybe remove WooCommerce scripts.
	 */
	public function maybe_deregister_woocommerce_scripts() {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }
		
		FluidCheckout_Enqueue::instance()->deregister_woocommerce_scripts();
	}
}

FluidCheckout_ThemeCompat_Minimog::instance();
