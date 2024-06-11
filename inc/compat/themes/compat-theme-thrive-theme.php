<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Thrive Theme (by Thrive Themes).
 */
class FluidCheckout_ThemeCompat_ThriveTheme extends FluidCheckout {

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

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Checkout template hooks
		$this->checkout_template_hooks();
	}

	/**
	 * Add checkout template hooks.
	 */
	public function checkout_template_hooks() {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return; }

		// Theme header elements
		add_action( 'fc_checkout_before_main_section_wrapper', array( $this, 'add_header_theme_elements' ), 10 );

		// Theme footer elements
		add_action( 'fc_checkout_after_main_section_wrapper', array( $this, 'add_footer_theme_elements' ), 10 );
	}



	/**
	 * Add header elements from the theme.
	 */
	public function add_header_theme_elements() {
		// Bail if theme's class is not available
		if ( ! class_exists( 'Thrive_Template' ) ) { return; }

		// Bail if theme's class method is not available
		if ( ! method_exists( 'Thrive_Template', 'render_theme_hf_section' ) ) { return; }

		$header = '<main id="wrapper">';
		$header .= Thrive_Template::instance()->render_theme_hf_section( THRIVE_HEADER_SECTION );

		echo $header;
	}



	/**
	 * Add footer elements from the theme.
	 */
	function add_footer_theme_elements() {
		// Bail if theme's class is not available
		if ( ! class_exists( 'Thrive_Template' ) ) { return; }

		// Bail if theme's class method is not available
		if ( ! method_exists( 'Thrive_Template', 'render_theme_hf_section' ) ) { return; }

		$footer = Thrive_Template::instance()->render_theme_hf_section( THRIVE_FOOTER_SECTION );
		$footer .= '</main>';

		echo $footer;
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
				'--fluidcheckout--field--height' => '43px',
				'--fluidcheckout--field--padding-left' => '10px',
				'--fluidcheckout--field--border-radius' => '3px',
				'--fluidcheckout--field--border-color' => 'rgba( 151, 151, 151, 0.5 )',
				'--fluidcheckout--field--background-color--accent' => 'var(--tcb-skin-color-0)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_ThriveTheme::instance();
