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
		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );
		add_filter( 'fc_apply_button_design_styles', '__return_true', 10 );

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
		remove_action( 'woocommerce_after_checkout_form', 'thegem_woocommerce_checkout_form_steps_script' );
		remove_action( 'woocommerce_after_checkout_registration_form', 'thegem_woocommerce_checkout_registration_buttons', 100 );
		remove_action( 'woocommerce_checkout_before_order_review', 'thegem_woocommerce_order_review_table_start', 1 );
		remove_action( 'woocommerce_checkout_after_order_review', 'thegem_woocommerce_order_review_table_end', 1000 );

		// Re-add with higher priority
		add_action( 'woocommerce_before_checkout_form', 'woocommerce_output_all_notices', 10 );
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Theme colors
		$primary_button_background = '#393d50';
		$primary_button_background_hover = 'transparent';
		$primary_button_text = '#ffffff';
		$primary_button_text_hover = '#393d50';
		$secondary_button_background = '#00bcd4';
		$secondary_button_background_hover = 'transparent';
		$secondary_button_text = '#ffffff';
		$secondary_button_text_hover = '#00bcd4';


		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '40px',
				'--fluidcheckout--field--padding-left' => '18px',
				'--fluidcheckout--field--border-radius' => '3px',
				'--fluidcheckout--field--background-color--accent' => $secondary_button_background,

				// Primary button color
				'--fluidcheckout--button--primary--border-color' => $primary_button_background,
				'--fluidcheckout--button--primary--background-color' => $primary_button_background,
				'--fluidcheckout--button--primary--text-color' => $primary_button_text,
				'--fluidcheckout--button--primary--border-color--hover' => $primary_button_background,
				'--fluidcheckout--button--primary--background-color--hover' => $primary_button_background_hover,
				'--fluidcheckout--button--primary--text-color--hover' => $primary_button_text_hover,

				// // Secondary button color
				'--fluidcheckout--button--secondary--border-color' => $secondary_button_background,
				'--fluidcheckout--button--secondary--background-color' => $secondary_button_background,
				'--fluidcheckout--button--secondary--text-color' => $secondary_button_text,
				'--fluidcheckout--button--secondary--border-color--hover' => $secondary_button_background,
				'--fluidcheckout--button--secondary--background-color--hover' => $secondary_button_background_hover,
				'--fluidcheckout--button--secondary--text-color--hover' => $secondary_button_text_hover,

				// // Button design styles
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
