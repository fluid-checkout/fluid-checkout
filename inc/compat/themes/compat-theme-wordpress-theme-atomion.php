<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: WordPress Theme Atomion (by MarketPress).
*/
class FluidCheckout_ThemeCompat_WordPressThemeAtomion extends FluidCheckout {

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

		// General
		add_filter( 'body_class', array( $this, 'add_body_class' ), 10 );

		// Settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );
		add_filter( 'fc_apply_button_design_styles', '__return_true', 10 );

		// Cart items description
		add_filter( 'atomion_wc_checkout_description_show_excerpt_with_markup', array( $this, 'maybe_change_cart_item_description_html' ), 10 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Theme elements
		remove_action( 'woocommerce_after_checkout_form', 'atomion_wc_required_fields_note', 10 );
		remove_action( 'woocommerce_review_order_before_submit', 'atomion_checkout_go_back_button', 10 );
		remove_action( 'woocommerce_checkout_order_review', 'atomion_checkout_go_back_button', 50 );

		// Order progress
		if ( FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) {
			remove_action( 'atomion_breadcrumb', 'atomion_order_progress', 15 );
		}
		if ( 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_compat_theme_atomion_display_order_progress' ) ) {
			add_action( 'woocommerce_before_checkout_form_cart_notices', 'atomion_order_progress', 10 );
		}

		// Form fields
		if ( 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_compat_theme_atomion_display_field_labels' ) ) {
			remove_filter( 'woocommerce_default_address_fields', 'atomion_wc_checkout_fields_set_placeholder', 20, 1 );
			remove_filter( 'woocommerce_billing_fields', 'atomion_wc_checkout_fields_set_placeholder_additonal_fields', 20, 1 );
			remove_filter( 'woocommerce_checkout_fields', 'atomion_wc_checkout_fields_remove_label', 10 );
		}
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
				'title' => __( 'Theme Atomion', 'fluid-checkout' ),
				'type'  => 'title',
				'id'    => 'fc_integrations_theme_atomion_options',
			),

			array(
				'title'           => __( 'Order progress', 'fluid-checkout' ),
				'desc'            => __( 'Display order progress from the theme', 'fluid-checkout' ),
				'id'              => 'fc_compat_theme_atomion_display_order_progress',
				'type'            => 'checkbox',
				'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_compat_theme_atomion_display_order_progress' ),
				'autoload'        => false,
			),

			array(
				'title'           => __( 'Checkout fields', 'fluid-checkout' ),
				'desc'            => __( 'Display the form field labels visible on the page', 'fluid-checkout' ),
				'desc_tip'        => __( 'It is recommended to keep this option enabled for a better user experience.', 'fluid-checkout' ),
				'id'              => 'fc_compat_theme_atomion_display_field_labels',
				'type'            => 'checkbox',
				'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_compat_theme_atomion_display_field_labels' ),
				'autoload'        => false,
			),

			array(
				'type' => 'sectionend',
				'id'    => 'fc_integrations_theme_atomion_options',
			),
		);

		$settings = array_merge( $settings, $settings_new );

		return $settings;
	}



	/**
	 * Add page body class for feature detection.
	 *
	 * @param array $classes Classes for the body element.
	 */
	public function add_body_class( $classes ) {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return $classes; }

		$add_classes = array();

		// Add extra class when displaying field labels
		if ( 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_compat_theme_atomion_display_field_labels' ) ) {
			$add_classes[] = 'has-visible-form-field-labels';
		}

		return array_merge( $classes, $add_classes );
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '.header-main.sticky';

		return $attributes;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Get theme main color
		$main_color = get_theme_mod( 'gc_primary_color_setting', '#37B9E3' );

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '50px',
				'--fluidcheckout--field--padding-left' => '12px',
				'--fluidcheckout--field--border-radius' => '3px',
				'--fluidcheckout--field--border-width' => '3px',
				'--fluidcheckout--field--border-color' => '#ddd',
				'--fluidcheckout--field--background-color--accent' => $main_color,

				// Primary button color
				'--fluidcheckout--button--primary--border-color' => $main_color,
				'--fluidcheckout--button--primary--background-color' => $main_color,
				'--fluidcheckout--button--primary--border-color--hover' => $main_color,
				'--fluidcheckout--button--primary--background-color--hover' => 'transparent',
				'--fluidcheckout--button--primary--text-color--hover' => $main_color,

				// Secondary button color
				'--fluidcheckout--button--secondary--border-color--hover' => $main_color,
				'--fluidcheckout--button--secondary--background-color--hover' => 'transparent',
				'--fluidcheckout--button--secondary--text-color--hover' => $main_color,

				// Button design styles
				'--fluidcheckout--button--border-width' => '3px',
				'--fluidcheckout--button--height' => '50px',
				'--fluidcheckout--button--font-weight' => 'bold',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}



	/**
	 * Change the cart item description HTML.
	 *
	 * @param   string   $html    The cart item description HTML.
	 */
	public function change_cart_item_description_html( $html ) {
		$html = str_replace( '<br><small>', '<div class="cart-item__element cart-item__description"><small>', $html );
		$html = str_replace( '</small>', '</small></div>', $html );
		return $html;
	}

	/**
	 * Maybe change the cart item description HTML for the checkout page.
	 *
	 * @param   string   $html    The cart item description HTML.
	 */
	public function maybe_change_cart_item_description_html( $html ) {
		// Bail if not on checkout page or fragment
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return $html; }

		return $this->change_cart_item_description_html( $html );
	}

}

FluidCheckout_ThemeCompat_WordPressThemeAtomion::instance();
