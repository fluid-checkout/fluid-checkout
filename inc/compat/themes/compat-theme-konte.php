<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Konte (by UIX Themes).
 */
class FluidCheckout_ThemeCompat_Konte extends FluidCheckout {

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
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Extra coupon field
		remove_action( 'woocommerce_before_checkout_form', array( 'Konte_WooCommerce_Template_Checkout', 'checkout_coupon_form' ), 15 );
	}



	/**
	 * Change the element used to position the progress bar and order summary when sticky.
	 * 
	 * @param  array  $attributes  The elements attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if theme functions isn't available
		if ( ! function_exists( 'konte_get_option' ) ) { return $attributes; }

		// Get sticky header option from the theme
		$sticky_header = konte_get_option( 'header_sticky' );

		// Maybe change the relative ID based on the sticky header option
		switch ( $sticky_header ) {
			case 'smart':
				$attributes['data-sticky-relative-to'] = '.header-sticky--smart';
				break;
			case 'normal':
				$attributes['data-sticky-relative-to'] = '.header-sticky--normal.sticky';
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
		// Bail if theme function is not available
		if ( ! function_exists( 'konte_get_option' ) ) { return $css_variables; }

		// Get the color scheme from the theme options
		$is_custom_scheme = konte_get_option( 'color_scheme_custom' );

		// If custom color scheme is enabled, use the custom color
		if ( $is_custom_scheme ) {
			$primary_color = konte_get_option( 'color_scheme_color' );
		} 
		else {
			// Use the color from the predefined color scheme
			$primary_color = konte_get_option( 'color_scheme' );
		}

		// If the primary color is not set, use the default color
		if ( empty( $primary_color ) ) {
			$primary_color = '#161619';
		}

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '48.57px',
				'--fluidcheckout--field--padding-left' => '0px',
				'--fluidcheckout--field--border-width' => '0px',
				'--fluidcheckout--field--background-color--accent' => $primary_color,

				// Primary button colors
				'--fluidcheckout--button--primary--border-color' => $primary_color,
				'--fluidcheckout--button--primary--background-color' => $primary_color,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Konte::instance();
