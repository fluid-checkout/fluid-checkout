<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Talemy (by ThemeSpirit).
 */
class FluidCheckout_ThemeCompat_Talemy extends FluidCheckout {

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

		// Remove theme smart setting from sticky navbar option
		add_action( 'init', array( $this, 'migrate_nav_sticky_style_from_smart' ), 100 );
		add_action( 'customize_register', array( $this, 'remove_nav_sticky_smart_customizer_choice' ), 999 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );

		// Buttons
		add_filter( 'fc_place_order_button_classes', array( $this, 'remove_place_order_button_alt_class' ), 10 );

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
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Fixes contact change button not being displayed.
		$this->remove_action_for_class( 'woocommerce_checkout_before_customer_details', array( 'Talemy_WooCommerce', 'customer_details_start' ), 0 );

		// Fixes the product summary not being displayed.
		$this->remove_action_for_class( 'woocommerce_checkout_after_customer_details', array( 'Talemy_WooCommerce', 'customer_details_end' ), 3000 );
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
	 * Remove alt class from place order button.
	 *
	 * @param  string  $classes  Button classes.
	 */
	public function remove_place_order_button_alt_class( $classes ) {
		return str_replace( ' alt', '', $classes );
	}



	/**
	 * Migrate sticky navbar option from smart to always.
	 */
	public function migrate_nav_sticky_style_from_smart() {
		$sticky_style = get_theme_mod( 'nav_sticky_style', null );

		// Bail if sticky navbar is explicitly set to another value
		if ( null !== $sticky_style && 'smart' !== $sticky_style ) { return; }

		// Migrate smart setting or unset default (which defaults to smart in Talemy)
		set_theme_mod( 'nav_sticky_style', 'always' );
	}



	/**
	 * Remove smart option from sticky navbar customizer control.
	 *
	 * @param WP_Customize_Manager $wp_customize  Customizer object.
	 */
	public function remove_nav_sticky_smart_customizer_choice( $wp_customize ) {
		$control = $wp_customize->get_control( 'nav_sticky_style' );

		// Bail if control is not available
		if ( ! $control || empty( $control->choices ) || ! isset( $control->choices[ 'smart' ] ) ) { return; }

		unset( $control->choices[ 'smart' ] );
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if theme functions are not available
		if ( ! function_exists( 'talemy_get_option' ) ) { return $attributes; }

		// Bail if sticky navbar is disabled
		$sticky_navbar = talemy_get_option( 'nav_sticky_style' );
		if ( 'disable' === $sticky_navbar ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '#header .navbar';

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
				'--fluidcheckout--field--height'                     => '44px',
				'--fluidcheckout--field--padding-left'               => '15px',
				'--fluidcheckout--field--font-size'                  => '14px',
				'--fluidcheckout--field--border-color'               => 'var(--theme-color-border)', // Is available in body selector.
				'--fluidcheckout--field--background-color--accent'   => 'var(--theme-color-background)', // Is available in body selector.
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Talemy::instance();
