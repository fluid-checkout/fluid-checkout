<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Kapee (by PressLayouts).
 */
class FluidCheckout_ThemeCompat_Kapee extends FluidCheckout {

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
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Checkout steps
		add_action( 'fc_checkout_header', array( $this, 'maybe_output_kapee_checkout_steps_section' ), 10 );

		// Settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Page title
		$this->maybe_remove_page_title();
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function change_fc_content_section_class( $class ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $class; }

		return $class . ' col-md-12';
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if plugin function isn't available
		if ( ! function_exists( 'kapee_get_option' ) ) { return $attributes; }

		// Desktop settings
		$desktop_settings = '';
		if ( true == kapee_get_option( 'sticky_header', 0 ) ) {
			$desktop_settings = '"md": { "breakpointInitial": 993, "breakpointFinal": 10000, "selector": ".header-sticky" }';
		}

		// Tablet settings
		$tablet_settings = '';
		if ( true == kapee_get_option( 'sticky-header-tablet', 0 ) ) {
			$tablet_settings = '"sm": { "breakpointInitial": 481, "breakpointFinal": 992, "selector": ".site-header .header-sticky" }';
		}

		// Mobile settings
		$mobile_settings = '';
		if ( true == kapee_get_option( 'sticky-header-mobile', 0 ) ) {
			$mobile_settings = '"xs": { "breakpointInitial": 0, "breakpointFinal": 480, "selector": ".site-header .header-sticky" }';
		}

		// Only keep non-empty values
		$settings = '';
		$settings = array_filter( array( $mobile_settings, $tablet_settings, $desktop_settings ), function( $value ) {
			return ! empty( $value );
		} );

		// Concatenate values with a comma
		$settings = implode( ', ', $settings );

		// Add the settings to the data attribute
		$attributes['data-sticky-relative-to'] = "{ {$settings} }";

		return $attributes;
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param  array  $settings  Array with all settings for the current section.
	 */
	public function add_settings( $settings ) {
		// Add new settings
		$settings_new = array(
			array(
				'title' => __( 'Theme Kapee', 'fluid-checkout' ),
				'type'  => 'title',
				'id'    => 'fc_integrations_theme_kapee_options',
			),

			array(
				'title'           => __( 'Checkout progress', 'fluid-checkout' ),
				'desc'            => __( 'Output the checkout steps section from the Kapee theme on the checkout, cart and order received pages.', 'fluid-checkout' ) . ' ' . FluidCheckout_Admin::instance()->get_documentation_link_html( 'https://fluidcheckout.com/docs/compat-theme-kapee/' ),
				'id'              => 'fc_compat_theme_kapee_output_checkout_steps_section',
				'type'            => 'checkbox',
				'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_compat_theme_kapee_output_checkout_steps_section' ),
				'autoload'        => false,
			),

			array(
				'type' => 'sectionend',
				'id'    => 'fc_integrations_theme_kapee_options',
			),
		);

		$settings = array_merge( $settings, $settings_new );

		return $settings;
	}



	/**
	 * Maybe output the checkout steps section from the Kapee theme.
	 */
	public function maybe_output_kapee_checkout_steps_section() {
		// Bail if not using distraction free header and footer
		if ( ! FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return; }

		// Bail if Kapee section output is disabled in the plugin settings
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_compat_theme_kapee_output_checkout_steps_section' ) ) { return; }

		// Bail if functions aren't available
		if ( ! function_exists( 'kapee_page_title' ) ) { return; }

		// Get theme's checkout steps section
		kapee_page_title();
	}



	/**
	 * Maybe remove the page title added by the 'kapee_page_title' function when using distraction free header and footer.
	 */
	public function maybe_remove_page_title() {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Bail if not using distraction free header and footer
		if ( ! FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return; }

		// Remove page title
		remove_action( 'kapee_inner_page_title', 'kapee_template_page_title', 10 );
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Bail if plugin function isn't available
		if ( ! function_exists( 'kapee_get_option' ) ) { return $attributes; }

		// Get border width value from theme options
		$border = kapee_get_option( 'site-border' );
		$border_width = '1px';
		if ( ! empty( $border['border-width'] ) ) {
			$border_width = $border['border-width'];
		}

		// Get checkout (place order) button colors from theme options
		$checkout_button = kapee_get_option( 'checkout-button-background' );
		$checkout_button_background_color = '#FB641B';
		$checkout_button_background_color_hover = '#FB641B';
		if ( ! empty( $checkout_button['regular'] ) ) {
			$checkout_button_background_color = $border['regular'];
		}
		if ( ! empty( $checkout_button['hover'] ) ) {
			$checkout_button_background_color_hover = $border['hover'];
		}

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '42px',
				'--fluidcheckout--field--padding-left' => '12px',
				'--fluidcheckout--field--border-color' => 'var(--site-border-color)',
				'--fluidcheckout--field--border-width' => $border_width,
				'--fluidcheckout--field--border-radius' => 'var(--site-border-radius)',
				'--fluidcheckout--field--font-size' => 'var(--site-font-size)',

				// Custom variables
				'--fluidcheckout--kappe--place-order-button--background-color' => $checkout_button_background_color,
				'--fluidcheckout--kappe--place-order-button--background-color--hover' => $checkout_button_background_color_hover,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Kapee::instance();
