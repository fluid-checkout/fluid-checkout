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
		add_action( 'wfc_checkout_after_order_review_inside', array( $this, 'output_widget_area_order_review_inside' ), 50 );
		add_action( 'wfc_checkout_after_order_review', array( $this, 'output_widget_area_order_review_outside' ), 50 );
	}





	/**
	 * Register widget areas for the checkout page.
	 */
	public function register_checkout_widgets_areas() {

		register_sidebar( array(
			'name'          => __( 'Checkout Header', 'fluid-checkout' ),
			'id'            => 'wfc_checkout_header',
			'description'   => __( 'Display widgets at the checkout header.', 'fluid-checkout' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h5 class="widget-title">',
			'after_title'   => '</h5>',
		) );

		register_sidebar( array(
			'name'          => __( 'Checkout Below Progress Bar', 'fluid-checkout' ),
			'id'            => 'wfc_checkout_below_progress_bar',
			'description'   => __( 'Display widgets below the checkout progress bar.', 'fluid-checkout' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h5 class="widget-title">',
			'after_title'   => '</h5>',
		) );



		register_sidebar( array(
			'name'          => __( 'Checkout Order Summary - Inside', 'fluid-checkout' ),
			'id'            => 'wfc_checkout_order_summary_inside',
			'description'   => __( 'Display widgets inside the order summary at the checkout page.', 'fluid-checkout' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h5 class="widget-title">',
			'after_title'   => '</h5>',
		) );

		register_sidebar( array(
			'name'          => __( 'Checkout Sidebar - After', 'fluid-checkout' ),
			'id'            => 'wfc_checkout_sidebar_after',
			'description'   => __( 'Display widgets on the checkout sidebar after the order summary.', 'fluid-checkout' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
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
	 *
	 * @param   bool  $is_sidebar_widget  Whether or not outputting the sidebar.
	 */
	public function output_widget_area_order_review_inside( $is_sidebar_widget ) {
		// Bail if not outputting widget areas for the sidebar
		if ( ! $is_sidebar_widget ) { return; }

		if ( is_active_sidebar( 'wfc_checkout_order_summary_inside' ) ) :
			echo '<div class="wfc-checkout-order-review__widgets-inside">';
			dynamic_sidebar( 'wfc_checkout_order_summary_inside' );
			echo '</div>';
		endif;
	}

	/**
	 * Output widget area outside order review section.
	 *
	 * @param   bool  $is_sidebar_widget  Whether or not outputting the sidebar.
	 */
	public function output_widget_area_order_review_outside( $is_sidebar_widget ) {
		// Bail if not outputting widget areas for the sidebar
		if ( ! $is_sidebar_widget ) { return; }

		if ( is_active_sidebar( 'wfc_checkout_sidebar_after' ) ) :
			echo '<div class="wfc-checkout-order-review__widgets-outside">';
			dynamic_sidebar( 'wfc_checkout_sidebar_after' );
			echo '</div>';
		endif;
	}

}

FluidCheckout_CheckoutWidgetAreas::instance();
