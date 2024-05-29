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
		// Remove theme hooks related to checkout page
		remove_action( 'wp', 'kenta_modify_template_hooks_after_init', 10 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );

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

		return $class . ' kenta-container container mx-auto px-gutter';
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
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Kenta::instance();