<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: PressMart (by PressLayouts).
 */
class FluidCheckout_ThemeCompat_PressMart extends FluidCheckout {

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

		// Settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Checkout page hooks
		$this->checkout_hooks();
	}



	/*
	* Add or remove checkout page hooks.
	*/
	public function checkout_hooks() {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'maybe_change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'maybe_change_sticky_elements_relative_header' ), 20 );

		// Theme's checkout steps
		add_action( 'wp', array( $this, 'maybe_output_or_remove_pressmart_checkout_steps_section' ), 1001 );
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function maybe_change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if required functions are not available
		if ( ! function_exists( 'pressmart_get_option' ) ) { return $attributes; }

		// Get sticky header setting
		$header_is_sticky = pressmart_get_option( 'header-sticky', 0 );

		// Bail if option is not enabled on the plugin
		if ( ! $header_is_sticky ) { return $attributes; }

		// Get sticky header class ending
		$header_selector_part = pressmart_get_option( 'header-sticky-part', 'main' );

		// Append class ending to the static header class as per theme's requirements
		$header_selector = '.header-' . $header_selector_part;

		// Change the relative ID
		$attributes['data-sticky-relative-to'] = $header_selector;

		return $attributes;
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function change_fc_content_section_class( $class ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $class; }

		return $class . ' col-12';
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Bail if theme function is not available
		if ( ! function_exists( 'pressmart_get_option' ) ) { return $css_variables; }

		// Get theme colors
		$primary_button_color = pressmart_get_option( 'checkout-button-background', array(
			'regular'     => '#9e7856',
			'hover'       => '#ae8866',
		) );
		$primary_text_color = pressmart_get_option( 'checkout-button-color', array(
			'regular'     => '#ffffff',
			'hover'       => '#fcfcfc',
		) );
		$secondary_button_color = pressmart_get_option( 'button-background', array(
			'regular'     => '#059473',
			'hover'       => '#048567',
		) );
		$secondary_text_color = pressmart_get_option( 'button-color', array(
			'regular'     => '#ffffff',
			'hover'       => '#fcfcfc',
		) );

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '42px',
				'--fluidcheckout--field--padding-left' => '12px',
				'--fluidcheckout--field--background-color--accent' => $secondary_button_color['regular'],

				// Primary button colors
				'--fluidcheckout--button--primary--border-color' => $primary_button_color['regular'],
				'--fluidcheckout--button--primary--background-color' => $primary_button_color['regular'],
				'--fluidcheckout--button--primary--text-color' => $primary_text_color['regular'],
				'--fluidcheckout--button--primary--border-color--hover' => $primary_button_color['hover'],
				'--fluidcheckout--button--primary--background-color--hover' => $primary_button_color['hover'],
				'--fluidcheckout--button--primary--text-color--hover' => $primary_text_color['hover'],

				// Secondary button colors
				'--fluidcheckout--button--secondary--border-color' => $secondary_button_color['regular'],
				'--fluidcheckout--button--secondary--background-color' => $secondary_button_color['regular'],
				'--fluidcheckout--button--secondary--text-color' => $secondary_text_color['regular'],
				'--fluidcheckout--button--secondary--border-color--hover' => $secondary_button_color['hover'],
				'--fluidcheckout--button--secondary--background-color--hover' => $secondary_button_color['hover'],
				'--fluidcheckout--button--secondary--text-color--hover' => $secondary_text_color['hover'],
			),
		);
		
		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 */
	public function add_settings( $settings ) {

		// Add new settings
		$settings_new = array(
			array(
				'title' => __( 'Theme Pressmart', 'fluid-checkout' ),
				'type'  => 'title',
				'id'    => 'fc_integrations_theme_pressmart_options',
			),

			array(
				'title'           => __( 'Checkout progress', 'fluid-checkout' ),
				'desc'            => __( 'Output the checkout steps section from the Pressmart theme on the checkout, cart and order received pages.', 'fluid-checkout' ) . ' ' . FluidCheckout_Admin::instance()->get_documentation_link_html( 'https://fluidcheckout.com/docs/compat-theme-pressmart/' ),
				'id'              => 'fc_compat_theme_pressmart_output_checkout_steps_section',
				'type'            => 'checkbox',
				'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_compat_theme_pressmart_output_checkout_steps_section' ),
				'autoload'        => false,
			),

			array(
				'type' => 'sectionend',
				'id'    => 'fc_integrations_theme_pressmart_options',
			),
		);

		$settings = array_merge( $settings, $settings_new );

		return $settings;
	}



	/**
	 * Maybe output the checkout steps section from the Pressmart theme.
	 */
	public function maybe_output_or_remove_pressmart_checkout_steps_section() {
		// Bail if not on the checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }
		
		// Maybe output the checkout steps section from the Pressmart theme
		if ( 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_compat_theme_pressmart_output_checkout_steps_section' ) ) {
			// Add checkout steps
			add_action( 'fc_checkout_header', 'pressmart_page_title', 20 );
		}
		else {
			// Remove the checkout steps section from the Pressmart theme
			remove_action( 'pressmart_page_title', 'pressmart_page_title', 10 );
		}
	}

}

FluidCheckout_ThemeCompat_PressMart::instance();
