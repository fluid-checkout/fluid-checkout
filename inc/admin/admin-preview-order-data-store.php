<?php
defined( 'ABSPATH' ) || exit;

/**
 * Order data store for preview requests, which hydrates dummy orders in memory instead of reading the database.
 */
class FluidCheckout_Admin_Preview_Order_Data_Store implements WC_Object_Data_Store_Interface {

	/**
	 * WooCommerce order data store used for real orders.
	 *
	 * @var WC_Object_Data_Store_Interface
	 */
	private $delegate;



	/**
	 * Wrap the WooCommerce order data store.
	 *
	 * @param   object|string  $store  Data store instance or class name.
	 */
	public function __construct( $store ) {
		// Get the delegate data store instance
		if ( is_object( $store ) ) {
			$this->delegate = $store;
		} else {
			$this->delegate = new $store();
		}
	}



	/**
	 * Read an order, hydrating dummy orders in memory.
	 *
	 * @param   WC_Order  $order  Order object.
	 */
	public function read( &$order ) {
		// Bail if not a dummy order, with the order from the database
		if ( ! FluidCheckout_Admin_Preview_Dummy_Data::instance()->is_dummy_order_id( $order->get_id() ) ) {
			$this->delegate->read( $order );
			return;
		}

		FluidCheckout_Admin_Preview_Dummy_Order::initialize_order_with_dummy_data( $order, $order->get_id() );
	}

	/**
	 * Read multiple orders, hydrating dummy orders in memory.
	 *
	 * @param   array  $orders  Order objects keyed by order ID.
	 */
	public function read_multiple( &$orders ) {
		// Bail if bulk read is not supported, with one read per order
		if ( ! is_callable( array( $this->delegate, 'read_multiple' ) ) ) {
			foreach ( $orders as &$order ) {
				$this->read( $order );
			}
			return;
		}

		$orders_to_read = array();

		// Iterate orders, hydrating dummy orders in memory
		foreach ( $orders as $order_id => $order ) {
			// Bail if not a dummy order, keeping the order for database read
			if ( ! FluidCheckout_Admin_Preview_Dummy_Data::instance()->is_dummy_order_id( $order_id ) ) {
				$orders_to_read[ $order_id ] = $order;
				continue;
			}

			$this->read( $order );
		}

		// Bail if no orders remain to read from the database
		if ( empty( $orders_to_read ) ) { return; }

		$this->delegate->read_multiple( $orders_to_read );
	}

	/**
	 * Create an order in the database.
	 *
	 * @param   WC_Order  $order  Order object.
	 */
	public function create( &$order ) {
		$this->delegate->create( $order );
	}

	/**
	 * Update an order in the database.
	 *
	 * @param   WC_Order  $order  Order object.
	 */
	public function update( &$order ) {
		$this->delegate->update( $order );
	}

	/**
	 * Delete an order from the database.
	 *
	 * @param   WC_Order  $order  Order object.
	 * @param   array     $args   Delete arguments.
	 *
	 * @return  bool
	 */
	public function delete( &$order, $args = array() ) {
		return $this->delegate->delete( $order, $args );
	}

	/**
	 * Read order meta from the database.
	 *
	 * @param   WC_Order  $order  Order object.
	 *
	 * @return  array
	 */
	public function read_meta( &$order ) {
		// Bail if not a dummy order, with meta from the database
		if ( ! FluidCheckout_Admin_Preview_Dummy_Data::instance()->is_dummy_order_id( $order->get_id() ) ) {
			return $this->delegate->read_meta( $order );
		}

		return array();
	}

	/**
	 * Delete order meta from the database.
	 *
	 * @param   WC_Order  $order  Order object.
	 * @param   object    $meta   Meta object.
	 *
	 * @return  array
	 */
	public function delete_meta( &$order, $meta ) {
		// Bail if not a dummy order, deleting meta in the database
		if ( ! FluidCheckout_Admin_Preview_Dummy_Data::instance()->is_dummy_order_id( $order->get_id() ) ) {
			return $this->delegate->delete_meta( $order, $meta );
		}

		return array();
	}

	/**
	 * Add order meta to the database.
	 *
	 * @param   WC_Order  $order  Order object.
	 * @param   object    $meta   Meta object.
	 *
	 * @return  int
	 */
	public function add_meta( &$order, $meta ) {
		// Bail if not a dummy order, adding meta in the database
		if ( ! FluidCheckout_Admin_Preview_Dummy_Data::instance()->is_dummy_order_id( $order->get_id() ) ) {
			return $this->delegate->add_meta( $order, $meta );
		}

		return 0;
	}

	/**
	 * Update order meta in the database.
	 *
	 * @param   WC_Order  $order  Order object.
	 * @param   object    $meta   Meta object.
	 */
	public function update_meta( &$order, $meta ) {
		// Bail if not a dummy order, updating meta in the database
		if ( ! FluidCheckout_Admin_Preview_Dummy_Data::instance()->is_dummy_order_id( $order->get_id() ) ) {
			$this->delegate->update_meta( $order, $meta );
		}
	}

	/**
	 * Forward datastore helper methods to the delegate (for example HPOS-only methods).
	 *
	 * @param   string  $method      Method name.
	 * @param   array   $parameters  Method parameters.
	 *
	 * @return  mixed
	 */
	public function __call( $method, $parameters ) {
		if ( ! is_callable( array( $this->delegate, $method ) ) ) {
			return null;
		}

		return $this->delegate->$method( ...$parameters );
	}

}
