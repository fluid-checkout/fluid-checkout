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

		// Template file loader
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 100, 3 );
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
			add_action( 'fc_shipping_methods_after_packages_inside', array( $this, 'review_order_shipping_pickups_location' ), 10, 4 );
		}
	}



	/**
	 * Locate template files from this plugin.
	 */
	public function locate_template( $template, $template_name, $template_path ) {
		global $woocommerce;
		$_template = null;

		// Set template path to default value when not provided
		if ( ! $template_path ) { $template_path = $woocommerce->template_url; };

		// Get plugin path
		$plugin_path  = self::$directory_path . 'templates/compat/plugins/woo-ups-pickup/';

		// Get the template from this plugin, if it exists
		if ( file_exists( $plugin_path . $template_name ) ) {
			$_template = $plugin_path . $template_name;

			// Look for template file in the theme
			if ( apply_filters( 'fc_override_template_with_theme_file', false, $template, $template_name, $template_path ) ) {
				$_template = locate_template( array(
					$template_path . $template_name,
					$template_name,
				) );
			}
		}

		// Use default template
		if ( ! $_template ) {
			$_template = $template;
		}

		// Return what we found
		return $_template;
	}



	/**
	 * Render the pickup location selection box on the checkout form.
	 * 
	 * @param $template_name
	 * @param $template_path
	 * @param $located
	 * @param $args
	 */
	public function review_order_shipping_pickups_location() {
		// Check if plugin folder exists
		$plugin_dir = WP_PLUGIN_DIR . '/woo-ups-pickup';
		if ( ! is_dir( $plugin_dir ) ) { return; }

		// Get shipping method object
		$ups_pickup_class_object = $this->get_object_by_class_name_from_hooks( WC_Ups_PickUps::METHOD_CLASS_NAME );

		// Load pickup locations template
		include_once( $plugin_dir . '/includes/templates/pickup-location.php');

		// Get shipping packages
		$packages = WC()->shipping->get_packages();
		foreach ( $packages as $i => $package ) {
			// Get chosen shipping method for the current package
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';

			// Maybe load pickup locations button template
			$helper = new Ups\Helper\Ups();
			if ( ! $helper->isPickUpsProductsPointsOverTheMax() && $ups_pickup_class_object->id == $chosen_method ) {
				wc_get_template( 'cart/pickup-button-html.php', array(
					'shipping_method'           => $ups_pickup_class_object,
				) );
			}
		}
	}

}

FluidCheckout_WooUPSPickup::instance();
