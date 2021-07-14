<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce PDF Invoices & Packing Slips (by Ewout Fernhout).
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
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Packing Slips customizations
		if ( class_exists( 'FluidCheckout_PackingSlips' ) && class_exists( 'FluidCheckout_GiftOptions' ) && ! FluidCheckout_GiftOptions::instance()->is_gift_message_in_order_details() ) {
			add_action( 'wpo_wcpdf_before_order_details', array( $this, 'output_message_box_for_packing_slips' ), 10, 2 );
			add_action( 'wpo_wcpdf_template_styles', array( $this, 'add_message_box_styles_for_packing_slips' ), 10, 2 );
		}
	}



	/**
	 * Output message box to the packing slip document.
	 *
	 * @param   string    $template_type  Type of document being generated. Possible values: `packing-slip`, `invoice`.
	 * @param   WC_Order  $order          Order object.
	 */
	public function output_message_box_for_packing_slips( $template_type, $order ) {
		// Bail if not packing slip
		if ( 'packing-slip' !== $template_type ) { return; }

		echo FluidCheckout_PackingSlips::instance()->get_message_box_html( $order->get_id() );
	}


	/**
	 * Add message box styles for packing slip styles.
	 *
	 * @param   string          $css       CSS code for the printable document styles.
	 * @param   Order_Document  $document  Invoice or Packing Slip document object.
	 */
	public function add_message_box_styles_for_packing_slips( $css, $order ) {
		$css = $css . PHP_EOL . FluidCheckout_PackingSlips::instance()->get_message_box_styles();
		return $css;
	}

}

FluidCheckout_WooCommercePDFInvoicesPackingSlips::instance();
