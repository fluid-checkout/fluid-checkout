<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Go (by GoDaddy).
 */
class FluidCheckout_ThemeCompat_Go extends FluidCheckout {

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
		// Settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10, 2 );
		add_filter( 'fc_enable_compat_theme_account_style_go', array( $this, 'maybe_disable_compat_styles_account_pages' ), 10 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
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
				'title' => __( 'Theme Go', 'fluid-checkout' ),
				'type'  => 'title',
				'id'    => 'fc_integrations_theme_go_options',
			),

			array(
				'title'           => __( 'My account layout', 'fluid-checkout' ),
				'desc'            => __( 'Enable wide layout on "My account" page.', 'fluid-checkout' ),
				'id'              => 'fc_compat_theme_go_enable_account_wide_layout',
				'type'            => 'checkbox',
				'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_compat_theme_go_enable_account_wide_layout' ),
				'autoload'        => false,
			),

			array(
				'type' => 'sectionend',
				'id'    => 'fc_integrations_theme_go_options',
			),
		);

		$settings = array_merge( $settings, $settings_new );

		return $settings;
	}



	/**
	 * Maybe disable the compatibility styles for the account pages.
	 * 
	 */
	public function maybe_disable_compat_styles_account_pages( $is_enabled ) {
		// Change the status basedon the settings
		$is_enabled = 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_compat_theme_go_enable_account_wide_layout' );

		return $is_enabled;
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function change_fc_content_section_class( $class ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $class; }

		return $class . ' content-area__wrapper max-w-wide w-full m-auto px';
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if sticky header is not enabled in the theme settings
		if ( true != get_theme_mod( 'sticky_header', false ) ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '#site-header';

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
				'--fluidcheckout--field--height' => '52.18px',
				'--fluidcheckout--field--padding-left' => '13.6px',
				'--fluidcheckout--field--border-radius' => '4px',
				'--fluidcheckout--field--border-color' => 'var(--go-input--border-color, var(--go-heading--color--text))',
				'--fluidcheckout--field--border-width' => '2px',
				'--fluidcheckout--field--background-color--accent' => 'var(--go--color--secondary)',

				// Primary button colors
				'--fluidcheckout--button--primary--border-color' => 'var(--go-button--color--background, var(--go--color--primary))',
				'--fluidcheckout--button--primary--background-color' => 'var(--go-button--color--background, var(--go--color--primary))',
				'--fluidcheckout--button--primary--text-color' => '#fff',
				'--fluidcheckout--button--primary--border-color--hover' => 'var(--go-button-interactive--color--background, var(--go--color--secondary))',
				'--fluidcheckout--button--primary--background-color--hover' => 'var(--go-button-interactive--color--background, var(--go--color--secondary))',
				'--fluidcheckout--button--primary--text-color--hover' => '#fff',

				// Secondary button colors
				'--fluidcheckout--button--secondary--border-color' => 'var(--go-button--color--background, var(--go--color--primary))',
				'--fluidcheckout--button--secondary--background-color' => 'var(--go-button--color--background, var(--go--color--primary))',
				'--fluidcheckout--button--secondary--text-color' => '#fff',
				'--fluidcheckout--button--secondary--border-color--hover' => 'var(--go-button-interactive--color--background, var(--go--color--secondary))',
				'--fluidcheckout--button--secondary--background-color--hover' => 'var(--go-button-interactive--color--background, var(--go--color--secondary))',
				'--fluidcheckout--button--secondary--text-color--hover' => '#fff',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Go::instance();
