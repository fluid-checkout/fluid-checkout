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
		// Template redirect hooks
		add_action( 'template_redirect', array( $this, 'template_redirect_hooks' ), 100 );
		
		// Very late hooks
		add_action( 'woocommerce_after_template_part', array( $this, 'template_part_hooks' ), 1 );

		// Template file loader
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 100, 3 );

		// Checkout fields
		add_action( 'woocommerce_checkout_fields', array( $this, 'wc_pickup_custom_override_checkout_fields' ), 10000 );

		// Maybe set step as incomplete
		add_filter( 'fc_is_step_complete_shipping', array( $this, 'maybe_set_step_incomplete_shipping' ), 10 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function template_redirect_hooks() {
		// Bail if UPS Pickup class is not present
		if ( ! class_exists( 'WC_Ups_PickUps' ) ) { return; }

		// UPS Pickup
		if ( class_exists( WC_Ups_PickUps::METHOD_CLASS_NAME ) ) {
			$ups_pickup_class_object = $this->get_object_by_class_name_from_hooks( WC_Ups_PickUps::METHOD_CLASS_NAME );
			remove_filter( 'woocommerce_checkout_fields', array( $ups_pickup_class_object, 'wc_pickup_custom_override_checkout_fields' ), 10000 );
		}
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function template_part_hooks() {
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
		wc_get_template( 'cart/pickup-location.php' );

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



	/**
	 * Add custom checkout fields to hold the value for the selected location.
	 *
	 * @param   Array  $fields  The checkout fields groups with all field attributes.
	 */
	public function wc_pickup_custom_override_checkout_fields( $fields ) {
        // Detect if cart has only virutal products
		$is_virtual = true;
        $cart_items = WC()->cart->cart_contents;
        foreach ( $cart_items as $item ) {
            $product = $item['data'];
            if ( is_callable( array( $product, 'is_virtual' ) ) && ! $product->is_virtual() ) {
                $is_virtual = false;
                break;
            }
        }

		// Bail if cart has only virtual products
        if ( $is_virtual ) { return $fields; }

		// Add pickup location 1 field
        $fields['billing']['pickups_location1'] = array(
            'label' => __( 'Number', 'woocommerce' ),
            'placeholder' => _x( 'Please select point', 'placeholder', 'woocommerce' ),
            'required' => false,
            'class' => array( 'form-row-wide' ),
			'type' => 'hidden',
        );

		// Add pickup location 2 field
        $fields['billing']['pickups_location2'] = array(
            'label' => __( 'Point', 'woocommerce' ),
            'placeholder' => _x( 'Please select point', 'placeholder', 'woocommerce' ),
            'required' => false,
            'class' => array( 'form-row-wide' ),
			'type' => 'hidden',
        );

        return $fields;
    }



	/**
	 * Set the shipping step as incomplete when shipping method is UPS pickup location but a location has not yet been selected.
	 *
	 * @param   bool  $is_step_complete  Whether the step is complete or not.
	 */
	public function maybe_set_step_incomplete_shipping( $is_step_complete ) {
		// Bail if step is already incomplete
		if ( ! $is_step_complete ) { return $is_step_complete; }

		// Get shipping packages
		$packages = WC()->shipping->get_packages();
		foreach ( $packages as $i => $package ) {
			// Get chosen shipping method for the current package
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';

			// Skip package if shipping method selected is not UPS pickup location
			if ( 'woo-ups-pickups' != $chosen_method ) { continue; }

			// Get pickup location code
			$pickups_location1_value = WC()->checkout->get_value( 'pickups_location1' );
			$pickups_location1_prefix = substr( $pickups_location1_value, 0, 4 );

			// Maybe mark step as incomplete if pickup location code is not accepted
			if ( $pickups_location1_prefix !== 'PKPS' && $pickups_location1_prefix !== 'PKPL' ) {
				$is_step_complete = false;
				break;
			}
		}

		return $is_step_complete;
	}

}

FluidCheckout_WooUPSPickup::instance();
