<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Ireca (by Envato).
 */
class FluidCheckout_ThemeCompat_Ireca extends FluidCheckout {

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
		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );
		
		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Buttons
		add_filter( 'fc_place_order_button_classes', array( $this, 'remove_button_classes' ), 10 );

		// Dequeue Select2 files
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_select2_files' ), 100 );

		// Add integration settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10 );

		// Page layout
		add_filter( 'theme_mod_main_layout', array( $this, 'maybe_force_no_sidebar_page_layout' ), 100 );
		add_filter( 'theme_mod_woo_layout', array( $this, 'maybe_force_no_sidebar_page_layout' ), 100 );
	}



	/**
	 * Change the element used to position the progress bar and order summary when sticky.
	 * 
	 * @param  array  $attributes  The elements attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// For Ireca theme, use the .ovamenu_shrink element for sticky positioning
		$attributes['data-sticky-relative-to'] = '.ovamenu_shrink';

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

		return $class . ' container';
	}



	/**
	 * Remove classes from place order button for Ireca theme.
	 *
	 * @param string $classes Button classes.
	 */
	public function remove_button_classes( $classes ) {
		// Add Ireca theme specific button classes
		$classes = str_replace( 'alt', '', $classes );
		
		return $classes;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Get Ireca theme's main color
		$main_color = get_theme_mod( 'main_color', '#e9a31b' );
		
		// Add CSS variables for Ireca theme
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '37px',
				'--fluidcheckout--field--font-size' => '14px',
				'--fluidcheckout--field--color' => '#200707cc',
				'--fluidcheckout--field--padding-left' => '7px',
				'--fluidcheckout--field--border-radius' => '4px',
				'--fluidcheckout--field--border-color' => '#200707cc',
				'--fluidcheckout--field--border-width' => '1px',
				'--fluidcheckout--field--background-color--accent' => $main_color,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}



	/**
	 * Check if the force no sidebar option is enabled.
	 * 
	 * @return bool True if the option is enabled.
	 */
	public function is_force_wide_layout_enabled() {
		return 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_integrations_ireca_force_no_sidebar' );
	}



	/**
	 * Maybe force "no sidebar" layout on WooCommerce pages.
	 * 
	 * @param   string  $value  The current theme mod value.
	 *
	 * @return  string          The forced layout value or original value.
	 */
	public function maybe_force_no_sidebar_page_layout( $value ) {
		// Bail if option to force wide layout is not enabled
		if ( ! $this->is_force_wide_layout_enabled() ) { return $value; }

		// Bail if not on checkout page, order received page, or account page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() && ! is_order_received_page() && ! is_view_order_page() && ! is_account_page() ) { return $value; }

		// Otherwise, force "no sidebar" layout from theme
		return 'no_sidebar';
	}



	/**
	 * Add integration settings for the theme.
	 * 
	 * @param   array  $settings  The existing integration settings.
	 *
	 * @return  array             The updated integration settings.
	 */
	public function add_settings( $settings ) {
		// Add new settings
		$settings_new = array(
			array(
				'title' => __( 'Theme Ireca', 'fluid-checkout' ),
				'type'  => 'title',
				'id'    => 'fc_integrations_theme_ireca_options',
			),

			array(
				'title'           => __( 'WooCommerce page layout', 'fluid-checkout' ),
				'desc'            => __( 'Force wide layout on WooCommerce pages', 'fluid-checkout' ),
				'desc_tip'        => __( 'This ensures the content section is not too narrow on WooCommerce pages including checkout, cart, order received, order pay and account pages.', 'fluid-checkout' ),
				'id'              => 'fc_integrations_ireca_force_no_sidebar',
				'type'            => 'checkbox',
				'default'         => 'yes',
				'autoload'        => false,
			),

			array(
				'type' => 'sectionend',
				'id'    => 'fc_integrations_theme_ireca_options',
			),
		);

		$settings = array_merge( $settings, $settings_new );

		return $settings;
	}



	/**
	 * Dequeue theme's Select2 files.
	 */
	public function dequeue_select2_files() {
		// Dequeue theme's Select2 files
		wp_dequeue_style( 'select2_ireca' );
		wp_dequeue_script( 'select2_ireca' );
	}

}

FluidCheckout_ThemeCompat_Ireca::instance();
