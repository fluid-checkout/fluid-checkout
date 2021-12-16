<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: German Market (by MarketPress).
 */
class FluidCheckout_WooCommerceGermanMarket extends FluidCheckout {

	/**
	 * Temporarily holds the place order button HTML.
	 *
	 * @var string
	 */
	public static $button_html;



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
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// General
		add_filter( 'body_class', array( $this, 'add_body_class' ), 10 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Has place order button placement changes
		if ( has_filter( 'woocommerce_order_button_html', array( 'WGM_Template', 'remove_order_button_html' ) ) ) {
			// General
			add_filter( 'body_class', array( $this, 'add_body_class_button_placement' ), 10 );

			// Place order button on payment section
			add_filter( 'woocommerce_order_button_html', array( $this, 'retrieve_order_button_html' ), 9998 );
			add_filter( 'woocommerce_order_button_html', array( $this, 'restore_order_button_html' ), 10000 );
			
			// Checkout widgets
			if ( class_exists( 'FluidCheckout_CheckoutWidgetAreas' ) ) {
				add_action( 'woocommerce_checkout_order_review', array( FluidCheckout_CheckoutWidgetAreas::instance(), 'output_widget_area_checkout_place_order_below' ), 10000 );
			}
		}
	}



	/**
	 * Add page body class for feature detection.
	 *
	 * @param array $classes Classes for the body element.
	 */
	public function add_body_class( $classes ) {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ){ return $classes; }

		$classes[] = 'has-fc-compat-german-market';
		return $classes;
	}

	/**
	 * Add page body class for feature detection.
	 *
	 * @param array $classes Classes for the body element.
	 */
	public function add_body_class_button_placement( $classes ) {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ){ return $classes; }

		$classes[] = 'has-fc-compat-german-market-button-placement';
		return $classes;
	}



	/**
	 * Retrieve and save the contents of the place order button to a local variable.
	 *
	 * @param   string  $button_html  The place order button html.
	 */
	public function retrieve_order_button_html( $button_html ) {
		self::$button_html = $button_html;
		return $button_html;
	}

	/**
	 * Restore the contents of the place order button from the local variable after German Market has remove it.
	 *
	 * @param   string  $button_html  The place order button html.
	 */
	public function restore_order_button_html( $button_html ) {
		return self::$button_html;
	}

}

FluidCheckout_WooCommerceGermanMarket::instance();
