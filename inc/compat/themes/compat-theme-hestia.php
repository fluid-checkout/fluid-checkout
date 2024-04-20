<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Hestia (by ThemeIsle).
 */
class FluidCheckout_ThemeCompat_Hestia extends FluidCheckout {

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
		// Late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );

		// Checkout page template
		add_filter( 'template_include', array( $this, 'checkout_page_template' ), 100 );

		// Template file loader
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_template_checkout_page_template' ), 100, 3 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// General CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// CSS variables on edit address page
		add_action( 'fc_css_variables', array( $this, 'add_css_variables_edit_address' ), 20 );
	}



	/**
	 * Replace the checkout page template with our own file.
	 *
	 * @param   String  $template  Template file path.
	 */
	public function checkout_page_template( $template ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $template; }

		// Bail if checkout page template is not enabled
		if ( true !== apply_filters( 'fc_enable_checkout_page_template', true ) ) { return $template; }

		// Bail if not on checkout page.
		if( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return $template; }

		// Locate new checkout page template
		$_template = $this->locate_template_checkout_page_template( $template, 'checkout/page-checkout.php', null );

		// Check if the file exists
		if ( file_exists( $_template ) ) {
			$template = $_template;
		}

		return $template;
	}



	/**
	 * Locate template files from this plugin.
	 */
	public function locate_template_checkout_page_template( $template, $template_name, $template_path ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $template; }
		
		$_template = null;

		// Set template path to default value when not provided
		if ( ! $template_path ) { $template_path = 'woocommerce/'; };

		// Get plugin path
		$plugin_path = self::$directory_path . 'templates/compat/themes/hestia/fc/checkout-page-template/';

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
	 * Maybe add the "main-raised" class from Hestia to the inner container of the page content.
	 */
	public function maybe_add_inner_container_class() {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return; }

		$hestia_general_layout = get_theme_mod( 'hestia_general_layout', apply_filters( 'hestia_boxed_layout_default', 1 ) );

		// Return the class if the Boxed Layout option is not enabled in the Hestia theme
		if ( ! isset( $hestia_general_layout ) || (bool) $hestia_general_layout !== true ) { return; }

		return 'main-raised';
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
				'--fluidcheckout--field--height' => '30.5px',
				'--fluidcheckout--field--padding-left' => '7px',
				'--fluidcheckout--field--background-color--accent' => '#9c27b0',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}



	/**
	 * Add CSS variables to the edit address page.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables_edit_address( $css_variables ) {
		// Bail if not on account address edit page
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_wc_endpoint_url( 'edit-address' ) ) { return $css_variables; }

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '36px', 
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

		$attributes['data-sticky-relative-to'] = '.navbar-fixed-top';

		return $attributes;
	}

}

FluidCheckout_ThemeCompat_Hestia::instance();
