<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Bookshelf (by ThemeREX).
 */
class FluidCheckout_ThemeCompat_Bookshelf extends FluidCheckout {

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

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10 );

		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );
	}



	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Maybe output the Bookshelf checkout steps section
		add_action( 'template_redirect', array( $this, 'maybe_remove_bookshelf_checkout_steps_section' ), 20 );
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
				// Using fixed values since the theme does not use its customized font and color options.
				'--fluidcheckout--field--padding-left' => '8px',
				'--fluidcheckout--field--height' => '40.88px',
				'--fluidcheckout--field--border-radius' => '4px',
				'--fluidcheckout--field--border-color' => 'rgba(32, 7, 7, 0.8)',
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

		$attributes['data-sticky-relative-to'] = '.top_panel_navi';

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
				'title' => __( 'Theme Bookshelf', 'fluid-checkout' ),
				'type'  => 'title',
				'id'    => 'fc_integrations_theme_bookshelf_options',
			),

			array(
				'title'           => __( 'Checkout progress', 'fluid-checkout' ),
				'desc'            => __( 'Output the checkout steps section from the Bookshelf theme when using Fluid Checkout.', 'fluid-checkout' ),
				'id'              => 'fc_compat_theme_bookshelf_output_checkout_steps_section',
				'type'            => 'checkbox',
				'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_compat_theme_bookshelf_output_checkout_steps_section' ),
				'autoload'        => false,
			),

			array(
				'type' => 'sectionend',
				'id'    => 'fc_integrations_theme_bookshelf_options',
			),
		);

		$settings = array_merge( $settings, $settings_new );

		return $settings;
	}



	/**
	 * Maybe remove the checkout steps section from the Bookshelf theme.
	 */
	public function maybe_remove_bookshelf_checkout_steps_section() {
		// Bail if not on cart, checkout or thankyou page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() && ! FluidCheckout_Steps::instance()->is_cart_page_or_fragment() && ! is_wc_endpoint_url( 'order-received' )) { return; }

		// Bail if Bookshelf section remove is disabled in the plugin settings
		if ( 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_compat_theme_bookshelf_output_checkout_steps_section' ) ) { return; }

		// Unhook the default checkout steps section
		remove_action( 'woocommerce_before_cart', 'woocommerce_show_product_status_bar');
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_show_product_status_bar');
		remove_action( 'woocommerce_before_thankyou', 'woocommerce_show_product_status_bar');
	}

}

FluidCheckout_ThemeCompat_Bookshelf::instance(); 
