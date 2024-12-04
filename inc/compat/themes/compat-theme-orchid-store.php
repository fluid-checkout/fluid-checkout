<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Orchid Store (by themebeez).
 */
class FluidCheckout_ThemeCompat_OrchidStore extends FluidCheckout {

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

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {

		// Get accent color from the theme
		if ( function_exists( 'orchid_store_get_option' ) ) {
			$accent_color = orchid_store_get_option( 'secondary_color' );
		}
		else {
			$accent_color = '#E26143';
		}
		

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '46px',
				'--fluidcheckout--field--padding-left' => '16px',
				'--fluidcheckout--field--border-width' => '2px',
				'--fluidcheckout--field--border-color' => '#ececec',
				'--fluidcheckout--field--background-color--accent' => $accent_color,

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing--password' => '32px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function change_fc_content_section_class( $class ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $class; }

		return $class . ' __os-container__';
	}


}

FluidCheckout_ThemeCompat_OrchidStore::instance();
