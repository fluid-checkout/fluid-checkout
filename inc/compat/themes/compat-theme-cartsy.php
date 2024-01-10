<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Cartsy (by Redq).
 */
class FluidCheckout_ThemeCompat_Cartsy extends FluidCheckout {

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

		// Checkout templates
		$this->checkout_layout_hooks();

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Bail if not on checkout page.
		if( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Bring back currency and decimals to cart item price values
		$this->remove_filter_for_class( 'woocommerce_cart_product_price', array( 'Framework\App\WooCommerceLoad', 'cartsyCartProductPrice' ), 10 );
	}



	/*
	* Checkout templates hooks.
	*/
	public function checkout_layout_hooks() {
		// Bail if using the distraction free template
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return; }

		// Prevent theme's page template from being replaced by FC checkout template
		add_filter( 'fc_enable_checkout_page_template', '__return_false', 10 );
	}



	/**
	 * Change the element used to position the progress bar and order summary when sticky.
	 * 
	 * @param  array  $attributes  The elements attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '.cartsy-menu-area';

		return $attributes;
	}

}

FluidCheckout_ThemeCompat_Cartsy::instance();
