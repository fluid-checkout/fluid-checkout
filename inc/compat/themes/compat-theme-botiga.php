<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Botiga (by aThemes).
 */
class FluidCheckout_ThemeCompat_Botiga extends FluidCheckout {

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
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );

		// Theme options
		add_filter( 'theme_mod_shop_checkout_layout', array( $this, 'change_theme_option_shop_checkout_layout' ), 100 );
		add_filter( 'theme_mod_checkout_distraction_free', array( $this, 'change_theme_option_checkout_distraction_free' ), 100 );

		// Remove theme's function causing fatal error
		remove_filter( 'woocommerce_loop_add_to_cart_link', 'botiga_filter_loop_add_to_cart', 10, 3 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Remove theme elements
		remove_action( 'woocommerce_checkout_before_order_review_heading', 'botiga_wrap_order_review_before', 5 );
		remove_action( 'woocommerce_checkout_after_order_review', 'botiga_wrap_order_review_after', 15 );
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if sticky header is not enabled
		$sticky_header = get_theme_mod( 'enable_sticky_header', 0 );
		if ( ! $sticky_header ) { return $attributes; }

		// Get sticky rows option
		$sticky_row = get_theme_mod( 'botiga_section_hb_wrapper__header_builder_sticky_row', 'main-header-row' );

		// Maybe change the relative ID based on the sticky row option
		switch ( $sticky_row ) {
			case 'all':
			case 'main-header-row':
				$attributes['data-sticky-relative-to'] = '.bhfb-header.has-sticky-header';
				break;
			case 'below-header-row':
				$attributes['data-sticky-relative-to'] = '.bhfb-header.has-sticky-header .is-sticky';
				break;
		}

		return $attributes;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Get theme colors
		$button_primary_border_color = get_theme_mod( 'button_border_color', '#212121' );
		$button_primary_background_color = get_theme_mod( 'button_background_color', '#212121' );
		$button_primary_color = get_theme_mod( 'button_color', '#FFF' );
		$button_primary_border_color_hover = get_theme_mod( 'button_border_color_hover', '#757575' );
		$button_primary_background_color_hover = get_theme_mod( 'button_background_color_hover', '#757575' );
		$button_primary_color_hover = get_theme_mod( 'button_color_hover', '#FFF' );

		// Add CSS variables
		$new_css_variables = array(
			':root body' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '52.88px',
				'--fluidcheckout--field--padding-left' => '16px',
				'--fluidcheckout--field--border-radius' => '0',
				'--fluidcheckout--field--border-color' => 'var(--bt-color-forms-borders, #212121)',
				'--fluidcheckout--field--background-color--accent' => 'var(--bt-color-button-bg)',

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing--select-alt' => '34px',

				// Button color styles - primary
				'--fluidcheckout--button--primary--border-color' => $button_primary_border_color,
				'--fluidcheckout--button--primary--background-color' => $button_primary_background_color,
				'--fluidcheckout--button--primary--text-color' => $button_primary_color,
				'--fluidcheckout--button--primary--border-color--hover' => $button_primary_border_color_hover,
				'--fluidcheckout--button--primary--background-color--hover' => $button_primary_background_color_hover,
				'--fluidcheckout--button--primary--text-color--hover' => $button_primary_color_hover,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}



	/**
	 * Change the checkout layout theme option to always use "Layout 1", which is the default layout.
	 * 
	 * @param  string  $value  The current value.
	 */
	public function change_theme_option_shop_checkout_layout( $value ) {
		return 'layout1';
	}

	/**
	 * Change the checkout distraction free header theme option to always be disabled.
	 * 
	 * @param  string  $value  The current value.
	 */
	public function change_theme_option_checkout_distraction_free( $value ) {
		// 0 = disabled
		return 0;
	}

}

FluidCheckout_ThemeCompat_Botiga::instance();
