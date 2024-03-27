<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Neve (by ThemeIsle).
 */
class FluidCheckout_ThemeCompat_Neve extends FluidCheckout {

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

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}



	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Contact substep
		$this->remove_action_for_closure( 'woocommerce_checkout_before_customer_details', 0 );
		$this->remove_action_for_class( 'woocommerce_checkout_after_customer_details', array( 'Neve\Compatibility\Woocommerce', 'close_div' ), 10 );
		$this->remove_action_for_class( 'woocommerce_checkout_after_customer_details', array( 'Neve\Compatibility\Woocommerce', 'close_div' ), PHP_INT_MAX );

		// Order summary
		$this->remove_action_for_closure( 'woocommerce_checkout_before_order_review_heading', 10 );
		$this->remove_action_for_class( 'woocommerce_checkout_after_order_review', array( 'Neve\Compatibility\Woocommerce', 'close_div' ), 10 );

		// Login and coupon code form position
		$this->remove_action_for_class( 'woocommerce_before_checkout_form', array( 'Neve\Compatibility\Woocommerce', 'move_coupon' ), 10 );
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
				'--fluidcheckout--field--height' => '51.2px',
				'--fluidcheckout--field--padding-left' => '12px',
				'--fluidcheckout--field--border-radius' => '3px',
				'--fluidcheckout--field--border-width' => '2px',
				'--fluidcheckout--field--background-color--accent' => 'var(--nv-secondary-accent)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Neve::instance();
