<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Salient (by ThemeNectar).
 */
class FluidCheckout_ThemeCompat_Salient extends FluidCheckout {

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
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
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
	 * Change the element used to position the progress bar and order summary when sticky.
	 * 
	 * @param  array  $attributes  The elements attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '#header-outer';

		return $attributes;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Bail if theme function is not available
		if ( ! function_exists( 'get_nectar_theme_options' ) ) { return $css_variables; }

		$theme_options = get_nectar_theme_options();

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Primary button styles
				'--fluidcheckout--button--primary--border-color' => $theme_options['accent-color'],
				'--fluidcheckout--button--primary--background-color' => $theme_options['accent-color'],
				'--fluidcheckout--button--primary--text-color' => '#ffffff',
				'--fluidcheckout--button--primary--border-color--hover' => $theme_options['accent-color'],
				'--fluidcheckout--button--primary--background-color--hover' => $theme_options['accent-color'],
				'--fluidcheckout--button--primary--text-color--hover' => '#ffffff',

				// Secondary button styles
				'--fluidcheckout--button--secondary--border-color' => $theme_options['accent-color'],
				'--fluidcheckout--button--secondary--background-color' => $theme_options['extra-color-3'],
				'--fluidcheckout--button--secondary--text-color' => '#ffffff',
				'--fluidcheckout--button--secondary--border-color--hover' => $theme_options['accent-color'],
				'--fluidcheckout--button--secondary--background-color--hover' => $theme_options['accent-color'],
				'--fluidcheckout--button--secondary--text-color--hover' => '#ffffff',

				// Form field styles
				'--fluidcheckout--field--height' => '42.29px',
				'--fluidcheckout--field--padding-left' => '10px',
				'--fluidcheckout--field--border-radius' => '4px',
				'--fluidcheckout--field--font-size' => '14px',
				'--fluidcheckout--field--background-color--accent' => $theme_options['accent-color'],
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Salient::instance();
