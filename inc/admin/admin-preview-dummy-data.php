<?php
defined( 'ABSPATH' ) || exit;

/**
 * Dummy customer, product, coupon and order data for the admin preview, which exists in memory only.
 */
class FluidCheckout_Admin_Preview_Dummy_Data extends FluidCheckout {

	/**
	 * User ID of the dummy logged-in customer, high enough to never belong to a real user.
	 */
	const CUSTOMER_ID = 2147483647;

	/**
	 * Email address of the dummy customer, on a domain reserved for examples.
	 */
	const CUSTOMER_EMAIL = 'john@example.com';

	/**
	 * First product ID used for dummy products, high enough to never belong to a real product.
	 */
	const PRODUCT_ID_START = 2147483600;

	/**
	 * Code of the dummy coupon applied to the preview cart, in lowercase as WooCommerce formats coupon codes.
	 */
	const COUPON_CODE = 'preview10';

	/**
	 * Order ID of the dummy paid order shown on the order received page, high enough to never belong to a real order.
	 */
	const ORDER_RECEIVED_ID = 2147483500;

	/**
	 * Order ID of the dummy order waiting for payment shown on the order pay page, high enough to never belong to a real order.
	 */
	const ORDER_PAY_ID = 2147483501;

	/**
	 * Order key of the dummy orders, within the 22 characters WooCommerce keeps.
	 */
	const ORDER_KEY = 'wc_order_fcpreview';

	/**
	 * Order number shown for the dummy orders, the same as in the WooCommerce email preview.
	 */
	const ORDER_NUMBER = '12345';

	/**
	 * Dummy customer address, cached after first retrieval.
	 *
	 * @var array|null
	 */
	private $customer_address = null;

	/**
	 * Dummy customer meta values by meta key, cached after first retrieval.
	 *
	 * @var array|null
	 */
	private $customer_meta = null;

	/**
	 * Dummy values of order fields by field key, cached after first retrieval.
	 *
	 * @var array|null
	 */
	private $order_fields = null;

	/**
	 * Dummy product properties by dummy product ID, cached after first retrieval.
	 *
	 * @var array|null
	 */
	private $products = null;



	/**
	 * Initialize the dummy data class.
	 *
	 * Intentionally has no hooks. Preview code calls the getters directly.
	 */
	public function __construct() {
		// Intentionally empty, as the class has no hooks and the parent constructor sets up the plugin
	}



	/**
	 * Get the user data of the dummy logged-in customer, in the same shape as a row of the users table.
	 *
	 * @return  object
	 */
	public function get_customer_user_data() {
		// Get dummy customer name
		$name = $this->get_customer_name();

		return (object) array(
			'ID'                     => self::CUSTOMER_ID,
			'user_login'             => 'fc-preview-customer',
			'user_pass'              => '',
			'user_nicename'          => 'fc-preview-customer',
			'user_email'             => self::CUSTOMER_EMAIL,
			'user_url'               => '',
			'user_registered'        => current_time( 'mysql', true ),
			'user_activation_key'    => '',
			'user_status'            => '0',
			'display_name'           => $name[ 'first_name' ] . ' ' . $name[ 'last_name' ],
		);
	}

	/**
	 * Get the first and last name of the dummy customer.
	 *
	 * @return  array  First and last name by field key.
	 */
	public function get_customer_name() {
		return array(
			'first_name'   => _x( 'John', 'Dummy customer first name in the admin preview', 'fluid-checkout' ),
			'last_name'    => _x( 'Doe', 'Dummy customer last name in the admin preview', 'fluid-checkout' ),
		);
	}

	/**
	 * Get the company name of the dummy customer, for when a company is needed.
	 *
	 * @return  string
	 */
	public function get_customer_company() {
		return _x( 'Example Company', 'Dummy customer company in the admin preview', 'fluid-checkout' );
	}

