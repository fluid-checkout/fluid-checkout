<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Zakra (by ThemeGrill).
 */
class FluidCheckout_ThemeCompat_Zakra extends FluidCheckout {

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

		// Remove theme's spin buttons
		$this->remove_action_for_class( 'woocommerce_before_quantity_input_field', array( 'Zakra_WooCommerce', 'product_quantity_minus_button' ), 10 );
		$this->remove_action_for_class( 'woocommerce_after_quantity_input_field', array( 'Zakra_WooCommerce', 'product_quantity_plus_button' ), 10 );
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Get theme's color
		$primary_color = get_theme_mod( 'zakra_primary_color', '#027abb' );

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '48px',
				'--fluidcheckout--field--padding-left' => '18px',
				'--fluidcheckout--field--border-radius' => '4px',
				'--fluidcheckout--field--border-color' => '#D4D4D8',
				'--fluidcheckout--field--background-color--accent' => $primary_color,

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing--password' => '30px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Zakra::instance();
