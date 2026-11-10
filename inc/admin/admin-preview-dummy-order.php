<?php
defined( 'ABSPATH' ) || exit;

/**
 * Order for preview requests that exists in memory only, shown on the order received and order pay pages.
 */
class FluidCheckout_Admin_Preview_Dummy_Order extends WC_Order {

	/**
	 * Dummy orders currently being built, by dummy order ID, so a nested read of the same order does not build it again.
	 *
	 * @var array
	 */
	private static $orders_being_initialized = array();



	/**
	 * Set the dummy order properties, items and totals instead of reading the order from the database.
	 *
	 * @param   int  $order  Dummy order ID.
	 */
	public function __construct( $order = 0 ) {
		// Initialize order data, without reading an order from the database
		parent::__construct( 0 );

		self::initialize_order_with_dummy_data( $this, $order );
	}

	/**
	 * Set dummy order properties, items and totals on an order object instead of reading from the database.
	 *
	 * @param   WC_Order  $order     Order object.
	 * @param   int       $order_id  Dummy order ID.
	 */
	public static function initialize_order_with_dummy_data( WC_Order $order, $order_id ) {
		$order_id = (int) $order_id;

		// Bail if this dummy order is already being built, such as when a `woocommerce_currency` callback constructs it directly.
		// Copy a currency already stored on it. Leave an empty currency alone, as `WC_Abstract_Order::set_currency()` would call `get_woocommerce_currency()` again.
		if ( array_key_exists( $order_id, self::$orders_being_initialized ) ) {
			// Read the stored currency without the view filter, which can load this order again
			$currency = self::$orders_being_initialized[ $order_id ]->get_currency( 'edit' );

			// Maybe copy currency already stored on the in-progress order
			if ( is_string( $currency ) && '' !== $currency ) {
				$order->set_currency( $currency );
			}

			return;
		}

		// Mark this dummy order as being built before any code that can load it again
		self::$orders_being_initialized[ $order_id ] = $order;

		// Build the dummy order, clearing the re-entrancy guard even when a nested load throws
		try {
			// Mark the order as not read yet, so the dummy order data is set as its data instead of as changes
			$order->set_object_read( false );

			// Iterate order item types, keeping their groups empty so items are not read from the database for the dummy order ID
			if ( $order instanceof self ) {
				foreach ( array( 'line_item', 'tax', 'shipping', 'fee', 'coupon' ) as $item_type ) {
					$order->items[ $order->type_to_group( $item_type ) ] = array();
				}

				// Keep meta data empty, so it is not read from the database for the dummy order ID
				$order->meta_data = array();
			}

			// Set dummy order properties
			$order->set_id( $order_id );
			$order->set_props( FluidCheckout_Admin_Preview_Dummy_Data::instance()->get_order_props( $order->get_id() ) );

			// Iterate dummy order meta data, such as fields added by plugins
			foreach ( FluidCheckout_Admin_Preview_Dummy_Data::instance()->get_order_meta() as $meta_key => $meta_value ) {
				$order->add_meta_data( $meta_key, $meta_value, true );
			}

			// Set the items and totals of the preview cart, like checkout does when it creates an order
			WC()->checkout()->set_data_from_cart( $order );

			// Assign temporary item and meta IDs so variation line items work with legacy array access on PHP 8.5+
			self::assign_temporary_preview_order_item_ids( $order );

			// Mark the order as read, so later changes are tracked as changes
			$order->set_object_read( true );
		}
		finally {
			// Clear the guard when building finishes or throws, so a later read of this dummy order can build it
			unset( self::$orders_being_initialized[ $order_id ] );
		}
	}

	/**
	 * Assign temporary IDs to in-memory order items and their meta data.
	 *
	 * Variation products build permalinks from `$item['item_meta_array']`, which uses meta IDs as array keys.
	 * Unsaved preview line items have no IDs yet, which triggers PHP 8.5 deprecations for null array offsets.
	 *
	 * @param   WC_Order  $order  Dummy order.
	 */
	public static function assign_temporary_preview_order_item_ids( WC_Order $order ) {
		$item_id = 1;
		$meta_id = 1;

		// Iterate order items
		foreach ( $order->get_items() as $item ) {
			// Skip invalid items
			if ( ! $item instanceof WC_Order_Item ) { continue; }

			// Maybe assign a temporary item ID
			if ( ! $item->get_id() ) {
				$item->set_id( $item_id );
			}

			$item_id++;

			// Iterate item meta data
			foreach ( $item->get_meta_data() as $meta ) {
				// Maybe assign a temporary meta ID
				if ( empty( $meta->id ) ) {
					$meta->id = $meta_id;
				}

				$meta_id++;
			}
		}
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
