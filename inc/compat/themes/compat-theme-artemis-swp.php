<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Artemis-SWP (by SmartWPress).
 */
class FluidCheckout_ThemeCompat_ArtemisSWP extends FluidCheckout {

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
		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Dark mode
		add_filter( 'fc_enable_dark_mode_styles', array( $this, 'maybe_set_is_dark_mode' ), 10 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );

		// Payment methods
		remove_action( 'woocommerce_review_order_before_payment', 'artemis_swp_woocommerce_review_order_before_payment', 10 );

		// Order summary
		remove_action( 'woocommerce_checkout_order_review', 'artemis_swp_woocommerce_checkout_order_review', 1 );
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function change_fc_content_section_class( $class ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $class; }

		return $class . ' lc_swp_full';
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '.lc_sticky_menu';

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
				'--fluidcheckout--field--padding-left' => '10px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}



	/**
	 * Maybe set dark mode enabled.
	 * 
	 * @param  array  $is_dark_mode  Whether it is dark mode or not.
	 */
	public function maybe_set_is_dark_mode( $is_dark_mode ) {
		// Bail if theme functions and classes are not available
		if ( ! function_exists( 'artemis_swp_get_default_color_scheme' ) ) { return $is_dark_mode; }

		// Get dark mode option from theme
		$theme_color_scheme = artemis_swp_get_default_color_scheme();

		// Bail if not using the dark mode
		if ( 'white_on_black' !== $theme_color_scheme ) { return $is_dark_mode; }

		$is_dark_mode = true;
		return $is_dark_mode;
	}

}

FluidCheckout_ThemeCompat_ArtemisSWP::instance();
