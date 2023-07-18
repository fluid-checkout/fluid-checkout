<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Umea (by Edge Themes).
 */
class FluidCheckout_ThemeCompat_Umea extends FluidCheckout {

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

		// Container class
		add_filter( 'fc_add_container_class', '__return_false' );

		// General
		// TODO: Replace this with specific hook and use CSS variables instead
		add_filter( 'body_class', array( $this, 'add_body_class' ), 10 );

		// CSS variables
		// TODO: Replace this with specific hook and use CSS variables instead
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_css_variables' ), 10 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Maybe remove elements from theme
		if ( FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) {
			remove_action( 'woocommerce_before_checkout_form', 'umea_add_main_woo_page_holder', 5 );
			remove_action( 'woocommerce_after_checkout_form', 'umea_add_main_woo_page_holder_end', 20 );
		}
	}



	/**
	 * Add page body class for feature detection.
	 *
	 * @param array $classes Classes for the body element.
	 */
	public function add_body_class( $classes ) {
		// Bail if not on affected pages.
		if (
			! function_exists( 'is_checkout' )
			|| (
				! is_checkout() // Checkout page
				&& ! is_wc_endpoint_url( 'add-payment-method' ) // Add payment method page
				&& ! is_wc_endpoint_url( 'edit-address' ) // Edit address page
			)
		) { return $classes; }

		// Add custom button color class
		$classes[] = 'has-fc-button-colors';
		$classes[] = 'has-fc-button-styles';

		return $classes;
	}



	/**
	 * Get CSS variables styles.
	 */
	public function get_css_variables_styles() {
		// Bail if theme function is not available
		if ( ! function_exists( 'umea_core_get_post_value_through_levels' ) ) { return ''; }

		// Get theme main color
		$main_color = umea_core_get_post_value_through_levels( 'qodef_main_color' );

		// Define CSS variables
		$css_variables = ":root {
			--fluidcheckout--button--font-weight: bold;

			--fluidcheckout--button--primary--border-color: {$main_color};
			--fluidcheckout--button--primary--background-color: {$main_color};

			--fluidcheckout--button--secondary--border-color: {$main_color};
			--fluidcheckout--button--secondary--background-color: transparent;
			--fluidcheckout--button--secondary--text-color: {$main_color};
			--fluidcheckout--button--secondary--border-color--hover: {$main_color};
			--fluidcheckout--button--secondary--background-color--hover: {$main_color};
			--fluidcheckout--button--secondary--text-color--hover: var( --fluidcheckout--color--white, #fff );
		}";

		return $css_variables;
	}



	/**
	 * Enqueue inline CSS variables.
	 */
	public function enqueue_css_variables() {
		// Enqueue inline style
		wp_add_inline_style( 'umea-main', $this->get_css_variables_styles() );
	}

	/**
	 * Maybe enqueue inline CSS variables.
	 */
	public function maybe_enqueue_css_variables() {
		// Bail if not on affected pages.
		if (
			! function_exists( 'is_checkout' )
			|| (
				! is_checkout() // Checkout page
				&& ! is_wc_endpoint_url( 'add-payment-method' ) // Add payment method page
				&& ! is_wc_endpoint_url( 'edit-address' ) // Edit address page
			)
		) { return; }

		$this->enqueue_css_variables();
	}

}

FluidCheckout_ThemeCompat_Umea::instance();
