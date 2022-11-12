<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Woocommerce UPS Israel Domestic Printing Plugin (by O.P.S.I International Handling Ltd).
 */
class FluidCheckout_WooUPSPickup extends FluidCheckout {

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
		// Very late hooks
		add_action( 'woocommerce_after_template_part', array( $this, 'very_late_hooks' ), 1 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Bail if UPS Pickup class is not present
		if ( ! class_exists( 'WC_Ups_PickUps' ) ) { return; }

		// UPS Pickup
		if ( class_exists( WC_Ups_PickUps::METHOD_CLASS_NAME ) ) {
			$ups_pickup_class_object = $this->get_object_by_class_name_from_hooks( WC_Ups_PickUps::METHOD_CLASS_NAME );
			remove_action( 'woocommerce_after_template_part', array( $ups_pickup_class_object, 'review_order_shipping_pickups_location' ), 10 );
			add_action( 'woocommerce_after_template_part', array( $this, 'review_order_shipping_pickups_location' ), 10, 4 );
		}
	}



	/**
	 * Render the pickup location selection box on the checkout form.
	 * 
	 * @param $template_name
	 * @param $template_path
	 * @param $located
	 * @param $args
	 */
	public function review_order_shipping_pickups_location( $template_name, $template_path, $located, $args ) {
		global $wp_query;
		$ups_pickup_class_object = $this->get_object_by_class_name_from_hooks( WC_Ups_PickUps::METHOD_CLASS_NAME );
		$is_ajax = defined( 'WC_DOING_AJAX' ) && 'update_order_review' === $wp_query->get( 'wc-ajax' );
		if ( 'fc/cart/shipping-methods-available.php' == $template_name && ( is_checkout() || $is_ajax ) ) {
			$plugin_dir = WP_PLUGIN_DIR . '/woo-ups-pickup';
			if ( is_dir( $plugin_dir ) ) {
				include_once( $plugin_dir . '/templates/pickup-location.php');
				$helper = new Ups\Helper\Ups();

				if ( ! $helper->isPickUpsProductsPointsOverTheMax() && $ups_pickup_class_object->id == $args['chosen_method'] ) {
					include_once( $plugin_dir . '/templates/pickup-button-html.php' );
				}
			}
		}
	}

}

FluidCheckout_WooUPSPickup::instance();
