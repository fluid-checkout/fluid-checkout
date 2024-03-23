<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Aora (by Thembay).
 */
class FluidCheckout_ThemeCompat_Aora extends FluidCheckout {

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
		remove_filter( 'woocommerce_cart_item_name', 'aora_woocommerce_cart_item_name', 10, 3 );
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
				'--fluidcheckout--field--height' => '44px',
				'--fluidcheckout--field--border-radius' => '3px',
				'--fluidcheckout--field--padding-left' => '15px',
				'--fluidcheckout--field--background-color--accent' => 'var(--tb-theme-color)',
				'--fluidcheckout--field--text-color--accent' => 'var(--link-color-2)',
				'--fluidcheckout--field--text-color--focus' => 'var(--link-color-2)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Aora::instance();
