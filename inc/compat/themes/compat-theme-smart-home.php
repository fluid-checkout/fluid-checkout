<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Smart Home (by Fuel Themes).
 */
class FluidCheckout_ThemeCompat_SmartHome extends FluidCheckout {

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

		// Checkout page template
		add_filter( 'template_include', array( $this, 'checkout_page_template' ), 100 );

		// Template file loader
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_template_checkout_page_template' ), 100, 3 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );

		// Remove theme elements from checkout page
		remove_action( 'woocommerce_checkout_before_customer_details', 'thb_checkout_before_customer_details', 5 );
		remove_action( 'woocommerce_checkout_after_customer_details', 'thb_checkout_after_customer_details', 30 );
		remove_action( 'woocommerce_checkout_after_order_review', 'thb_checkout_after_order_review', 30 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
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

		// Prevent theme from replacing `select2` field assets
		remove_action( 'wp_enqueue_scripts', 'thb_woocommerce_scripts_styles', 10001 );
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
		$plugin_path = self::$directory_path . 'templates/compat/themes/smart-home/fc/checkout-page-template/';

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
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function change_fc_content_section_class( $class ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $class; }

		return $class . ' row';
	}



	/**
	 * Change the element used to position the progress bar and order summary when sticky.
	 * 
	 * @param  array  $attributes  The elements attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '.thb-main-header';

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
				'--fluidcheckout--field--height' => '46px',
				'--fluidcheckout--field--padding-left' => '15px',
				'--fluidcheckout--field--border-radius' => '6px',
				'--fluidcheckout--field--border-color' => '#EAEAEA',
				'--fluidcheckout--field--font-size' => '12px',
				'--fluidcheckout--field--background-color--accent' => '#272525',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_SmartHome::instance();
