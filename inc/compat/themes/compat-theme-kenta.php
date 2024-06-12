<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Kenta (by WP Moose).
 */
class FluidCheckout_ThemeCompat_Kenta extends FluidCheckout {

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
		// Template custom attributes
		add_filter( 'fc_checkout_html_custom_attributes', array( $this, 'add_html_attributes' ), 10 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Remove theme hooks related to checkout page
		remove_action( 'wp', 'kenta_modify_template_hooks_after_init', 10 );
	}



	/**
	 * Add custom attributes to the html element.
	 *
	 * @param  array  $custom_attributes   HTML attributes.
	 */
	public function add_html_attributes( $custom_attributes ) {
		// Bail if theme function is not available
		if ( ! function_exists( 'kenta_get_html_attributes' ) ) { return $custom_attributes; }

		// Get theme custom attributes
		$theme_custom_attributes = kenta_get_html_attributes();

		// Merge custom attributes
		$custom_attributes = array_merge( $custom_attributes, $theme_custom_attributes );

		return $custom_attributes;
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function change_fc_content_section_class( $class ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $class; }

		return $class . ' kenta-container container mx-auto px-gutter';
	}



	/**
	 * Change the element used to position the progress bar and order summary when sticky.
	 * 
	 * @param  array  $attributes  The elements attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if theme function is not available
		if ( ! function_exists( 'kenta_app' ) ) { return $attributes; }

		// Get theme application instance
		$app = kenta_app( 'CZ' );

		// Bail if object or method isn't not available
		if ( ! is_object( $app ) || ! method_exists( $app, 'checked' ) ) { return $attributes; }

		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if sticky header is disabled in theme options
		if ( ! $app->checked( 'kenta_sticky_header' ) ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = 'header .kenta-sticky';

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
				'--fluidcheckout--field--height' => 'var(--kenta-form-control-height, 45px)',
				'--fluidcheckout--field--padding-left' => 'var(--kenta-form-control-paddding, 10.2px)',
				'--fluidcheckout--field--border-radius' => 'var(--kenta-form-control-radius, 2px)',
				'--fluidcheckout--field--border-color' => 'var(--kenta-form-border-color, var(--kenta-base-300))',
				'--fluidcheckout--field--font-size' => '13.6px',
				'--fluidcheckout--field--background-color--accent' => 'var(--kenta-primary-color)',
			),
			':root[data-kenta-theme="dark"]' => FluidCheckout_DesignTemplates::instance()->get_css_variables_dark_mode(),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Kenta::instance();
