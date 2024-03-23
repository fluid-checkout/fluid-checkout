<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: BuddyBoss theme.
 */
class FluidCheckout_ThemeCompat_BuddyBoss extends FluidCheckout {

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
		// Dequeue
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_dequeue_scripts' ), 100 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}



	/**
	 * Dequeue theme scripts unnecessary on checkout page and that interfere with Fluid Checkout scripts.
	 */
	public function maybe_dequeue_scripts() {
		// Bail if not on checkout page
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return; }
		
		wp_dequeue_script( 'buddyboss-theme-woocommerce-js' );
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
				'--fluidcheckout--field--height' => '41px',
				'--fluidcheckout--field--padding-left' => '12px',
				'--fluidcheckout--field--border-radius' => 'var(--bb-input-radius)',
				'--fluidcheckout--field--background-color--accent' => 'var(--bb-primary-color)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_BuddyBoss::instance();
