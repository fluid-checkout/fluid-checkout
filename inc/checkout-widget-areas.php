<?php
defined( 'ABSPATH' ) || exit;

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
		// General
		add_filter( 'body_class', array( $this, 'add_body_class' ), 10 );

		// Widget Areas
		add_action( 'widgets_init', array( $this, 'register_widgets_areas' ), 50 );
		add_action( 'fc_checkout_header_widgets', array( $this, 'output_widget_area_checkout_header' ), 50 );
		add_action( 'woocommerce_before_checkout_form_cart_notices', array( $this, 'output_widget_area_checkout_header_below' ), 3 ); // Displays widgets before the progress bar
		add_action( 'fc_checkout_after_order_review_inside', array( $this, 'output_widget_area_order_review_inside' ), 50 );
		add_action( 'fc_checkout_after_order_review', array( $this, 'output_widget_area_order_review_outside' ), 50 );
		add_action( 'fc_place_order', array( $this, 'output_widget_area_checkout_place_order_below' ), 50 );
		add_action( 'fc_checkout_footer_widgets', array( $this, 'output_widget_area_checkout_footer' ), 50 );
	}



	/**
	 * Undo hooks.
	 */
	public function undo_hooks() {
		// General
		remove_filter( 'body_class', array( $this, 'add_body_class' ), 10 );

		// Widget Areas
		remove_action( 'widgets_init', array( $this, 'register_widgets_areas' ), 50 );
		remove_action( 'fc_checkout_header_widgets', array( $this, 'output_widget_area_checkout_header' ), 50 );
		remove_action( 'woocommerce_before_checkout_form_cart_notices', array( $this, 'output_widget_area_checkout_header_below' ), 3 ); // Displays widgets before the progress bar
		remove_action( 'fc_checkout_after_order_review_inside', array( $this, 'output_widget_area_order_review_inside' ), 50 );
		remove_action( 'fc_checkout_after_order_review', array( $this, 'output_widget_area_order_review_outside' ), 50 );
		remove_action( 'fc_place_order', array( $this, 'output_widget_area_checkout_place_order_below' ), 50 );
		remove_action( 'fc_checkout_footer_widgets', array( $this, 'output_widget_area_checkout_footer' ), 50 );
	}



	/**
	 * Add page body class for feature detection.
	 *
	 * @param array $classes Classes for the body element.
	 */
	public function add_body_class( $classes ) {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return $classes; }

		// Maybe add extra body class
		if ( 'yes' === get_option( 'fc_enable_checkout_widget_area_sidebar_last_step', 'no' ) ) {
			$classes[] = 'has-fc-sidebar-widget-area-last-step-only';
		}

		return $classes;
	}



	/**
	 * Register widget areas.
	 */
	public function register_widgets_areas() {

		// Only register header widget areas when using Fluid Checkout header
		if ( 'yes' === get_option( 'fc_hide_site_header_footer_at_checkout', 'yes' ) ) {

			register_sidebar( array(
				'name'          => __( 'Checkout Header - Desktop', 'fluid-checkout' ),
				'id'            => 'fc_checkout_header',
				'description'   => __( 'Display widgets on the checkout header for large screens. Only displayed if using the plugin\'s checkout header.', 'fluid-checkout' ),
				'before_widget' => '<div id="%1$s" class="widget %2$s">',
				'after_widget'  => '</div>',
				'before_title'  => '<h2 class="widget-title">',
				'after_title'   => '</h2>',
			) );

			register_sidebar( array(
				'name'          => __( 'Checkout Header - Mobile', 'fluid-checkout' ),
				'id'            => 'fc_checkout_below_header',
				'description'   => __( 'Display widgets below the checkout header for mobile devices. Only displayed if using the plugin\'s checkout header.', 'fluid-checkout' ),
				'before_widget' => '<div id="%1$s" class="widget %2$s">',
				'after_widget'  => '</div>',
				'before_title'  => '<h2 class="widget-title">',
				'after_title'   => '</h2>',
			) );

		}



		register_sidebar( array(
			'name'          => __( 'Checkout Sidebar', 'fluid-checkout' ),
			'id'            => 'fc_checkout_sidebar_after',
			'description'   => __( 'Display widgets on the checkout sidebar after the order summary.', 'fluid-checkout' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		) );



		register_sidebar( array(
			'name'          => __( 'Checkout Order Summary', 'fluid-checkout' ),
			'id'            => 'fc_order_summary_after',
			'description'   => __( 'Display widgets inside the order summary at the checkout page.', 'fluid-checkout' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h4 class="widget-title">',
			'after_title'   => '</h4>',
		) );

		register_sidebar( array(
			'name'          => __( 'Checkout Below Place Order', 'fluid-checkout' ),
			'id'            => 'fc_place_order_after',
			'description'   => __( 'Display widgets below the place order button at the checkout page.', 'fluid-checkout' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h4 class="widget-title">',
			'after_title'   => '</h4>',
		) );



		// Only register footer widget areas when using Fluid Checkout footer
		if ( 'yes' === get_option( 'fc_hide_site_header_footer_at_checkout', 'yes' ) ) {

			register_sidebar( array(
				'name'          => __( 'Checkout Footer', 'fluid-checkout' ),
				'id'            => 'fc_checkout_footer',
				'description'   => __( 'Display widgets on the checkout footer. Only displayed if using the plugin\'s checkout footer.', 'fluid-checkout' ),
				'before_widget' => '<div id="%1$s" class="widget %2$s">',
				'after_widget'  => '</div>',
				'before_title'  => '<h2 class="widget-title">',
				'after_title'   => '</h2>',
			) );

		}

	}





	/**
	 * Output widget area on the checkout header.
	 */
	public function output_widget_area_checkout_header() {
		// Bail if not on the checkout page
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) { return; }

		if ( is_active_sidebar( 'fc_checkout_header' ) || has_action( 'fc_checkout_header_widgets_inside_before' ) || has_action( 'fc_checkout_header_widgets_inside_after' ) ) :
			echo '<div class="fc-widget-area fc-checkout__header-widgets">';
			do_action( 'fc_checkout_header_widgets_inside_before' );
			dynamic_sidebar( 'fc_checkout_header' );
			do_action( 'fc_checkout_header_widgets_inside_after' );
			echo '</div>';
		endif;
	}

	/**
	 * Output widget area below the checkout progress bar.
	 */
	public function output_widget_area_checkout_header_below() {
		// Bail if not on the checkout page
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) { return; }

		if ( is_active_sidebar( 'fc_checkout_below_header' ) ) :
			echo '<div class="fc-widget-area fc-checkout__below-header-widgets">';
			dynamic_sidebar( 'fc_checkout_below_header' );
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

		if ( is_active_sidebar( 'fc_checkout_sidebar_after' ) ) :
			$additional_classes = 'yes' === get_option( 'fc_enable_checkout_widget_area_sidebar_last_step', 'no' ) ? 'last-step-only' : '';
			echo '<div class="fc-widget-area fc-checkout-order-review__widgets-outside fc-clearfix ' . $additional_classes . '">';
			dynamic_sidebar( 'fc_checkout_sidebar_after' );
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

		if ( is_active_sidebar( 'fc_order_summary_after' ) ) :
			echo '<div class="fc-widget-area fc-checkout-order-review__widgets-inside fc-clearfix">';
			dynamic_sidebar( 'fc_order_summary_after' );
			echo '</div>';
		endif;
	}



	/**
	 * Output widget area below the checkout place order button.
	 */
	public function output_widget_area_checkout_place_order_below() {
		if ( is_active_sidebar( 'fc_place_order_after' ) ) :
			echo '<div class="fc-widget-area fc-checkout__below-place-order-widgets fc-clearfix">';
			dynamic_sidebar( 'fc_place_order_after' );
			echo '</div>';
		endif;
	}



	/**
	 * Output widget area on the checkout footer.
	 */
	public function output_widget_area_checkout_footer() {
		// Bail if not on the checkout page
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) { return; }

		if ( is_active_sidebar( 'fc_checkout_footer' ) || has_action( 'fc_checkout_footer_widgets_inside_before' ) || has_action( 'fc_checkout_footer_widgets_inside_after' ) ) :
			do_action( 'fc_checkout_footer_widgets_inside_before' );
			dynamic_sidebar( 'fc_checkout_footer' );
			do_action( 'fc_checkout_footer_widgets_inside_after' );
		endif;
	}

}

FluidCheckout_CheckoutWidgetAreas::instance();
