<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Colibri WP (by Extend Themes).
 */
class FluidCheckout_ThemeCompat_ColibriWP extends FluidCheckout {

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

		return $class . ' h-section-boxed-container';
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
				'--fluidcheckout--field--padding-left' => '12px',
				'--fluidcheckout--field--font-size' => '16px',
				'--fluidcheckout--field--border-color' => '#aaaaaa',
				'--fluidcheckout--field--border-width' => '1px',
				'--fluidcheckout--field--border-radius' => '3px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_ColibriWP::instance();
