<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: DPD Baltic Shipping (by DPD).
 */
class FluidCheckout_WooShippingDPDBaltic extends FluidCheckout {

	/**
	 * The shipping method id.
	 */
	public const SHIPPING_METHOD_ID = 'dpd_parcels';
	
	/**
	 * Session field name.
	 */
	public const SESSION_FIELD_NAME = 'wc_shipping_dpd_parcels_terminal';


	/**
	 * Class name for the plugin which this compatibility class is related to.
	 */
	public const CLASS_NAME = 'DPD_Parcels';


	/**
	 * Hold cached terminal data to improve performance.
	 */
	private $terminal_cache = array();



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
		// Template file loader
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 100, 3 );

		// Shipping methods hooks
		add_action( 'woocommerce_shipping_init', array( $this, 'shipping_methods_hooks' ), 100 );
		
		// Persisted data
		add_action( 'fc_set_parsed_posted_data', array( $this, 'maybe_set_terminals_field_session_values' ), 10 );

		// Maybe set substep as incomplete
		add_filter( 'fc_is_substep_complete_shipping_method', array( $this, 'maybe_set_substep_incomplete_shipping_method' ), 10 );

		// Checkout validation settings
		add_filter( 'fc_checkout_validation_script_settings', array( $this, 'change_js_settings_checkout_validation' ), 10 );

		// Add substep review text lines
		add_filter( 'fc_substep_shipping_method_text_lines', array( $this, 'add_substep_text_lines_shipping_method' ), 10 );
	}

	/**
	 * Add or remove shipping method hooks.
	 */
	public function shipping_methods_hooks() {
		// Bail if class is not available
		if ( ! class_exists( self::CLASS_NAME ) ) { return; }

		// Get object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( self::CLASS_NAME );

		// Remove DPD's inefficient method and replace with the optimized version
		remove_action( 'woocommerce_review_order_after_shipping', array( $class_object, 'review_order_after_shipping' ), 10 );
		add_action( 'fc_shipping_methods_after_packages_inside', array( $this, 'review_order_after_shipping' ), 10 );
	}



	/**
	 * Review order after shipping.
	 * Optimized to avoid loading all 98k+ terminals by using direct SQL queries.
	 * COPIED AND ADAPTED FROM: DPD_Parcels::review_order_after_shipping().
	 * 
	 */
	public function review_order_after_shipping() {
		global $is_hook_executed;

		// Bail if class is not available
		if ( ! class_exists( self::CLASS_NAME ) ) { return; }

		// Get the DPD object to access its properties
		$dpd_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( self::CLASS_NAME );
		if ( empty( $dpd_object ) ) { return; }

		// Get currently selected shipping methods
		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

		if ( ! empty( $chosen_shipping_methods ) && substr( $chosen_shipping_methods[0], 0, strlen( self::SHIPPING_METHOD_ID ) ) === self::SHIPPING_METHOD_ID ) {
			$limit = 500;
			$page = 1;
			$offset = $limit * ( $page - 1 );
			$selected_terminal = WC()->session->get( self::SESSION_FIELD_NAME ) ?: '';
			$selected_terminal_name = '';

			// CHANGE: Use optimized method to get terminal name instead of `get_terminal_name()`.
			// This avoids loading all 98 897 terminals into memory by querying only the specific terminal needed.
			if ( ! empty( $selected_terminal ) ) {
				$selected_terminal_name = $this->get_terminal_name( $selected_terminal );
			}

			$has_more = $limit + 1;

			// Use DPD's pagination method (this is already optimized)
			$terminals_pagination = $dpd_object->get_terminals_pagination( WC()->customer->get_shipping_country(), $has_more, $offset );

			if ( count( $terminals_pagination ) > $limit ) {
				array_pop( $terminals_pagination );
			} else {
				$page = -1;
			}

			// CHANGE: Removed the call to `get_terminals()`
			// That call loaded all 98 897 terminals but the result was never used (it was commented out in the template_data below)

			$template_data = array(
				'terminals'  => $dpd_object->get_grouped_terminals( $terminals_pagination ),
				'field_name' => self::SESSION_FIELD_NAME,
				'field_id'   => self::SESSION_FIELD_NAME,
				'selected'   => $selected_terminal ? $selected_terminal : '',
				'selected_terminal_name' => $selected_terminal_name,
				'load_more_page' => $page,
			);

			do_action( self::SHIPPING_METHOD_ID . '_before_terminals' );

			$google_map_api = get_option( 'dpd_google_map_key' );

			if ( ! method_exists( $dpd_object, 'checkDoesNotFitInTerminal' ) || ! $dpd_object->checkDoesNotFitInTerminal( WC()->cart->get_cart() ) && ! $is_hook_executed ) {
				if ( '' != $google_map_api ) {
					// CHANGE: Use the already-retrieved $selected_terminal_name instead of calling get_terminal_name() again
					$template_data[ 'selected_name' ] = $selected_terminal_name;
					wc_get_template( 'checkout/form-shipping-dpd-terminals-with-map.php', $template_data );
				} else {
					wc_get_template( 'checkout/form-shipping-dpd-terminals.php', $template_data );
				}

				$is_hook_executed = true;
			}

			do_action( self::SHIPPING_METHOD_ID . '_after_terminals' );
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
		$plugin_path = self::$directory_path . 'templates/compat/plugins/woo-shipping-dpd-baltic/';

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
	 * Add settings to the plugin settings JS object for the checkout validation.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function change_js_settings_checkout_validation( $settings ) {
		// Get current values
		$current_validate_field_selector = array_key_exists( 'validateFieldsSelector', $settings ) ? $settings[ 'validateFieldsSelector' ] : '';
		$current_reference_node_selector = array_key_exists( 'referenceNodeSelector', $settings ) ? $settings[ 'referenceNodeSelector' ] : '';
		$current_always_validate_selector = array_key_exists( 'alwaysValidateFieldsSelector', $settings ) ? $settings[ 'alwaysValidateFieldsSelector' ] : '';

		// Prepend new values to existing settings
		$settings[ 'validateFieldsSelector' ] = 'input[name="' . self::SESSION_FIELD_NAME . '"]' . ( ! empty( $current_validate_field_selector ) ? ', ' : '' ) . $current_validate_field_selector;
		$settings[ 'referenceNodeSelector' ] = 'input[name="' . self::SESSION_FIELD_NAME . '"]' . ( ! empty( $current_reference_node_selector ) ? ', ' : '' ) . $current_reference_node_selector;
		$settings[ 'alwaysValidateFieldsSelector' ] = 'input[name="' . self::SESSION_FIELD_NAME . '"]' . ( ! empty( $current_always_validate_selector ) ? ', ' : '' ) . $current_always_validate_selector;

		return $settings;
	}



	/**
	 * Maybe set session data for the terminals field.
	 *
	 * @param  array  $posted_data   Post data for all checkout fields.
	 */
	public function maybe_set_terminals_field_session_values( $posted_data ) {
		// Bail if field value was not posted
		if ( ! array_key_exists( self::SESSION_FIELD_NAME, $posted_data ) ) { return $posted_data; }

		// Save field value to session, as it is needed for the plugin to recover its value
		WC()->session->set( self::SESSION_FIELD_NAME, $posted_data[ self::SESSION_FIELD_NAME ] );

		// Return unchanged posted data
		return $posted_data;
	}



	/**
	 * Set the shipping substep as incomplete.
	 *
	 * @param   bool  $is_substep_complete  Whether the step is complete or not.
	 */
	public function maybe_set_substep_incomplete_shipping_method( $is_substep_complete ) {
		// Bail if substep is already incomplete
		if ( ! $is_substep_complete ) { return $is_substep_complete; }

		// Bail if class is not available
		if ( ! class_exists( self::CLASS_NAME ) ) { return $is_substep_complete; }

		// Get object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( self::CLASS_NAME );

		// Get currently selected shipping methods
		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

		// Bail if there are no shipping methods selected
		if ( empty( $chosen_shipping_methods ) ) { return $is_substep_complete; }

		// Check whether target shipping method is selected
		$has_target_shipping_method = false;
		foreach ( $chosen_shipping_methods as $shipping_method_id ) {
			if ( 0 === strpos( $shipping_method_id, self::SHIPPING_METHOD_ID ) ) {
				$has_target_shipping_method = true;
				break;
			}
		}

		// Bail if target shipping method is not selected
		if ( ! $has_target_shipping_method ) { return $is_substep_complete; }

		// Get selected terminal
		$selected_terminal = WC()->session->get( self::SESSION_FIELD_NAME );

		// Maybe set substep as incomplete
		if ( empty( $selected_terminal ) ) {
			$is_substep_complete = false;
		}

		return $is_substep_complete;
	}



	/**
	 * Get terminal data by ID directly from database.
	 * 
	 * @param  string  $terminal_id  The terminal ID.
	 */
	public function get_terminal_by_id( $terminal_id ) {
		// Try to return value from cache
		if ( isset( $this->terminal_cache[ $terminal_id ] ) ) {
			return $this->terminal_cache[ $terminal_id ];
		}

		global $wpdb;

		// Query only the specific terminal needed
		$terminal = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}dpd_terminals WHERE parcelshop_id = %s LIMIT 1",
			$terminal_id
		) );

		// Maybe set cache value
		if ( $terminal ) {
			$this->terminal_cache[ $terminal_id ] = $terminal;
		}

		return $terminal;
	}



	/**
	 * Get formatted terminal name by ID (optimized version).
	 * 
	 * @param  string  $terminal_id  The terminal ID.
	 */
	public function get_terminal_name( $terminal_id ) {
		// Get terminal
		$terminal = $this->get_terminal_by_id( $terminal_id );

		// Bail if terminal data is not available
		if ( ! is_object( $terminal ) || ! isset( $terminal->company, $terminal->street ) ) { return; }

		// Generate terminal name
		$terminal_name = $terminal->company . ', ' . $terminal->street;

		return $terminal_name;
	}



	/**
	 * Add the shipping methods substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_shipping_method( $review_text_lines = array() ) {
		// Maybe skip adding pickup point address as review text lines
		if ( true === apply_filters( 'fc_skip_add_pickup_point_info_as_review_text_lines', false ) ) { return $review_text_lines; }

		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Bail if class is not available
		if ( ! class_exists( self::CLASS_NAME ) ) { return $review_text_lines; }

		// Get object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( self::CLASS_NAME );

		// Get currently selected shipping methods
		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

		// Bail if there are no shipping methods selected
		if ( empty( $chosen_shipping_methods ) ) { return $review_text_lines; }

		// Check whether target shipping method is selected
		$has_target_shipping_method = false;
		foreach ( $chosen_shipping_methods as $shipping_method_id ) {
			if ( 0 === strpos( $shipping_method_id, self::SHIPPING_METHOD_ID ) ) {
				$has_target_shipping_method = true;
				break;
			}
		}

		// Bail if target shipping method is not selected
		if ( ! $has_target_shipping_method ) { return $review_text_lines; }

		// Get selected terminal
		$selected_terminal = WC()->session->get( self::SESSION_FIELD_NAME );

		// Bail if there is no selected terminal
		if ( empty( $selected_terminal ) ) { return $review_text_lines; }

		// Use optimized method to get terminal name
		$terminal_name = $this->get_terminal_name( $selected_terminal );

		// Maybe fallback to original method if optimized version fails
		if ( ! $terminal_name ) {
			$terminal_name = $class_object->get_terminal_name( $selected_terminal );
		}

		// Add terminal name as review text line
		if ( $terminal_name ) {
			$review_text_lines[] = $terminal_name;
		}

		return $review_text_lines;
	}

}

FluidCheckout_WooShippingDPDBaltic::instance();
