<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Astra (by Brainstorm Force).
 */
class FluidCheckout_ThemeCompat_Astra extends FluidCheckout {

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
		// Bail if not on checkout or cart page or doing AJAX call
		if ( ! function_exists( 'is_checkout' ) || ( ! is_checkout() && ! is_cart() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) ) { return; }

		// Remove shipping fields from the billing section added by the theme
		// @see themes/astra/inc/compatibility/woocommerce/class-astra-woocommerce.php:LN759
		remove_action( 'woocommerce_checkout_billing', array( WC()->checkout(), 'checkout_form_shipping' ), 10 );
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
				'--fluidcheckout--field--height' => '44.7px',
				'--fluidcheckout--field--padding-left' => '11px',
				'--fluidcheckout--field--background-color--accent' => 'var(--ast-global-color-1)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Astra::instance();
