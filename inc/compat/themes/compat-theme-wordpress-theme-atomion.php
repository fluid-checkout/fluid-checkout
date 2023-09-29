<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: WordPress Theme Atomion (by MarketPress).
*/
class FluidCheckout_ThemeCompat_WordPressThemeAtomion extends FluidCheckout {

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

		// // Container class
		// add_filter( 'fc_add_container_class', '__return_false', 10 );
		// add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Checkout elements
		remove_action( 'woocommerce_after_checkout_form', 'atomion_wc_required_fields_note', 10 );
	}



	/**
	 * Add container class to the main content element for the cart page.
	 *
	 * @param string $class Main content element classes.
	 */
	public function change_fc_content_section_class( $class ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $class; }

		return $class . ' site-container';
	}

}

FluidCheckout_ThemeCompat_WordPressThemeAtomion::instance();
