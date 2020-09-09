<?php

/**
 * Order received page feature
 */
class FluidCheckout_OrderReceived extends FluidCheckout {



	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->init();
	}
	


	/**
	 * Initialize class.
	 */
	public function init() {
		// Bail if order received page not enabled
		if ( get_option( 'wfc_enable_order_received', 'true' ) !== 'true' ) { return; }

		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		add_filter( 'body_class', array( $this, 'add_body_class' ) );

		// Widget Areas
		add_action( 'widgets_init', array( $this, 'register_order_received_widgets_areas' ), 50 );
		add_action( 'woocommerce_before_thankyou', array( $this, 'output_sidebar_order_received_before' ), 50 );
		add_action( 'woocommerce_thankyou', array( $this, 'output_sidebar_order_review_outside' ), 50 );

		// Order Details
		remove_action( 'wfc_order_details_after_order_table_section', array( $this->multistep_enhanced(), 'output_order_customer_details' ), 10 );
		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'move_shipping_address_order_received_details_before_payment' ), 10, 3 );
		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'add_shipping_address_order_received_details' ), 20, 3 );
		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'add_billing_address_order_received_details' ), 20, 3 );
	}



	/**
	 * Return WooCommerce Fluid Checkout multi-step enhanced class instance
	 */
	public function multistep_enhanced() {
		return FluidCheckoutLayout_MultiStepEnhanced::instance();
	}



	/**
	 * Add page body class for feature detection
	 */
	public function add_body_class( $classes ) {
		// Bail if not on order received page.
		if( ! function_exists( 'is_order_received_page' ) || ! is_order_received_page() ){ return $classes; }

		$classes[] = 'has-wfc-order-received';

		return $classes;
	}



	/**
	 * Register widget areas for the checkout pages
	 */
	function register_order_received_widgets_areas() {
		register_sidebar( array(
			'name'			=> __( 'Order Confirmation - Before', 'lobsteranywhere-customizations' ),
			'id'			=> 'wfc_order_received_before',
			'description'	=> __( 'Display widgets on order confirmation page before the order details.', 'lobsteranywhere-customizations' ),
			'before_widget'	=> '<aside id="%1$s" class="widget %2$s">',
			'after_widget'	=> '</aside>',
			'before_title'	=> '<h5 class="widget-title">',
			'after_title'	=> '</h5>',
		) );

		register_sidebar( array(
			'name'			=> __( 'Order Confirmation - After', 'lobsteranywhere-customizations' ),
			'id'			=> 'wfc_order_received_after',
			'description'	=> __( 'Display widgets after the order confirmation page after the order details.', 'lobsteranywhere-customizations' ),
			'before_widget'	=> '<aside id="%1$s" class="widget %2$s">',
			'after_widget'	=> '</aside>',
			'before_title'	=> '<h5 class="widget-title">',
			'after_title'	=> '</h5>',
		) );
	}

	/**
	 * Output widget area inside order review section
	 */
	function output_sidebar_order_received_before() {
		if ( is_active_sidebar( 'wfc_order_received_before' ) ) :
			dynamic_sidebar( 'wfc_order_received_before' );
		endif;
	}

	/**
	 * Output widget area outside order review section
	 */
	function output_sidebar_order_review_outside() {
		if ( is_active_sidebar( 'wfc_order_received_after' ) ) :
			dynamic_sidebar( 'wfc_order_received_after' );
		endif;
	}





	/**
	 * Move order item details position for shipping row to before payment method
	 */
	public function move_shipping_address_order_received_details_before_payment( $total_rows, $order, $tax_display ) {
		// Bail if shipping or payment method not present
		if ( ! array_key_exists( 'shipping', $total_rows ) || ! array_key_exists( 'payment_method', $total_rows ) ) { return; }

		// Get shipping row values
		$shipping_row = $total_rows[ 'shipping' ];

		// Get token position
		$position_index = array_search( 'payment_method', array_keys( $total_rows ) ) - 1;

		// Insert at token position
		$new_total_rows  = array_slice( $total_rows, 0, $position_index );
		$new_total_rows[ 'shipping' ] = $shipping_row;
		$new_total_rows = array_merge( $new_total_rows, array_slice( $total_rows, $position_index, count( $total_rows ) ) );
	
		return $new_total_rows;
	}
	
	/**
	 * Add shipping address to order details totals.
	 */
	public function add_shipping_address_order_received_details( $total_rows, $order, $tax_display ) {
		// Get token position
		$position_index = array_search( 'shipping', array_keys( $total_rows ) ) + 1;
	
		// Insert at token position
		$new_total_rows  = array_slice( $total_rows, 0, $position_index );
		$new_total_rows[ 'shipping_address' ] = array(
			'label' => __( 'Shipping address:', 'woocommerce-fluid-checkout' ),
			'value' => $order->get_formatted_shipping_address(),
		);
		$new_total_rows = array_merge( $new_total_rows, array_slice( $total_rows, $position_index, count( $total_rows ) ) );
	
		return $new_total_rows;
	}

	/**
	 * Add billing address to order details totals.
	 */
	public function add_billing_address_order_received_details( $total_rows, $order, $tax_display ) {
		// Get token position
		$position_index = array_search( 'payment_method', array_keys( $total_rows ) ) + 1;

		// Get billing address value
		$billing_address_value = $order->get_formatted_billing_address();

		// Check for billing address same as shipping address
		$same_as_shipping = true;
		$address_fields =  apply_filters( 'wfc_address_fields_for_same_as_shipping_address', array( 'first_name', 'last_name', 'address_1', 'address_2', 'city', 'state', 'country', 'postcode' ) );
		if ( class_exists( 'FluidCheckout_AddressBook' ) && get_option( 'wfc_enable_address_book', 'true' ) === 'true' ) {
			$shipping_address = $order->get_address( 'shipping' );
			$billing_address = $order->get_address( 'billing' );
			
			foreach ( $billing_address as $field_key => $value ) {
				if ( in_array( $field_key, $address_fields ) && array_key_exists( $field_key, $shipping_address ) && $billing_address[ $field_key ] != $shipping_address[ $field_key ] ) {
					$same_as_shipping = false;
					break;
				}
			}
		
			if ( $same_as_shipping ) {
				$billing_address_value = __( 'Same as shipping address', 'woocommerce-fluid-checkout' );
			}
		}
	
		// Insert at token position
		$new_total_rows  = array_slice( $total_rows, 0, $position_index );
		$new_total_rows[ 'billing_address' ] = array(
			'label' => __( 'Billing address:', 'woocommerce-fluid-checkout' ),
			'value' => $billing_address_value,
		);
		$new_total_rows = array_merge( $new_total_rows, array_slice( $total_rows, $position_index, count( $total_rows ) ) );
	
		return $new_total_rows;
	}

}

FluidCheckout_OrderReceived::instance();
