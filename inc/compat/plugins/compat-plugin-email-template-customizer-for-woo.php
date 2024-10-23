<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Email Template Customizer for WooCommerce (by VillaTheme).
 */
class FluidCheckout_EmailTemplateCustomizerForWoo extends FluidCheckout {

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
		// Formatted address
		add_filter( 'fc_add_phone_localisation_formats', array( $this, 'maybe_skip_add_phone_localisation_formats' ), 10, 1 );
	}



	/**
	 * Maybe skip adding phone localisation formats when generating emails with this plugin.
	 */
	public function maybe_skip_add_phone_localisation_formats( $should_add ) {
		// Bail if not processing emails with this plugin
		if ( ! doing_action( 'viwec_render_content' ) ) { return $should_add; }

		// Otherwise, skip adding phone localisation formats
		return 'no';
	}

}

FluidCheckout_EmailTemplateCustomizerForWoo::instance();
