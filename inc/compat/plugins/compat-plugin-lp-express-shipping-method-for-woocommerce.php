<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: "LP Express" Shipping Method for WooCommerce (by Martynas Žaliaduonis).
 */
class FluidCheckout_LPExpressShippingMethodForWooCommerce extends FluidCheckout {

	/**
	 * The shipping method id.
	 */
	public const SHIPPING_METHOD_ID = 'lpexpress_terminals';



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

		// Persisted data
		add_action( 'fc_set_parsed_posted_data', array( $this, 'maybe_set_terminals_field_session_values' ), 10 );

		// Shipping methods hooks
		add_action( 'woocommerce_shipping_init', array( $this, 'shipping_methods_hooks' ), 100 );

		// Maybe set substep as incomplete
		add_filter( 'fc_is_substep_complete_shipping_method', array( $this, 'maybe_set_substep_incomplete_shipping_method' ), 10 );

		// Add substep review text lines
		add_filter( 'fc_substep_shipping_method_text_lines', array( $this, 'add_substep_text_lines_shipping_method' ), 10 );
	}

	/**
	 * Add or remove shipping method hooks.
	 */
	public function shipping_methods_hooks() {
		// Bail if class is not available
		$class_name = 'WC_LPExpress_Terminals_Shipping_Method';
		if ( ! class_exists( $class_name ) ) { return; }

		// Get object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $class_name );

		// Move shipping method hooks
		remove_action( 'woocommerce_review_order_after_shipping', array( $class_object, 'review_order_after_shipping' ), 10 );
		add_action( 'fc_shipping_methods_after_packages_inside', array( $class_object, 'review_order_after_shipping' ), 10 );
	}



	/**
	 * Locate template files from this plugin.
	 */
	public function locate_template( $template, $template_name, $template_path ) {
		$_template = null;

		// Set template path to default value when not provided
		if ( ! $template_path ) { $template_path = 'woocommerce/'; };

		// Get plugin path
		$plugin_path = self::$directory_path . 'templates/compat/plugins/lp-express-shipping-method-for-woocommerce/';

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
	 * Maybe set session data for the terminals field.
	 *
	 * @param  array  $posted_data   Post data for all checkout fields.
	 */
	public function maybe_set_terminals_field_session_values( $posted_data ) {
		$field_key = 'wc_lpexpress_terminals_info';
		
		// Bail if field value was not posted
		if ( ! array_key_exists( $field_key, $posted_data ) ) { return $posted_data; }

		// Save field value to session, as it is needed for the plugin to recover its value
		WC()->session->set( $field_key, $posted_data[ $field_key ] );
		WC()->session->set( self::SHIPPING_METHOD_ID, $posted_data[ $field_key ] ); // Actually used to determine selected field option

		// Return unchanged posted data
		return $posted_data;
	}


	/**
	 * Set the shipping method substep as incomplete.
	 *
	 * @param   bool  $is_substep_complete  Whether the substep is complete or not.
	 */
	public function maybe_set_substep_incomplete_shipping_method( $is_substep_complete ) {
		// Bail if substep is already incomplete
		if ( ! $is_substep_complete ) { return $is_substep_complete; }

		// Bail if class is not available
		$class_name = 'WC_LPExpress_Terminals_Shipping_Method';
		if ( ! class_exists( $class_name ) ) { return $is_substep_complete; }

		// Get object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $class_name );

		// Get currently selected shipping methods
		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

		// Bail if LP is not selected as the shipping method
		if( empty( $chosen_shipping_methods ) || ! in_array( self::SHIPPING_METHOD_ID, $chosen_shipping_methods ) ) { return $is_substep_complete; }

		// Get selected terminal
		$selected_terminal = WC()->session->get( self::SHIPPING_METHOD_ID );

		// Maybe set substep as incomplete
		if ( empty( $selected_terminal ) ) {
			$is_substep_complete = false;
		}

		return $is_substep_complete;
	}



	/**
	 * Add the shipping methods substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_shipping_method( $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Bail if class is not available
		$class_name = 'WC_LPExpress_Terminals_Shipping_Method';
		if ( ! class_exists( $class_name ) ) { return $review_text_lines; }

		// Get object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $class_name );

		// Get currently selected shipping methods
		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

		// Bail if LP is not selected as the shipping method
		if( empty( $chosen_shipping_methods ) || ! in_array( self::SHIPPING_METHOD_ID, $chosen_shipping_methods ) ) { return $review_text_lines; }

		// Get selected terminal
		$selected_terminal = WC()->session->get( self::SHIPPING_METHOD_ID );

		// Bail if there is no selected terminal
		if ( empty( $selected_terminal ) ) { return $review_text_lines; }

		// Get terminal info
		$terminal = $class_object->get_terminal_info( $selected_terminal );

		// Bail if there is not information for selected terminal
		if ( empty( $terminal ) ) { return $review_text_lines; }

		// Add terminal name as review text line
		$review_text_lines[] = $terminal->name;

		return $review_text_lines;
	}

}

FluidCheckout_LPExpressShippingMethodForWooCommerce::instance();
