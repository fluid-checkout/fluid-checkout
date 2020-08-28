<?php

/**
 * Order received page feature
 */
class FluidCheckout_OrderReceived extends FluidCheckout {

	private $address_book_entries_per_user = array();


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

}

FluidCheckout_OrderReceived::instance();
