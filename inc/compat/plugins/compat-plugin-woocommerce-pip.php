<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce Print Invoices/Packing Lists (by SkyVerge)
 */
class FluidCheckout_WooCommercePIP extends FluidCheckout {

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
			add_action( 'wc_pip_before_body', array( $this, 'output_message_box_for_packing_slips' ), 10, 4 );
			add_action( 'wc_pip_styles', array( $this, 'add_message_box_styles_for_packing_slips' ), 10 );
		}
	}



	/**
	 * Output gift message to the packing list document.
	 *
	 * @param   string            $document_type   Type of document being generated. Possible values: `packing-list`, `invoice`.
	 * @param   string            $action          Action being applied to the document.
	 * @param   WC_PIP_Document   $document        Document object.
	 * @param   WC_Order          $order           Order object.
	 */
	public function output_message_box_for_packing_slips( $document_type, $action, $document, $order ) {
		// Bail if not packing slip
		if ( 'packing-list' !== $document->type ) { return; }

		echo FluidCheckout_PackingSlips::instance()->get_message_box_html( $order->get_id() );
	}


	/**
	 * Output gift message for packing list.
	 */
	public function add_message_box_styles_for_packing_slips() {
		echo FluidCheckout_PackingSlips::instance()->get_message_box_styles();

		// Add plugin specific styles
		echo '
			.packing-list__message-wrapper * {
				box-sizing: border-box;
			}
			.packing-list__message-body.packing-list__message-body {
				height: 145px;
				line-height: 26px;
			}
		';
	}

}

FluidCheckout_WooCommercePIP::instance();
