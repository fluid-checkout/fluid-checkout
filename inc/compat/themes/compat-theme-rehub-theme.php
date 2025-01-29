<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Rehub theme (by Wpsoul).
 */
class FluidCheckout_ThemeCompat_RehubTheme extends FluidCheckout {

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

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Order review section layout
		remove_action( 'woocommerce_checkout_before_order_review_heading', 'rehub_woo_order_checkout', 10 );
		remove_action( 'woocommerce_checkout_after_order_review', 'rehub_woo_after_order_checkout', 10 );
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if required theme function is not available
		if ( ! function_exists( 'rehub_option' ) ) { return $attributes; }

		$is_sticky = rehub_option( 'rehub_sticky_nav' );

		// Bail if theme's conditions for sticky header are not met
		if ( ! $is_sticky ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '.re-stickyheader';

		return $attributes;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Bail if theme function is not available
		if ( ! function_exists( 'rehub_option' ) ) { return $css_variables; }

		// Default values
		$field_height = '37.14px';
		$field_padding_left = '10px';
		$field_font_size = '15px';
		$field_box_shadow = 'none';
		$button_bg_color = rehub_option( 'rehub_btnoffer_color' );
		$button_bg_color_hover = rehub_option( 'rehub_btnoffer_color_hover' );
		$button_text_color = rehub_option( 'rehub_btnoffer_color_text' );
		$button_text_color_hover = rehub_option( 'rehub_btnofferhover_color_text' );

		// Set different values for "System pages" template
		if ( is_page_template( 'template-systempages.php' ) ) {
			$field_height = '47.14px';
			$field_padding_left = '12px';
			$field_font_size = '16px';
			$field_box_shadow = 'inset 0 1px 3px #ddd';
		}
		
		// Maybe use fallback values for colors
		if ( ! $button_bg_color && defined( 'REHUB_BUTTON_COLOR' ) ) {
			$button_bg_color = REHUB_BUTTON_COLOR; 
		}
		if ( ! $button_bg_color_hover ) {
			$button_bg_color_hover = $button_bg_color;
		}
		if ( ! $button_text_color && defined( 'REHUB_BUTTON_COLOR_TEXT' ) ) {
			$button_text_color = REHUB_BUTTON_COLOR_TEXT; 
		}
		if ( ! $button_bg_color_hover ) {
			$button_bg_color_hover = $button_text_color;
		}

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => $field_height,
				'--fluidcheckout--field--padding-left' => $field_padding_left,
				'--fluidcheckout--field--font-size' => $field_font_size,
				'--fluidcheckout--field--box-shadow' => $field_box_shadow,
				'--fluidcheckout--field--border-color' => '#ccc',
				'--fluidcheckout--field--border-width' => '1px',
				'--fluidcheckout--field--border-radius' => '4px',

				// Primary button colors
				'--fluidcheckout--button--primary--border-color' => $button_bg_color,
				'--fluidcheckout--button--primary--background-color' => $button_bg_color,
				'--fluidcheckout--button--primary--text-color' => $button_text_color,
				'--fluidcheckout--button--primary--border-color--hover' => $button_bg_color_hover,
				'--fluidcheckout--button--primary--background-color--hover' => $button_bg_color_hover,
				'--fluidcheckout--button--primary--text-color--hover' => $button_text_color_hover,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_RehubTheme::instance();
