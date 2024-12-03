<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce Fast Cart (by Barn2 Plugins).
 */
class FluidCheckout_WooCommerceFastCart extends FluidCheckout {

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
		// Checkout page template
		add_filter( 'fc_enable_checkout_page_template', array( $this, 'maybe_disable_checkout_page_template' ), 300 );

		// Container class
		// Needs to run at priority (100), after themes add their container class so we can effectively remove it.
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 100 );
	}



	/**
	 * Disable custom template for the checkout page content part.
	 */
	public function maybe_disable_checkout_page_template( $enabled ) {
		// Bail if not on fast cart iframe.
		if ( ! array_key_exists( 'wfc-checkout', $_GET ) || 'true' !== $_GET[ 'wfc-checkout' ] ) { return $enabled; }
		
		// Disable custom checkout templates.
		return false;
	}



	/**
	 * Remove theme container classes from the main content element.
	 *
	 * @param  string  $class   Main content element classes.
	 */
	public function change_fc_content_section_class( $class ) {
		// Bail if not on fast cart iframe.
		if ( ! array_key_exists( 'wfc-checkout', $_GET ) || 'true' !== $_GET[ 'wfc-checkout' ] ) { return $class; }

		return '';
	}

}

FluidCheckout_WooCommerceFastCart::instance();
