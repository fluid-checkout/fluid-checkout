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

		// Template file loader
		add_filter( 'woocommerce_locate_template', array( $this, 'maybe_locate_template_checkout_page_template' ), 100, 3 );
		
		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Additional header sections from the theme
		$this->maybe_remove_additional_header_sections();

		// Theme's page title section
		add_action( 'fc_checkout_header', array( $this, 'maybe_display_additional_header_sections' ), 10 );
	}



	/**
	 * Maybe remove additional header sections from the theme.
	 */
	public function maybe_remove_additional_header_sections() {
		// Bail if theme sections output is enabled in the plugin settings
		if ( 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_compat_theme_dt_the7_output_additional_header_sections' ) ) { return; }

		// Bail if not on checkout page.
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Remove sections
		remove_action( 'presscore_before_main_container', 'presscore_fancy_header_controller', 15 );
		remove_action( 'presscore_before_main_container', 'presscore_slideshow_controller', 15 );
		remove_action( 'presscore_before_main_container', 'presscore_page_title_controller', 16 );
		remove_action( 'presscore_before_main_container', 'dt_woocommerce_cart_progress', 17 );
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
				'desc'            => __( 'Output additional header sections if enabled in the theme settings.', 'fluid-checkout' ),
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



	/**
	 * Maybe locate template files from this plugin.
	 */
	public function maybe_locate_template_checkout_page_template( $template, $template_name, $template_path ) {
		// Bail if not using distraction free header and footer
		if ( ! FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $template; }

		$_template = null;

		// Set template path to default value when not provided
		if ( ! $template_path ) { $template_path = 'woocommerce/'; };

		// Get plugin path
		$plugin_path = self::$directory_path . 'templates/compat/themes/dt-the7/checkout-page-template/';

		// Get the template from this plugin, if it exists
		if ( file_exists( $plugin_path . $template_name ) ) {
			$_template = $plugin_path . $template_name;

			// Look for template file in the theme
			if ( apply_filters( 'fc_override_template_with_theme_file', false, $template, $template_name, $template_path ) ) {
				$_template_override = locate_template( array(
					trailingslashit( $template_path ) . $template_name,
					$template_name,
				) );
	
				// Check if files exist before changing template
				if ( file_exists( $_template_override ) ) {
					$_template = $_template_override;
				}
			}
		}

		// Use default template
		if ( ! $_template ) {
			$_template = $template;
		}

		// Return what we found
		return $_template;
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
				'--fluidcheckout--field--height' => 'var(--the7-input-height)',
				'--fluidcheckout--field--padding-left' => 'var(--the7-left-input-padding)',
				'--fluidcheckout--field--border-color' => 'var(--the7-input-border-color)',
				'--fluidcheckout--field--border-width' => 'var(--the7-top-input-border-width)',
				'--fluidcheckout--field--border-radius' => 'var(--the7-input-border-radius)',
				'--fluidcheckout--field--font-size' => 'var(--the7-form-md-font)',
				'--fluidcheckout--field--background-color' => 'var(--the7-input-bg-color)',
				'--fluidcheckout--field--background-color--accent' => '#222',

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing--select-alt' => '32px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_DTThe7::instance();
