<?php
/**
 * Widget Areas on the checkout page.
 */
class FluidCheckout_CheckoutWidgetAreas extends FluidCheckout {

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
		// Widget Areas
		add_action( 'widgets_init', array( $this, 'register_checkout_widgets_areas' ), 50 );
		add_action( 'wfc_checkout_header_widgets', array( $this, 'output_widget_area_checkout_header' ), 50 );
		add_action( 'wfc_checkout_before_steps', array( $this, 'output_widget_area_checkout_below_progress_bar' ), 50 );
		add_action( 'woocommerce_checkout_after_order_review', array( $this, 'output_widget_area_order_review_inside' ), 50 );
		add_action( 'wfc_checkout_after_order_review', array( $this, 'output_widget_area_order_review_outside' ), 50 );
	}





	/**
	 * Register widget areas for the checkout page.
	 */
	public function register_checkout_widgets_areas() {

		register_sidebar( array(
			'name'          => __( 'Checkout Header', 'woocommerce-fluid-checkout' ),
			'id'            => 'wfc_checkout_header',
			'description'   => __( 'Display widgets at the checkout header.', 'woocommerce-fluid-checkout' ),
			'before_widget' => '<aside id="%1$s" class="widget %2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h5 class="widget-title">',
			'after_title'   => '</h5>',
		) );
		
		register_sidebar( array(
			'name'          => __( 'Checkout Below Progress Bar', 'woocommerce-fluid-checkout' ),
			'id'            => 'wfc_checkout_below_progress_bar',
			'description'   => __( 'Display widgets below the checkout progress bar.', 'woocommerce-fluid-checkout' ),
			'before_widget' => '<aside id="%1$s" class="widget %2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h5 class="widget-title">',
			'after_title'   => '</h5>',
		) );
		


		register_sidebar( array(
			'name'          => __( 'Checkout Order Summary - Inside', 'woocommerce-fluid-checkout' ),
			'id'            => 'wfc_checkout_order_summary_inside',
			'description'   => __( 'Display widgets inside the order summary at the checkout page.', 'woocommerce-fluid-checkout' ),
			'before_widget' => '<aside id="%1$s" class="widget %2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h5 class="widget-title">',
			'after_title'   => '</h5>',
		) );

		register_sidebar( array(
			'name'          => __( 'Checkout Order Summary - After', 'woocommerce-fluid-checkout' ),
			'id'            => 'wfc_checkout_order_summary_outside',
			'description'   => __( 'Display widgets after the order summary at the checkout page.', 'woocommerce-fluid-checkout' ),
			'before_widget' => '<aside id="%1$s" class="widget %2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h5 class="widget-title">',
			'after_title'   => '</h5>',
		) );

	}


	


	/**
	 * Output widget area on the checkout header.
	 */
	public function output_widget_area_checkout_header() {
		if ( is_active_sidebar( 'wfc_checkout_header' ) ) :
			dynamic_sidebar( 'wfc_checkout_header' );
		endif;
	}

	/**
	 * Output widget area below the checkout progress bar.
	 */
	public function output_widget_area_checkout_below_progress_bar() {
		if ( is_active_sidebar( 'wfc_checkout_below_progress_bar' ) ) :
			echo '<div class="wfc-checkout__steps-widgets">';
			dynamic_sidebar( 'wfc_checkout_below_progress_bar' );
			echo '</div>';
		endif;
	}



	/**
	 * Output widget area inside order review section.
	 */
	public function output_widget_area_order_review_inside() {
		if ( is_active_sidebar( 'wfc_checkout_order_summary_inside' ) ) :
			dynamic_sidebar( 'wfc_checkout_order_summary_inside' );
		endif;
	}

	/**
	 * Output widget area outside order review section.
	 */
	public function output_widget_area_order_review_outside() {
		if ( is_active_sidebar( 'wfc_checkout_order_summary_outside' ) ) :
			dynamic_sidebar( 'wfc_checkout_order_summary_outside' );
		endif;
	}

}

FluidCheckout_CheckoutWidgetAreas::instance();