	/**
	 * Get the address of the dummy customer: the store address, when the store sells and ships to its own country.
	 *
	 * @return  array  Address values by field key, without the `billing_` or `shipping_` prefix.
	 */
	public function get_customer_address() {
		// Return cached address
		if ( null !== $this->customer_address ) { return $this->customer_address; }

		// Get countries the store sells and ships to
		$countries = WC()->countries;
		$allowed_countries = $countries->get_allowed_countries();
		$shipping_countries = $countries->get_shipping_countries();

		// Maybe limit to countries the store ships to, as the list is empty when shipping is disabled
		if ( ! empty( $shipping_countries ) ) {
			$allowed_countries = array_intersect_key( $allowed_countries, $shipping_countries );
		}

		// Get company name, only when the company field is required, as the dummy customer is otherwise a private customer
		$company = 'required' === FluidCheckout_Settings::instance()->get_option( 'woocommerce_checkout_company_field' ) ? $this->get_customer_company() : '';

		// Define dummy address with the store address
		$address = array_merge( $this->get_customer_name(), array(
			'company'      => $company,
			'address_1'    => $countries->get_base_address(),
			'address_2'    => $countries->get_base_address_2(),
			'city'         => $countries->get_base_city(),
			'state'        => $countries->get_base_state(),
			'postcode'     => $countries->get_base_postcode(),
			'country'      => $countries->get_base_country(),
			'email'        => self::CUSTOMER_EMAIL,
			'phone'        => '555-0100',
		) );

		// Maybe use the first allowed country with a generic address, as the store address would not be accepted
		// The postcode stays empty, as its format depends on the country
		if ( ! empty( $allowed_countries ) && ! array_key_exists( $address[ 'country' ], $allowed_countries ) ) {
			// Get fallback country and its states
			$country = key( $allowed_countries );
			$states = $countries->get_states( $country );

			// Use a generic address in that country
			$address = array_merge( $address, array(
				'address_1'    => _x( '123 Main Street', 'Dummy customer street address in the admin preview', 'fluid-checkout' ),
				'address_2'    => '',
				'city'         => _x( 'Anytown', 'Dummy customer city in the admin preview', 'fluid-checkout' ),
				'state'        => is_array( $states ) && ! empty( $states ) ? key( $states ) : '',
				'postcode'     => '',
				'country'      => $country,
			) );
		}

		// Cache the address before filtering, as filter callbacks may read it again
		$this->customer_address = $address;

		/**
		 * Filter the address of the dummy customer in the admin preview.
		 *
		 * @since  5.0.0
		 *
		 * @param  array  $address  Address values by field key, without the `billing_` or `shipping_` prefix.
		 *
		 * @return array  Address values.
		 */
		$this->customer_address = apply_filters( 'fc_admin_preview_dummy_customer_address', $address );

		return $this->customer_address;
	}

	/**
	 * Get the billing and shipping fields of the dummy customer, as saved on a customer account.
	 *
	 * @return  array  Field values by field key, with the `billing_` or `shipping_` prefix.
	 */
	public function get_customer_fields() {
		// Define fields
		$fields = array();

		// Iterate address fields
		foreach ( $this->get_customer_address() as $field_key => $value ) {
			// Add billing field
			$fields[ 'billing_' . $field_key ] = $value;

			// Skip fields that WooCommerce shipping addresses do not have, such as the email or fields added by other plugins
			if ( ! method_exists( 'WC_Customer', 'set_shipping_' . $field_key ) ) { continue; }

			// Add shipping field
			$fields[ 'shipping_' . $field_key ] = $value;
		}

		return $fields;
	}

	/**
	 * Get the meta values of the dummy logged-in customer: its role, name, and billing and shipping addresses.
	 *
	 * @return  array  Meta values by meta key.
	 */
	public function get_customer_meta() {
		global $wpdb;

		// Return cached meta values
		if ( null !== $this->customer_meta ) { return $this->customer_meta; }

		// Define role meta value, read when the dummy customer becomes the current user
		$meta = array( $wpdb->get_blog_prefix() . 'capabilities' => array( 'customer' => true ) );

		// Bail with the role only until WooCommerce sets up its countries at `init`, as the addresses need them
		if ( ! WC()->countries instanceof WC_Countries ) { return $meta; }

		// Cache the role only while getting the addresses, as country filters from other plugins may read this meta again
		$this->customer_meta = $meta;

		// Cache the meta values before filtering, as filter callbacks may read them again
		$this->customer_meta = array_merge( $meta, $this->get_customer_name(), $this->get_customer_fields() );

		/**
		 * Filter the meta values of the dummy logged-in customer in the admin preview.
		 *
		 * @since  5.0.0
		 *
		 * @param  array  $meta  Meta values by meta key.
		 *
		 * @return array  Meta values.
		 */
		$this->customer_meta = (array) apply_filters( 'fc_admin_preview_dummy_customer_meta', $this->customer_meta );

		return $this->customer_meta;
	}



	/**
	 * Get the values of the order fields the dummy customer fills at checkout, such as order notes.
	 *
	 * @return  array  Field values by field key.
	 */
	public function get_order_fields() {
		// Maybe build the order fields, only once per request
		if ( null === $this->order_fields ) {
			// Cache an empty list before filtering, as filter callbacks may read the order fields again
			$this->order_fields = array();

			/**
			 * Filter the values of the order fields the dummy customer fills at checkout in the admin preview.
			 *
			 * @since  5.0.0
			 *
			 * @param  array  $fields  Field values by field key.
			 *
			 * @return array  Field values.
			 */
			$this->order_fields = (array) apply_filters( 'fc_admin_preview_dummy_order_fields', array(
				'order_comments'    => _x( 'Please leave the package at the front door.', 'Dummy order notes in the admin preview', 'fluid-checkout' ),
			) );
		}

		return $this->order_fields;
	}



