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

}

FluidCheckout_OrderReceived::instance();
