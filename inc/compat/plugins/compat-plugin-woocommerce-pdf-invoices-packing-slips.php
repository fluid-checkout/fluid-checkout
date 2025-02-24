<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce PDF Invoices & Packing Slips (by WPOvernight).
 */
class FluidCheckout_WooCommercePDFInvoicesPackingSlips extends FluidCheckout {

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
		add_filter( 'wpo_wcpdf_before_billing_address', array( $this, 'before_wpo_wcpdf_billing_address_hooks' ), 10 );
		add_filter( 'wpo_wcpdf_after_billing_address', array( $this, 'after_wpo_wcpdf_billing_address_hooks' ), 10 );
	}

	/**
	 * Add or remove hooks before formatted billing address used by the plugin.
	 */
	public function before_wpo_wcpdf_billing_address_hooks() {
		// Formatted address
		add_filter( 'fc_add_phone_localisation_formats', array( $this, 'skip_add_phone_localisation_formats' ), 10 );
	}

	/**
	 * Add or remove hooks after formatted billing address used by the plugin.
	 */
	public function after_wpo_wcpdf_billing_address_hooks() {
		// Formatted address
		remove_filter( 'fc_add_phone_localisation_formats', array( $this, 'skip_add_phone_localisation_formats' ), 10 );
	}



	/**
	 * Maybe skip adding phone localisation formats.
	 */
	public function skip_add_phone_localisation_formats() {
		return 'no';
	}

}

FluidCheckout_WooCommercePDFInvoicesPackingSlips::instance();