	/**
	 * Get the dummy products used to top up the preview cart.
	 *
	 * @return  array  Product properties by dummy product ID.
	 */
	public function get_products() {
		// Maybe build the dummy products list, only once per request
		if ( null === $this->products ) {
			// Cache an empty list before filtering, as filter callbacks may load products again
			$this->products = array();

			/**
			 * Filter the dummy products used to top up the preview cart when the catalog has fewer products.
			 * Each item holds product properties as accepted by `WC_Product::set_props()`.
			 *
			 * @since  5.0.0
			 *
			 * @param  array  $products  Product property lists.
			 *
			 * @return array  Product property lists.
			 */
			$products = apply_filters( 'fc_admin_preview_dummy_products', array(
				array( 'name' => _x( 'Beanie', 'Dummy product name in the admin preview', 'fluid-checkout' ), 'regular_price' => '20', 'sale_price' => '18', 'sku' => 'fc-preview-beanie' ),
				array( 'name' => _x( 'Cap', 'Dummy product name in the admin preview', 'fluid-checkout' ), 'regular_price' => '18', 'sale_price' => '16', 'sku' => 'fc-preview-cap' ),
				array( 'name' => _x( 'T-Shirt', 'Dummy product name in the admin preview', 'fluid-checkout' ), 'regular_price' => '18', 'sku' => 'fc-preview-t-shirt' ),
			) );

			// Iterate dummy products, assigning IDs that no real product uses
			foreach ( array_values( (array) $products ) as $index => $props ) {
				$this->products[ self::PRODUCT_ID_START + $index ] = $props;
			}
		}

		return $this->products;
	}

	/**
	 * Check whether a product ID belongs to a dummy product.
	 *
	 * @param   int  $product_id  Product ID.
	 *
	 * @return  bool
	 */
	public function is_dummy_product_id( $product_id ) {
		return array_key_exists( (int) $product_id, $this->get_products() );
	}

	/**
	 * Get the properties of a dummy product, with defaults for a published product hidden from the catalog.
	 *
	 * @param   int  $product_id  Dummy product ID.
	 *
	 * @return  array  Product properties as accepted by `WC_Product::set_props()`.
	 */
	public function get_product_props( $product_id ) {
		// Bail if not a dummy product
		if ( ! $this->is_dummy_product_id( $product_id ) ) { return array(); }

		// Get product properties
		$products = $this->get_products();
		$props = (array) $products[ (int) $product_id ];

		// Get active price, the sale price when set
		$regular_price = array_key_exists( 'regular_price', $props ) ? $props[ 'regular_price' ] : '';
		$price = array_key_exists( 'sale_price', $props ) && '' !== $props[ 'sale_price' ] ? $props[ 'sale_price' ] : $regular_price;

		return array_merge( array(
			'status'                 => 'publish',
			'catalog_visibility'     => 'hidden',
			'price'                  => $price,
		), $props );
	}



	/**
	 * Get the properties of the dummy coupon applied to the preview cart.
	 *
	 * @return  array  Coupon properties as accepted by the `woocommerce_get_shop_coupon_data` filter.
	 */
	public function get_coupon_props() {
		return array(
			'discount_type'       => 'percent',
			'amount'              => 10,
		);
	}



	/**
	 * Check whether an order ID belongs to a dummy order.
	 *
	 * @param   int  $order_id  Order ID.
	 *
	 * @return  bool
	 */
	public function is_dummy_order_id( $order_id ) {
		return in_array( (int) $order_id, array( self::ORDER_RECEIVED_ID, self::ORDER_PAY_ID ), true );
	}

