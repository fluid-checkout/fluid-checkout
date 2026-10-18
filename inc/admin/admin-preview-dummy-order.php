<?php
defined( 'ABSPATH' ) || exit;

/**
 * Order for preview requests that exists in memory only, shown on the order received and order pay pages.
 */
class FluidCheckout_Admin_Preview_Dummy_Order extends WC_Order {

	/**
	 * Set the dummy order properties, items and totals instead of reading the order from the database.
	 *
	 * @param   int  $order  Dummy order ID.
	 */
	public function __construct( $order = 0 ) {
		// Initialize order data, without reading an order from the database
		parent::__construct( 0 );

		// Mark the order as not read yet, so the dummy order data is set as its data instead of as changes
		$this->set_object_read( false );

		// Iterate order item types, keeping their groups empty so items are not read from the database for the dummy order ID
		foreach ( array( 'line_item', 'tax', 'shipping', 'fee', 'coupon' ) as $item_type ) {
			$this->items[ $this->type_to_group( $item_type ) ] = array();
		}

		// Keep meta data empty, so it is not read from the database for the dummy order ID
		$this->meta_data = array();

		// Set dummy order properties
		$this->set_id( $order );
		$this->set_props( FluidCheckout_Admin_Preview_Dummy_Data::instance()->get_order_props( $this->get_id() ) );

		// Iterate dummy order meta data, such as fields added by plugins
		foreach ( FluidCheckout_Admin_Preview_Dummy_Data::instance()->get_order_meta() as $meta_key => $meta_value ) {
			$this->add_meta_data( $meta_key, $meta_value, true );
		}

		// Set the items and totals of the preview cart, like checkout does when it creates an order
		WC()->checkout()->set_data_from_cart( $this );

		// Mark the order as read, so later changes are tracked as changes
		$this->set_object_read( true );
	}



	/**
	 * Get the order number shown for the dummy order.
	 *
	 * @return  string
	 */
	public function get_order_number() {
		return FluidCheckout_Admin_Preview_Dummy_Data::ORDER_NUMBER;
	}

	/**
	 * Get the dummy order updates sent to the customer, as the dummy order has no order notes in the database.
	 *
	 * @return  WP_Comment[]
	 */
	public function get_customer_order_notes() {
		return FluidCheckout_Admin_Preview_Dummy_Data::instance()->get_customer_order_notes();
	}

	/**
	 * Check whether the dummy order has downloadable items, always true to show the dummy downloads.
	 *
	 * @return  bool
	 */
	public function has_downloadable_item() {
		return true;
	}

	/**
	 * Get the dummy downloads, as the dummy order has no download permissions in the database.
	 *
	 * @return  array
	 */
	public function get_downloadable_items() {
		return FluidCheckout_Admin_Preview_Dummy_Data::instance()->get_order_downloads( $this );
	}



	/**
	 * Skip saving, as dummy orders exist in memory only.
	 *
	 * @return  int  The dummy order ID.
	 */
	public function save() {
		return $this->get_id();
	}

	/**
	 * Skip saving meta data, as dummy orders exist in memory only and plugins save order meta without saving the order on the order received page, such as to remember tracked purchases.
	 */
	public function save_meta_data() {
		// Intentionally empty, dummy orders exist in memory only
	}

	/**
	 * Skip deleting, as dummy orders exist in memory only.
	 *
	 * @param   bool  $force_delete  Whether to delete permanently.
	 *
	 * @return  bool
	 */
	public function delete( $force_delete = false ) {
		return false;
	}

	/**
	 * Skip adding order notes, as WooCommerce saves them as comments directly.
	 *
	 * @param   string  $note              Note text.
	 * @param   int     $is_customer_note  Whether the note is for the customer.
	 * @param   bool    $added_by_user     Whether the note was added by a user.
	 * @param   array   $meta_data         Note meta data.
	 *
	 * @return  int  Always `0`, as no note is added.
	 */
	public function add_order_note( $note, $is_customer_note = 0, $added_by_user = false, $meta_data = array() ) {
		return 0;
	}

}
