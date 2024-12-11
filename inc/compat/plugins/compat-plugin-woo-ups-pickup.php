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

		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// Persisted fields
		add_filter( 'fc_customer_persisted_data_clear_fields_order_processed', array( $this, 'change_customer_persisted_data_clear_fields_order_processed' ), 10 );

		// Checkout validation settings
		add_filter( 'fc_checkout_validation_script_settings', array( $this, 'change_js_settings_checkout_validation_reference_node' ), 10 );

		// Maybe set substep as incomplete
		add_filter( 'fc_is_substep_complete_shipping_method', array( $this, 'maybe_set_substep_incomplete_shipping_method' ), 10 );

		// Add substep review text lines
		add_filter( 'fc_substep_shipping_method_text_lines', array( $this, 'add_substep_text_lines_shipping_method' ), 10 );
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
		// Bail if not on checkout or doing AJAX call
		if ( ! function_exists( 'is_checkout' ) || ( ! is_checkout() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) ) { return; }

		// Bail if UPS Pickup class is not present
		if ( ! class_exists( 'WC_Ups_PickUps' ) ) { return; }

		// UPS Pickup
		if ( class_exists( WC_Ups_PickUps::METHOD_CLASS_NAME ) ) {
			$ups_pickup_class_object = $this->get_object_by_class_name_from_hooks( WC_Ups_PickUps::METHOD_CLASS_NAME );
			remove_action( 'woocommerce_after_template_part', array( $ups_pickup_class_object, 'review_order_shipping_pickups_location' ), 10 );
			add_action( 'fc_shipping_methods_after_packages_inside', array( $this, 'review_order_shipping_pickups_location' ), 10, 4 );
			add_action( 'fc_shipping_methods_after_packages_inside', array( $this, 'output_pickup_location_hidden_fields' ), 10 );
		}
	}



	/**
	 * Locate template files from this plugin.
	 */
	public function locate_template( $template, $template_name, $template_path ) {
		$_template = null;

		// Set template path to default value when not provided
		if ( ! $template_path ) { $template_path = 'woocommerce/'; };

		// Get plugin path
		$plugin_path = self::$directory_path . 'templates/compat/plugins/woo-ups-pickup/';

		// Get the template from this plugin, if it exists
		if ( file_exists( $plugin_path . $template_name ) ) {
			$_template = $plugin_path . $template_name;

			// Look for template file in the theme
			if ( apply_filters( 'fc_override_template_with_theme_file', false, $template, $template_name, $template_path ) ) {
				$_template_override = locate_template( array(
					trailingslashit( $template_path ) . $template_name,
					$template_name,
				) );
	
				// Check if files exist before changing template
				if ( file_exists( $_template_override ) ) {
					$_template = $_template_override;
				}
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
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'fc-compat-woo-ups-pickup-location-handler', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/woo-ups-pickup/ups-pickup-location-handler' ), array(), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-compat-woo-ups-pickup-location-handler', 'window.addEventListener("load",function(){UpsPickupLocationHandler.init();})' );
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-compat-woo-ups-pickup-location-handler' );
	}

	/**
	 * Maybe enqueue assets.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not at checkout
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return; }

		$this->enqueue_assets();
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
		// Bail if not on checkout or doing AJAX call
		if ( ! function_exists( 'is_checkout' ) || ( ! is_checkout() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) ) { return; }

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
	 * Output pickup location hidden fields.
	 */
	public function output_pickup_location_hidden_fields() {
		// Detect if cart has only virtual products
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
		
		$pickups_location1_value = WC()->checkout->get_value( 'pickups_location1' );
		$pickups_location2_value = WC()->checkout->get_value( 'pickups_location2' );
		?>
		<div class="ups-pickup-location-hidden-fields form-row validate-required fc-no-validation-icon">
			<span class="woocommerce-input-wrapper">
				<input type="hidden" name="pickups_location1" value="<?php echo esc_attr( $pickups_location1_value ); ?>" />
				<input type="hidden" name="pickups_location2" value="<?php echo esc_attr( $pickups_location2_value ); ?>" />
			</span>
		</div>
		<?php
	}

	/**
	 * Add settings to the plugin settings JS object for the checkout validation message reference node.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function change_js_settings_checkout_validation_reference_node( $settings ) {
		// Get current values
		$current_form_row_selector = array_key_exists( 'formRowSelector', $settings ) ? $settings[ 'formRowSelector' ] : '';
		$current_validate_field_selector = array_key_exists( 'validateFieldsSelector', $settings ) ? $settings[ 'validateFieldsSelector' ] : '';
		$current_reference_node_selector = array_key_exists( 'referenceNodeSelector', $settings ) ? $settings[ 'referenceNodeSelector' ] : '';
		$current_always_validate_selector = array_key_exists( 'alwaysValidateFieldsSelector', $settings ) ? $settings[ 'alwaysValidateFieldsSelector' ] : '';

		// Prepend new values to existing settings
		$settings[ 'formRowSelector' ] = '.ups-pickup-location-hidden-fields' . ( ! empty( $current_form_row_selector ) ? ', ' : '' ) . $current_form_row_selector;
		$settings[ 'validateFieldsSelector' ] = 'input[name="pickups_location1"]' . ( ! empty( $current_validate_field_selector ) ? ', ' : '' ) . $current_validate_field_selector;
		$settings[ 'referenceNodeSelector' ] = 'input[name="pickups_location1"]' . ( ! empty( $current_reference_node_selector ) ? ', ' : '' ) . $current_reference_node_selector;
		$settings[ 'alwaysValidateFieldsSelector' ] = 'input[name="pickups_location1"]' . ( ! empty( $current_always_validate_selector ) ? ', ' : '' ) . $current_always_validate_selector;

		return $settings;
	}



	/**
	 * Add pickup location fields to be cleared when order is processed.
	 *
	 * @param   array  $clear_field_keys  Checkout field keys to clear from the session after placing an order.
	 */
	public function change_customer_persisted_data_clear_fields_order_processed( $clear_field_keys ) {
		// Add fields to be cleared
		$clear_field_keys[] = 'pickups_location1';
		$clear_field_keys[] = 'pickups_location2';

		return $clear_field_keys;
	}



	/**
	 * Set the shipping method substep as incomplete when shipping method is UPS pickup location but a location has not yet been selected.
	 *
	 * @param   bool  $is_substep_complete  Whether the substep is complete or not.
	 */
	public function maybe_set_substep_incomplete_shipping_method( $is_substep_complete ) {
		// Bail if substep is already incomplete
		if ( ! $is_substep_complete ) { return $is_substep_complete; }

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

			// Maybe mark substep as incomplete if pickup location code is not accepted
			if ( $pickups_location1_prefix !== 'PKPS' && $pickups_location1_prefix !== 'PKPL' ) {
				$is_substep_complete = false;
				break;
			}
		}

		return $is_substep_complete;
	}



	/**
	 * Add the shipping methods substep review text lines for the selected pickup location.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_shipping_method( $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Get shipping packages
		$packages = WC()->shipping->get_packages();
		foreach ( $packages as $i => $package ) {
			// Get chosen shipping method for the current package
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';

			// Skip package if shipping method selected is not UPS pickup location
			if ( 'woo-ups-pickups' != $chosen_method ) { continue; }

			// Get pickup location code
			$pickups_location1_value = WC()->checkout->get_value( 'pickups_location1' );
			$pickups_location2_value = WC()->checkout->get_value( 'pickups_location2' );
			$pickups_location1_prefix = substr( $pickups_location1_value, 0, 4 );

			// Maybe mark step as incomplete if pickup location code is not accepted
			if ( $pickups_location1_prefix == 'PKPS' || $pickups_location1_prefix == 'PKPL' ) {
				// Get parsed data of the selected location
				$pickups_location2_value_parsed = json_decode( $pickups_location2_value );

				// Add review text lines
				$review_text_lines[] = '<strong>' . $pickups_location2_value_parsed->title . '</strong>&nbsp;(' . $pickups_location2_value_parsed->iid . ')';
				$review_text_lines[] = $pickups_location2_value_parsed->city . ', ' . $pickups_location2_value_parsed->street;
				$review_text_lines[] = '<small>' . $pickups_location2_value_parsed->zip . '</small>';
			}
		}

		return $review_text_lines;
	}

}

FluidCheckout_WooUPSPickup::instance();
