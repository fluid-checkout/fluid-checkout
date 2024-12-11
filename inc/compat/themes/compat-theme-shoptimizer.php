<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Shoptimizer (by CommerceGurus).
 */
class FluidCheckout_ThemeCompat_Shoptimizer extends FluidCheckout {

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

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// Site header sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 30 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 30 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}



	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Removes Coupon code from woocommerce after checkout form
		if ( class_exists( 'FluidCheckout_CouponCodes' ) && FluidCheckout_CouponCodes::instance()->is_feature_enabled() ) {
			remove_action( 'woocommerce_after_checkout_form', 'shoptimizer_coupon_wrapper_start', 5 );
			remove_action( 'woocommerce_after_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
			remove_action( 'woocommerce_after_checkout_form', 'shoptimizer_coupon_wrapper_end', 99 );
		}

		// Remove Shoptimizer progress bar
		remove_action( 'woocommerce_before_cart', 'shoptimizer_cart_progress', 10 );
		remove_action( 'woocommerce_before_checkout_form', 'shoptimizer_cart_progress', 5 );

		// Remove duplicate product image on order summary
		remove_filter( 'woocommerce_cart_item_name', 'shoptimizer_product_thumbnail_in_checkout', 20, 3 );

		// Remove quantity customizations
		remove_filter( 'woocommerce_checkout_cart_item_quantity', 'shoptimizer_woocommerce_checkout_cart_item_quantity', 10, 3 );
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $settings; }

		// Add settings
		$settings[ 'utils' ][ 'scrollOffsetSelector' ] = '.site-header, .col-full-nav';

		return $settings;
	}



	/**
	 * Change the relative selector for sticky elements.
	 *
	 * @param   array  $attributes  The element HTML attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }
	
		$attributes['data-sticky-relative-to'] = '{ "xs": { "breakpointInitial": 0, "breakpointFinal": 992, "selector": ".site-header" }, "sm": { "breakpointInitial": 993, "breakpointFinal": 100000, "selector": ".col-full-nav" } }';
	
		return $attributes;
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
				'--fluidcheckout--field--height' => '40px',
				'--fluidcheckout--field--padding-left' => '11.2px',
				'--fluidcheckout--field--border-radius' => '5px',
				'--fluidcheckout--field--border-color' => '#d2d6dc',
				'--fluidcheckout--field--box-shadow' => '0 1px 2px 0 rgba( 0, 0, 0, .05 )',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Shoptimizer::instance();
