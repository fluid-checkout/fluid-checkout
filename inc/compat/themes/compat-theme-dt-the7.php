<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: The7 (by Dream-Theme).
 */
class FluidCheckout_ThemeCompat_DTThe7 extends FluidCheckout {

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

		// Settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Theme's page title section
		add_action( 'fc_checkout_header', array( $this, 'maybe_display_additional_header_sections' ), 10 );
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
		if ( ! function_exists( 'of_get_option' ) ) { return $attributes; }

		// Get the sticky header option
		$is_sticky = of_get_option( 'header-show_floating_navigation' );

		// Bail if sticky header is not enabled
		if ( ! $is_sticky ) { return $attributes; }

		// Get the header style option
		$header_style = of_get_option( 'header-floating_navigation-style' );

		// Define relative element based on the header style
		switch ( $header_style ) {
			case 'slide':
			case 'fade':
				$attributes[ 'data-sticky-relative-to' ] = '{ "xs": { "breakpointInitial": 0, "breakpointFinal": 992, "selector": ".masthead.sticky-mobile-on" }, "sm": { "breakpointInitial": 993, "breakpointFinal": 100000, "selector": "#phantom" } }';
				break;
			case 'sticky':
				$attributes[ 'data-sticky-relative-to' ] = '.masthead';
				break;
		}

		return $attributes;
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings ) {
		// Add new settings
		$settings_new = array(
			array(
				'title' => __( 'Theme The7', 'fluid-checkout' ),
				'type'  => 'title',
				'id'    => 'fc_integrations_theme_dt_the7_options',
			),

			array(
				'title'           => __( 'Additional header sections', 'fluid-checkout' ),
				'desc'            => __( 'Output additional header sections from the The7 theme when using Fluid Checkout header and footer.', 'fluid-checkout' ),
				'id'              => 'fc_compat_theme_dt_the7_output_additional_header_sections',
				'type'            => 'checkbox',
				'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_compat_theme_dt_the7_output_additional_header_sections' ),
				'autoload'        => false,
			),

			array(
				'type' => 'sectionend',
				'id'    => 'fc_integrations_theme_dt_the7_options',
			),
		);

		$settings = array_merge( $settings, $settings_new );

		return $settings;
	}



	/**
	 * Maybe output additional sections from the theme when using distraction free header and footer.
	 */
	public function maybe_display_additional_header_sections() {
		// Bail if not using distraction free header and footer
		if ( ! FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return; }

		// Bail if theme sections output is disabled in the plugin settings
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_compat_theme_dt_the7_output_additional_header_sections' ) ) { return; }

		// Bail if theme functions are not available
		if ( ! function_exists( 'presscore_template_config_init' ) || ! function_exists( 'the7_print_post_inlne_css' ) || ! function_exists( 'presscore_fancy_header_controller' ) || ! function_exists( 'presscore_slideshow_controller' ) || ! function_exists( 'presscore_page_title_controller' ) || ! function_exists( 'dt_woocommerce_cart_progress' ) ) { return; }

		// Initialize plugin's config object
		presscore_template_config_init();

		// Output required inline CSS from the theme
		the7_print_post_inlne_css();

		// Output "fancy header" section if enabled
		presscore_fancy_header_controller();
		// Output page title section if enabled
		presscore_page_title_controller();
		// Output checkout steps section if enabled
		dt_woocommerce_cart_progress();
	}

}

FluidCheckout_ThemeCompat_DTThe7::instance();
