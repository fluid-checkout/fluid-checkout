<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Avada (by ThemeFusion).
 */
class FluidCheckout_ThemeCompat_Avada extends FluidCheckout {

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

		// Avada section override hooks
		add_action( 'wp', array( $this, 'after_layout_section_override_hooks' ), 90 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		global $avada_woocommerce;

		// Remove Avada customizations
		if ( null !== $avada_woocommerce ) {
			remove_action( 'woocommerce_before_checkout_form', array( $avada_woocommerce, 'avada_top_user_container' ), 1 );
			remove_action( 'woocommerce_before_checkout_form', array( $avada_woocommerce, 'checkout_coupon_form' ), 10 );
			remove_action( 'woocommerce_checkout_after_order_review', array( $avada_woocommerce, 'checkout_after_order_review' ), 20 );
			remove_action( 'woocommerce_before_checkout_form', array( $avada_woocommerce, 'before_checkout_form' ), 10 );
			remove_action( 'woocommerce_after_checkout_form', array( $avada_woocommerce, 'after_checkout_form' ), 10 );
			remove_action( 'woocommerce_checkout_before_customer_details', array( $avada_woocommerce, 'checkout_before_customer_details' ), 10 );
			remove_action( 'woocommerce_checkout_after_customer_details', array( $avada_woocommerce, 'checkout_after_customer_details' ), 10 );
			remove_action( 'woocommerce_checkout_billing', array( $avada_woocommerce, 'checkout_billing' ), 20 );
			remove_action( 'woocommerce_checkout_shipping', array( $avada_woocommerce, 'checkout_shipping' ), 20 );
		}
	}



	/**
	 * Add or remove hooks after the layout section overrides are done by Avada.
	 */
	public function after_layout_section_override_hooks() {
		global $avada_woocommerce;

		// Remove Avada customizations
		if ( null !== $avada_woocommerce ) {
			remove_filter( 'woocommerce_enable_order_notes_field', array( $avada_woocommerce, 'enable_order_notes_field' ) );
		}
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {
		// Bail if Avada class and settings object not available
		if ( ! function_exists( 'Avada' ) || ! Avada()->settings ) { return $settings; }

		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $settings; }

		// Add settings
		$settings[ 'utils' ][ 'scrollOffsetSelector' ] = '.fusion-secondary-main-menu, .fusion-header';

		return $settings;
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if Avada class and settings object not available
		if ( ! function_exists( 'Avada' ) || ! Avada()->settings ) { return $attributes; }

		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Get header style
		$header_style = Avada()->settings->get( 'header_layout' );
		
		// Define relative element based on the header style
		switch ( $header_style ) {
			case 'v4':
			case 'v5':
				$attributes['data-sticky-relative-to'] =  '.fusion-is-sticky .fusion-secondary-main-menu';
				break;
			default:
				$attributes['data-sticky-relative-to'] = '.fusion-is-sticky .fusion-header';
				break;
		}

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
				'--fluidcheckout--field--height' => '50px',
				'--fluidcheckout--field--border-radius' => '7px',
				'--fluidcheckout--field--padding-left' => '15px',
				'--fluidcheckout--validation-check--horizontal-spacing--select' => '40px',
				'--fluidcheckout--validation-check--horizontal-spacing--select-alt' => '50px',
				'--fluidcheckout--field--background-color--accent' => 'var(--awb-color4)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Avada::instance();
