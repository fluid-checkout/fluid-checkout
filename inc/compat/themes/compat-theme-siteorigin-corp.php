<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: SiteOrigin Corp (by SiteOrigin).
 */
class FluidCheckout_ThemeCompat_SiteOriginCorp extends FluidCheckout {

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
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if theme function isn't available
		if ( ! function_exists( 'siteorigin_setting' ) ) { return $attributes; }

		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if fixed header is disabled in the theme
		if ( ! siteorigin_setting( 'header_sticky' ) ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '.site-header.sticky';

		return $attributes;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Get theme colors
		$primary_color = get_theme_mod( 'theme_settings_typography_accent', '#f14e4e' );
		$input_border_color = get_theme_mod( 'theme_settings_typography_border_dark', '#d6d6d6' );
		$cart_item_text_color = get_theme_mod( 'theme_settings_typography_heading', '#2d2d2d' );
		$cart_item_text_color_hover = get_theme_mod( 'theme_settings_typography_text', '#626262' );

		// Get RGBA hover color if theme's function exists
		if ( method_exists( 'SiteOrigin_Settings_Color', 'hex2rgb' ) ) {
			// Get RGB values
			$primary_color_rgb = SiteOrigin_Settings_Color::hex2rgb( $primary_color );

			// Concatenate array values into RGBA string
			$primary_color_rgba = implode( ',', $primary_color_rgb ) . ', .8';

			// Turn RGBA value into CSS property
			$primary_color_hover = sprintf( 'rgba( %s );' , $primary_color_rgba );
		} else {
			// Use default theme's value
			$primary_color_hover = 'rgba(241, 78, 78, .8)';
		}

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '39px',
				'--fluidcheckout--field--padding-left' => '13px',
				'--fluidcheckout--field--border-radius' => '0px',
				'--fluidcheckout--field--border-color' => $input_border_color,
				'--fluidcheckout--field--font-size' => '14px',
				'--fluidcheckout--field--background-color--accent' => $primary_color,

				// Primary button color
				'--fluidcheckout--button--primary--border-color' => $primary_color,
				'--fluidcheckout--button--primary--background-color' => $primary_color,
				'--fluidcheckout--button--primary--text-color' => '#fff',
				'--fluidcheckout--button--primary--border-color--hover' => $primary_color_hover,
				'--fluidcheckout--button--primary--background-color--hover' => $primary_color_hover,
				'--fluidcheckout--button--primary--text-color--hover' => '#fff',

				// Secondary button color
				'--fluidcheckout--button--secondary--border-color' => $primary_color,
				'--fluidcheckout--button--secondary--background-color' => $primary_color,
				'--fluidcheckout--button--secondary--text-color' => '#fff',
				'--fluidcheckout--button--secondary--border-color--hover' => $primary_color_hover,
				'--fluidcheckout--button--secondary--background-color--hover' => $primary_color_hover,
				'--fluidcheckout--button--secondary--text-color--hover' => '#fff',

				// Custom theme variables
				'--fluidcheckout--siteorigin-corp--cart-item--text-color' => $cart_item_text_color,
				'--fluidcheckout--siteorigin-corp--cart-item--text-color--hover' => $cart_item_text_color_hover,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_SiteOriginCorp::instance();
