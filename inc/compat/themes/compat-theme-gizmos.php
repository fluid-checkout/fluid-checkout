<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Gizmos (by Mikado Themes).
 */
class FluidCheckout_ThemeCompat_Gizmos extends FluidCheckout {

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

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );
		add_filter( 'fc_apply_button_design_styles', '__return_true', 10 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Remove theme elements
		remove_action( 'woocommerce_before_checkout_form', 'gizmos_add_main_woo_page_holder', 5 );
		remove_action( 'woocommerce_after_checkout_form', 'gizmos_add_main_woo_page_holder_end', 20 );
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Add CSS variables
		$new_css_variables = array(
			// Buttons
			':root body' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '60.14px',
				'--fluidcheckout--field--padding-left' => '20px',
				'--fluidcheckout--field--border-radius' => '8px',
				'--fluidcheckout--field--border-color' => '#eaeaea',
				'--fluidcheckout--field--font-size' => '14px',
				'--fluidcheckout--field--background-color--accent' => 'var( --qode-main-color )',

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing--select-alt' => '30px',

				// Primary button color
				'--fluidcheckout--button--primary--border-color' => 'var( --qode-main-color );',
				'--fluidcheckout--button--primary--background-color' => 'var( --qode-main-color );',
				'--fluidcheckout--button--primary--text-color' => '#fff',
				'--fluidcheckout--button--primary--border-color--hover' => 'var( --qode-main-color );',
				'--fluidcheckout--button--primary--background-color--hover' => 'var( --qode-main-color );',
				'--fluidcheckout--button--primary--text-color--hover' => '#fff',

				// Secondary button color
				'--fluidcheckout--button--secondary--border-color' => 'var( --qode-main-color );',
				'--fluidcheckout--button--secondary--background-color' => 'transparent',
				'--fluidcheckout--button--secondary--text-color' => 'var( --qode-main-color );',
				'--fluidcheckout--button--secondary--border-color--hover' => 'var( --qode-main-color );',
				'--fluidcheckout--button--secondary--background-color--hover' => 'var( --qode-main-color );',
				'--fluidcheckout--button--secondary--text-color--hover' => '#fff',

				// Button design styles
				'--fluidcheckout--button--height' => '50px',
				'--fluidcheckout--button--border-radius' => '8px',
				'--fluidcheckout--button--font-size' => '12px',
				'--fluidcheckout--button--font-weight' => 'bold',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if theme function is not available
		if ( ! function_exists( 'gizmos_core_get_post_value_through_levels' ) ) { return $attributes; }

		// Get theme header scroll appearance
		$header_scroll_appearance = gizmos_core_get_post_value_through_levels( 'qodef_header_scroll_appearance' );

		// Maybe set for sticky header
		if ( 'sticky' === $header_scroll_appearance ) {
			$attributes['data-sticky-relative-to'] = '.qodef-header-sticky';
		}
		// Maybe set for fixed header
		else if ( 'fixed' === $header_scroll_appearance ) {
			$attributes['data-sticky-relative-to'] = '.qodef-header--fixed-display #qodef-page-header';
		}

		return $attributes;
	}

}

FluidCheckout_ThemeCompat_Gizmos::instance();
