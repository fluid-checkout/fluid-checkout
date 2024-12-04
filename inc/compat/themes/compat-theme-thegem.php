<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: TheGem (by Codex Themes).
 */
class FluidCheckout_ThemeCompat_TheGem extends FluidCheckout {

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

		// Settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10 );

		// Header elements
		add_action( 'fc_checkout_header', array( $this, 'maybe_output_thegem_checkout_steps_section' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );
		add_filter( 'fc_apply_button_design_styles', '__return_true', 10 );

		// Terms checkbox template
		add_filter( 'wc_get_template', array( $this, 'revert_terms_template' ), 10, 5 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Remove checkout elements added by the theme
		remove_action( 'woocommerce_before_checkout_form', 'thegem_woocommerce_checkout_scripts', 1 );
		remove_action( 'woocommerce_before_checkout_form', 'thegem_woocommerce_checkout_tabs', 5 );
		remove_action('woocommerce_before_checkout_form', 'thegem_cart_checkout_steps', 5 );
		remove_action('woocommerce_before_thankyou', 'thegem_cart_checkout_steps', 5 );
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 9 );
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 11 );
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_output_all_notices', 4 );
		remove_action( 'woocommerce_before_checkout_form_cart_notices', 'thegem_woocommerce_remove_checkout_template_notices', 1 );
		remove_action( 'woocommerce_before_checkout_form', 'thegem_woocommerce_before_checkout_wrapper_start', 6 );
		remove_action( 'woocommerce_before_checkout_form', 'thegem_woocommerce_before_checkout_wrapper_end', 100 );
		remove_action( 'woocommerce_checkout_after_customer_details', 'thegem_woocommerce_checkout_nav_buttons', 100 );
		remove_action( 'woocommerce_checkout_before_customer_details', 'thegem_woocommerce_customer_details_start', 1 );
		remove_action( 'woocommerce_checkout_after_customer_details', 'thegem_woocommerce_customer_details_end', 1000 );
		remove_action( 'woocommerce_checkout_before_order_review_heading', 'thegem_woocommerce_order_review_start', 1 );
		remove_action( 'woocommerce_checkout_after_order_review', 'thegem_woocommerce_order_review_end', 1000 );
		remove_action( 'woocommerce_after_checkout_form', 'thegem_woocommerce_checkout_form_steps_script', 10 );
		remove_action( 'woocommerce_after_checkout_registration_form', 'thegem_woocommerce_checkout_registration_buttons', 100 );
		remove_action( 'woocommerce_checkout_before_order_review', 'thegem_woocommerce_order_review_table_start', 1 );
		remove_action( 'woocommerce_checkout_after_order_review', 'thegem_woocommerce_order_review_table_end', 1000 );

		// Re-add with higher priority
		add_action( 'woocommerce_before_checkout_form', 'woocommerce_output_all_notices', 10 );

