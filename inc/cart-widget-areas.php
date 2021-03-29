<?php
/**
 * Widget Areas on the checkout page.
 */
class FluidCheckout_CartWidgetAreas extends FluidCheckout {

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
		add_action( 'widgets_init', array( $this, 'register_cart_widgets_areas' ), 50 );
		add_action( 'woocommerce_after_cart_totals', array( $this, 'output_widget_area_cart_totals_inside' ), 50 );
		add_action( 'woocommerce_cart_collaterals', array( $this, 'output_widget_area_cart_totals_outside' ), 11 );
	}





	/**
	 * Register widget areas for the cart pages.
	 */
	public function register_cart_widgets_areas() {

		register_sidebar( array(
			'name'          => __( 'Cart Totals - Inside', 'woocommerce-fluid-checkout' ),
			'id'            => 'wfc_cart_totals_inside',
			'description'   => __( 'Display widgets on cart totals section at checkout.', 'woocommerce-fluid-checkout' ),
			'before_widget' => '<aside id="%1$s" class="widget %2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h5 class="widget-title">',
			'after_title'   => '</h5>',
		) );

		register_sidebar( array(
			'name'          => __( 'Cart Totals - After', 'woocommerce-fluid-checkout' ),
			'id'            => 'wfc_cart_totals_outside',
			'description'   => __( 'Display widgets after the cart totals section at checkout.', 'woocommerce-fluid-checkout' ),
			'before_widget' => '<aside id="%1$s" class="widget %2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h5 class="widget-title">',
			'after_title'   => '</h5>',
		) );

	}



	

	/**
	 * Output widget area inside cart totals section.
	 */
	public function output_widget_area_cart_totals_inside() {
		if ( is_active_sidebar( 'wfc_cart_totals_inside' ) ) :
			dynamic_sidebar( 'wfc_cart_totals_inside' );
		endif;
	}

	/**
	 * Output widget area outside cart totals section.
	 */
	public function output_widget_area_cart_totals_outside() {
		if ( is_active_sidebar( 'wfc_cart_totals_outside' ) ) :
			dynamic_sidebar( 'wfc_cart_totals_outside' );
		endif;
	}
}

FluidCheckout_CartWidgetAreas::instance();
