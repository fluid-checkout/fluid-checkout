<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Page Builder Framework (by David Vongries).
 */
class FluidCheckout_ThemeCompat_PageBuilderFramework extends FluidCheckout {

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
		// Remove theme's spin buttons
		remove_action( 'woocommerce_before_quantity_input_field', 'wpbf_woo_before_quantity_input_field', 10 );
		remove_action( 'woocommerce_after_quantity_input_field', 'wpbf_woo_after_quantity_input_field', 10 );
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
				'--fluidcheckout--field--height' => '46px',
				'--fluidcheckout--field--padding-left' => '15px',
				'--fluidcheckout--field--background-color--accent' => 'var(--accent-color)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_PageBuilderFramework::instance();
