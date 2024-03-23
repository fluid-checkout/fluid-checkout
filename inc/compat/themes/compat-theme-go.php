<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Go (by GoDaddy).
 */
class FluidCheckout_ThemeCompat_Go extends FluidCheckout {

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
		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '52.18px',
				'--fluidcheckout--field--padding-left' => '13.6px',
				'--fluidcheckout--field--border-radius' => '4px',
				'--fluidcheckout--field--border-color' => 'var(--go-input--border-color, var(--go-heading--color--text))',
				'--fluidcheckout--field--border-width' => '2px',
				'--fluidcheckout--field--background-color--accent' => 'var(--go--color--secondary)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Go::instance();
