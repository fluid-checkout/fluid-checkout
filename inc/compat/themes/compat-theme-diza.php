<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Diza (by Thembay).
 */
class FluidCheckout_ThemeCompat_Diza extends FluidCheckout {

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
		remove_filter( 'woocommerce_cart_item_name', 'diza_woocommerce_cart_item_name', 10, 3 );
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Bail if theme function is not available
		if ( ! function_exists( 'diza_tbay_get_config' ) ) { return $css_variables; }

		// Get accent color from the theme
		$accent_color = diza_tbay_get_config( 'main_color_second' );

		// If color isn't set, use the default value
		if ( ! $accent_color ) {
			$accent_color = '#52d5e6';
		}

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '42px',
				'--fluidcheckout--field--padding-left' => '15px',
				'--fluidcheckout--field--border-radius' => '3px',
				'--fluidcheckout--field--background-color--accent' => $accent_color,

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing--select-alt' => '30px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Diza::instance();
