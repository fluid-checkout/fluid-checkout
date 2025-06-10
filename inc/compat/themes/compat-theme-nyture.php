<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Nyture (by The WordPress Team).
 */
class FluidCheckout_ThemeCompat_Nyture extends FluidCheckout {

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
		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10 );

		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );
	}



	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Maybe output the Nyture checkout steps section
		add_action( 'template_redirect', array( $this, 'maybe_remove_nyture_checkout_steps_section' ), 20 );
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
				'--fluidcheckout--field--padding-left' => '12px',
				'--fluidcheckout--field--height' => '48px',
				'--fluidcheckout--field--border-radius' => '3px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
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
				'title' => __( 'Theme Nyture', 'fluid-checkout' ),
				'type'  => 'title',
				'id'    => 'fc_integrations_theme_nyture_options',
			),

			array(
				'title'           => __( 'Checkout progress', 'fluid-checkout' ),
				'desc'            => __( 'Output the checkout steps section from the Nyture theme when using Fluid Checkout.', 'fluid-checkout' ),
				'id'              => 'fc_compat_theme_nyture_output_checkout_steps_section',
				'type'            => 'checkbox',
				'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_compat_theme_nyture_output_checkout_steps_section' ),
				'autoload'        => false,
			),

			array(
				'type' => 'sectionend',
				'id'    => 'fc_integrations_theme_nyture_options',
			),
		);

		$settings = array_merge( $settings, $settings_new );

		return $settings;
	}



	/**
	 * Maybe remove the checkout steps section from the Nyture theme.
	 */
	public function maybe_remove_nyture_checkout_steps_section() {
		// Bail if not on cart, checkout or thankyou page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() && ! FluidCheckout_Steps::instance()->is_cart_page_or_fragment() && ! is_wc_endpoint_url( 'order-received' )) { return; }

		// Bail if Nyture section remove is disabled in the plugin settings
		if ( 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_compat_theme_nyture_output_checkout_steps_section' ) ) { return; }

		// Unhook the default checkout steps section
		remove_action( 'woocommerce_before_cart', 'nova_add_shopping_cart_status_menu', 1 );
		remove_action( 'woocommerce_before_checkout_form', 'nova_add_checkout_status_menu', 1 );
		remove_action( 'woocommerce_before_thankyou', 'nova_add_thankyou_status_menu', 1 );
	}

}

FluidCheckout_ThemeCompat_Nyture::instance();
