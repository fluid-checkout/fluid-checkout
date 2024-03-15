<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: The Hanger (by Get Bowtied).
 */
class FluidCheckout_ThemeCompat_TheHanger extends FluidCheckout {

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
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Default theme's accent color
		$accent_color = '#C4B583';

		// Get theme's color value if exists
		if ( method_exists( 'GBT_Opt', 'getOption' ) ) {
			$accent_color = GBT_Opt::getOption( 'accent_color' );
		}

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '48px',
				'--fluidcheckout--field--padding-left' => '12px',
				'--fluidcheckout--field--border-color' => '#777',
				'--fluidcheckout--field--background-color--accent' => $accent_color,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_TheHanger::instance();