		// Checkout template hooks
		$this->checkout_template_hooks();
	}

	/**
	 * Add checkout template hooks.
	 */
	public function checkout_template_hooks() {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return; }

		// Theme's page title section
		add_action( 'fc_checkout_before_main_section_wrapper', array( $this, 'maybe_display_page_title' ), 10 );

		// Theme's inner container
		add_action( 'fc_checkout_before_main_section', array( $this, 'add_inner_container_opening_tag' ), 10 );
		add_action( 'fc_checkout_after_main_section', array( $this, 'add_inner_container_closing_tag' ), 10 );
	}



	/**
	 * Add opening tag for inner container from the theme.
	 */
	public function add_inner_container_opening_tag() {
		?>
		<div class="container">
		<?php
	}

	/**
	 * Add closing tag for inner container from the theme.
	 */
	public function add_inner_container_closing_tag() {
		?>
		</div>
		<?php
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if theme function isn't available
		if ( ! function_exists( 'thegem_get_option' ) ) { return $attributes; }

		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if fixed header is disabled in the theme
		if ( thegem_get_option( 'disable_fixed_header' ) ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '#site-header.fixed';

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

		return $class . ' block-content';
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
				'title' => __( 'Theme The Gem', 'fluid-checkout' ),
				'type'  => 'title',
				'id'    => 'fc_integrations_theme_thegem_options',
			),

			array(
				'title'           => __( 'Checkout progress', 'fluid-checkout' ),
				'desc'            => __( 'Output the checkout steps section from The Gem theme when using Fluid Checkout header and footer.', 'fluid-checkout' ) . ' ' . FluidCheckout_Admin::instance()->get_documentation_link_html( 'https://fluidcheckout.com/docs/compat-theme-thegem/' ),
				'id'              => 'fc_compat_theme_thegem_output_checkout_steps_section',
				'type'            => 'checkbox',
				'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_compat_theme_thegem_output_checkout_steps_section' ),
				'autoload'        => false,
			),

			array(
				'type' => 'sectionend',
				'id'    => 'fc_integrations_theme_thegem_options',
			),
		);

		$settings = array_merge( $settings, $settings_new );

		return $settings;
	}



	/**
	 * Maybe output the checkout steps section from The Gem theme.
	 */
	public function maybe_output_thegem_checkout_steps_section() {
		// Bail if not using distraction free header and footer
		if ( ! FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return; }

		// Bail if The Gem section output is disabled in the plugin settings
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_compat_theme_thegem_output_checkout_steps_section' ) ) { return; }

		// Bail if The Gem checkout steps function isn't available
		if ( ! function_exists( 'thegem_cart_checkout_title_steps' ) ) { return; }
		
		// Output the checkout steps section from the theme
		echo thegem_cart_checkout_title_steps( '' );
	}



	/**
	 * Revert the 'terms.php' template file to use the original file as located by WooCommerce.
	 */
	public function revert_terms_template( $template, $template_name, $args, $template_path, $default_path ) {
		// Bail if necessary WooCommerce functions are not available
		if ( ! function_exists( 'WC' ) || ! method_exists( WC(), 'plugin_path' ) ) { return $template; }

		if ( $template_name == 'checkout/terms.php' ) {
			$default_path = WC()->plugin_path() . '/templates/';
			$template = $default_path . $template_name;
		}

		return $template;
	}



	/**
	 * Maybe display the default page title from The Gem theme
	 */
	public function maybe_display_page_title() {
		// Bail if theme function isn't available
		if ( ! function_exists( 'thegem_page_title' ) ) { return; }

		// Get page title
		$page_title = thegem_page_title();

		// Bail if no page title
		if ( empty( $page_title ) ) { return; }

		// Output page title
		echo $page_title;
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
				'--fluidcheckout--field--height' => '40px',
				'--fluidcheckout--field--padding-left' => '18px',
				'--fluidcheckout--field--border-radius' => '3px',
				'--fluidcheckout--field--border-color' => 'var(--thegem-to-form-elements-border-color)',
				'--fluidcheckout--field--background-color--accent' => 'var(--thegem-to-styled-color1)',

				// Primary button color
				'--fluidcheckout--button--primary--border-color' => 'var(--thegem-to-styled-color4)',
				'--fluidcheckout--button--primary--background-color' => 'var(--thegem-to-styled-color4)',
				'--fluidcheckout--button--primary--text-color' => 'var(--thegem-to-button-basic-color)',
				'--fluidcheckout--button--primary--border-color--hover' => 'var(--thegem-to-styled-color4)',
				'--fluidcheckout--button--primary--background-color--hover' => 'transparent',
				'--fluidcheckout--button--primary--text-color--hover' => 'var(--thegem-to-styled-color4)',

				// Secondary button color
				'--fluidcheckout--button--secondary--border-color' => 'var(--thegem-to-styled-color1)',
				'--fluidcheckout--button--secondary--background-color' => 'var(--thegem-to-styled-color1)',
				'--fluidcheckout--button--secondary--text-color' => 'var(--thegem-to-button-basic-color)',
				'--fluidcheckout--button--secondary--border-color--hover' => 'var(--thegem-to-styled-color1)',
				'--fluidcheckout--button--secondary--background-color--hover' => 'transparent',
				'--fluidcheckout--button--secondary--text-color--hover' => 'var(--thegem-to-styled-color1)',

				// Button design styles
				'--fluidcheckout--button--border-radius' => '3px',
				'--fluidcheckout--button--border-width' => '2px',
				'--fluidcheckout--button--font-size' => '14px',
				'--fluidcheckout--button--font-weight' => '700',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_TheGem::instance();
