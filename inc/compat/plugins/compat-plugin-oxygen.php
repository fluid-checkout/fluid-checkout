<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Oxygen (by Soflyy).
 */
class FluidCheckout_Oxygen extends FluidCheckout {

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
		// Maybe remove checkout page template
		// - when editing the checkout page
		// - when using theme header and footer
		if ( isset( $_GET[ 'ct_builder' ] ) || isset( $_GET[ 'action' ] ) || ! FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) {
			remove_filter( 'template_include', array( FluidCheckout_CheckoutPageTemplate::instance(), 'checkout_page_template' ), 100 );
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
				'--fluidcheckout--field--height' => '43px',
				'--fluidcheckout--field--border-radius' => '4px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_Oxygen::instance();
