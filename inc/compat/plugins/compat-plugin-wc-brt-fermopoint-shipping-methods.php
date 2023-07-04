<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: BRT Fermopoint (by BRT)
 */
class FluidCheckout_WC_BRT_FermopointShippingMethods extends FluidCheckout {

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
		// Bail if Fermopoint classes are not available
		if ( ! class_exists( 'WC_BRT_FermoPoint_Shipping_Methods' ) || ! WC_BRT_FermoPoint_Shipping_Methods::instance() || ! WC_BRT_FermoPoint_Shipping_Methods::instance()->core ) { return; }

		// Move fermopoint details section
		remove_action( 'woocommerce_review_order_after_shipping', array( WC_BRT_FermoPoint_Shipping_Methods::instance()->core, 'add_maps_or_list' ), 10 );
		add_action( 'fc_shipping_methods_after_packages_inside', array( $this, 'add_maps_or_list' ), 10 );
	}



	/**
	 * Add maps or list output from the plugin, replacing `tr` elements with `div`.
	 */
	public function add_maps_or_list() {
		// Get the maps or list output from the plugin
		ob_start();
		WC_BRT_FermoPoint_Shipping_Methods::instance()->core->add_maps_or_list();
		$html = ob_get_clean();

		// Replace `tr` elements with `div`
		$replace = array(
			'<tr' => '<div',
			'</tr' => '</div',
			'<td' => '<div',
			'</td' => '</div',
		);
		$html = str_replace( array_keys( $replace ), array_values( $replace ), $html );

		// Output
		echo $html;
	}

}

FluidCheckout_WC_BRT_FermopointShippingMethods::instance();