	/**
	 * Get the properties of a dummy order placed by the dummy customer, paid for the order received page or waiting for payment for the order pay page.
	 *
	 * @param   int  $order_id  Dummy order ID.
	 *
	 * @return  array  Order properties as accepted by `WC_Order::set_props()`.
	 */
	public function get_order_props( $order_id ) {
		// Bail if not a dummy order
		if ( ! $this->is_dummy_order_id( $order_id ) ) { return array(); }

		// Get enabled payment methods, as checking which are available on the order pay page would load the dummy order being built
		$payment_gateways = wp_list_filter( WC()->payment_gateways()->payment_gateways(), array( 'enabled' => 'yes' ) );

		// Get order fields, with the order notes saved as the customer note
		$order_fields = $this->get_order_fields();

		return array_merge( $this->get_customer_fields(), array(
			'order_key'              => self::ORDER_KEY,
			'status'                 => self::ORDER_PAY_ID === (int) $order_id ? 'pending' : 'processing',
			'customer_id'            => FluidCheckout_Admin_Preview::MODE_LOGGED_IN === FluidCheckout_Admin_Preview::instance()->get_preview_mode() ? self::CUSTOMER_ID : 0,
			'created_via'            => 'checkout',
			'currency'               => get_woocommerce_currency(),
			'prices_include_tax'     => wc_prices_include_tax(),
			'payment_method'         => empty( $payment_gateways ) ? '' : reset( $payment_gateways ),
			'customer_note'          => array_key_exists( 'order_comments', $order_fields ) ? $order_fields[ 'order_comments' ] : '',
			'date_created'           => time(),
			'date_paid'              => self::ORDER_PAY_ID === (int) $order_id ? null : time(),
		) );
	}

	/**
	 * Get the meta data of the dummy orders, with the fields that have no order property, as checkout saves them.
	 *
	 * @return  array  Meta values by meta key.
	 */
	public function get_order_meta() {
		// Define meta data
		$order_meta = array();

		// Iterate customer fields, saving fields without an order property with an underscore prefix, like WooCommerce does at checkout
		foreach ( $this->get_customer_fields() as $field_key => $value ) {
			// Skip fields saved as order properties
			if ( method_exists( 'WC_Order', 'set_' . $field_key ) ) { continue; }

			// Add field value with the underscore prefix
			$order_meta[ '_' . $field_key ] = $value;
		}

		// Iterate order fields, saving them with their field key, like plugins usually save order fields
		foreach ( $this->get_order_fields() as $field_key => $value ) {
			// Skip the order notes, saved as the customer note
			if ( 'order_comments' === $field_key ) { continue; }

			// Add field value
			$order_meta[ $field_key ] = $value;
		}

		return $order_meta;
	}

	/**
	 * Get the order updates the store sent to the dummy customer, which customers see with their orders.
	 *
	 * @return  WP_Comment[]  Customer order notes.
	 */
	public function get_customer_order_notes() {
		return array(
			new WP_Comment( (object) array(
				'comment_ID'             => 0,
				'comment_content'        => _x( 'We received your order and will send you an update when it ships.', 'Dummy order update in the admin preview', 'fluid-checkout' ),
				'comment_date'           => current_time( 'mysql' ),
				'comment_type'           => 'order_note',
				'comment_approved'       => '1',
			) ),
		);
	}

	/**
	 * Get the downloads of a dummy order: a user guide for its first product.
	 *
	 * @param   WC_Order  $order  Dummy order.
	 *
	 * @return  array  Downloads, as returned by `WC_Order::get_downloadable_items()`.
	 */
	public function get_order_downloads( $order ) {
		// Get first order item
		$items = $order->get_items();
		$item = reset( $items );

		// Bail if the order has no products
		if ( ! $item ) { return array(); }

		// Get product and download name
		$product = $item->get_product();
		$download_name = _x( 'User guide', 'Dummy download in the admin preview', 'fluid-checkout' );

		return array(
			array(
				'download_url'           => '#',
				'download_id'            => 'fc-preview-user-guide',
				'product_id'             => $item->get_product_id(),
				'product_name'           => $item->get_name(),
				'product_url'            => $product && $product->is_visible() ? $product->get_permalink() : '',
				'download_name'          => $download_name,
				'order_id'               => $order->get_id(),
				'order_key'              => $order->get_order_key(),
				'downloads_remaining'    => '',
				'access_expires'         => null,
				'file'                   => array( 'name' => $download_name, 'file' => '' ),
			),
		);
	}

	/**
	 * Get the order received page URL of the dummy paid order.
	 *
	 * @return  string
	 */
	public function get_order_received_url() {
		return add_query_arg( 'key', self::ORDER_KEY, wc_get_endpoint_url( 'order-received', self::ORDER_RECEIVED_ID, wc_get_checkout_url() ) );
	}

	/**
	 * Get the order pay page URL of the dummy order waiting for payment.
	 *
	 * @return  string
	 */
	public function get_order_pay_url() {
		return add_query_arg( array( 'pay_for_order' => 'true', 'key' => self::ORDER_KEY ), wc_get_endpoint_url( 'order-pay', self::ORDER_PAY_ID, wc_get_checkout_url() ) );
	}

}

FluidCheckout_Admin_Preview_Dummy_Data::instance();
