<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Delivery & Pickup Date Time for WooCommerce (by CodeRockz).
 */
class FluidCheckout_WooDelivery extends FluidCheckout {

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

		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );

		// Persisted data
		add_action( 'fc_set_parsed_posted_data', array( $this, 'maybe_set_datepicker_fields_session_values' ), 10 );

		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Plugin element hooks
		$this->plugin_elements_hooks();
	}

	/**
	 * Add or remove hooks for the plugin elements.
	 */
	public function plugin_elements_hooks() {
		// Bail if plugin classes available
		if ( ! class_exists( 'Coderockz_Woo_Delivery' ) || ! class_exists( 'Coderockz_Woo_Delivery_Public' ) ) { return; }

		// Remove plugin hooks
		$this->remove_action_for_class( 'woocommerce_checkout_billing', array( 'Coderockz_Woo_Delivery_Public', 'coderockz_woo_delivery_add_custom_field' ), 10 );
		$this->remove_action_for_class( 'woocommerce_after_checkout_billing_form', array( 'Coderockz_Woo_Delivery_Public', 'coderockz_woo_delivery_add_custom_field' ), 10 );
		$this->remove_action_for_class( 'woocommerce_checkout_shipping', array( 'Coderockz_Woo_Delivery_Public', 'coderockz_woo_delivery_add_custom_field' ), 10 );
		$this->remove_action_for_class( 'woocommerce_after_checkout_shipping_form', array( 'Coderockz_Woo_Delivery_Public', 'coderockz_woo_delivery_add_custom_field' ), 10 );
		$this->remove_action_for_class( 'woocommerce_before_order_notes', array( 'Coderockz_Woo_Delivery_Public', 'coderockz_woo_delivery_add_custom_field' ), 10 );
		$this->remove_action_for_class( 'woocommerce_after_order_notes', array( 'Coderockz_Woo_Delivery_Public', 'coderockz_woo_delivery_add_custom_field' ), 10 );
		$this->remove_action_for_class( 'woocommerce_review_order_before_payment', array( 'Coderockz_Woo_Delivery_Public', 'coderockz_woo_delivery_add_custom_field' ), 10 );
		$this->remove_action_for_class( 'woocommerce_checkout_before_order_review_heading', array( 'Coderockz_Woo_Delivery_Public', 'coderockz_woo_delivery_add_custom_field' ), 10 );

		// TODO: CHECK WHY IT WAS REMOVED
		// // Get delivery date value from session
		// add_filter( 'woocommerce_checkout_get_value', array( $this, 'change_default_field_values_from_session' ), 200, 2 );

		// Skip optional fields
		add_filter( 'fc_hide_optional_fields_skip_by_class', array( $this, 'add_optional_fields_skip_classes' ), 10 );

		// Change delivery date field args
		add_filter( 'woocommerce_form_field_args', array( $this, 'change_delivery_date_field_args' ), 10, 3 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Substep
		$this->maybe_register_substep_delivery_date();
	}

	/**
	 * Maybe register the delivery date substep.
	 */
	public function maybe_register_substep_delivery_date() {
		// Bail if plugin classes and functions are not available
		if ( ! class_exists( 'Coderockz_Woo_Delivery' ) || ! class_exists( 'Coderockz_Woo_Delivery_Public' ) ) { return; }

		// Get settings
		$delivery_option_settings = FluidCheckout_Settings::instance()->get_option( 'coderockz_woo_delivery_option_delivery_settings' );
		$delivery_date_settings = FluidCheckout_Settings::instance()->get_option( 'coderockz_woo_delivery_date_settings' );
		$pickup_date_settings = FluidCheckout_Settings::instance()->get_option( 'coderockz_woo_delivery_pickup_date_settings' );

		// Bail if none of the delivery options are saved
		if (
			( ! is_array( $delivery_option_settings ) || true !== $delivery_option_settings[ 'enable_option_time_pickup' ] )
			&& ! is_array( $delivery_date_settings )
			&& ! is_array( $pickup_date_settings )
		) { return; }

		// Define substep title
		$substep_title = __( 'Delivery Options', 'fluid-checkout' );

		// Both delivery and pickup enabled
		if ( is_array( $delivery_date_settings ) && is_array( $pickup_date_settings ) && true === $delivery_date_settings[ 'enable_delivery_date' ] && true === $pickup_date_settings[ 'enable_pickup_date' ] ) {
			$substep_title = __( 'Delivery or Pickup', 'fluid-checkout' );
		}
		// Only delivery enabled
		else if ( is_array( $delivery_date_settings ) && true === $delivery_date_settings[ 'enable_delivery_date' ] ) {
			$substep_title = __( 'Delivery Date', 'fluid-checkout' );
		}
		// Only pickup enabled
		else if ( is_array( $pickup_date_settings ) && true === $pickup_date_settings[ 'enable_pickup_date' ] ) {
			$substep_title = __( 'Pickup Date', 'fluid-checkout' );
		}

		// Filter substep title
		$substep_title = apply_filters( 'fc_woodelivery_substep_title', $substep_title );

		// Get substep position args
		$substep_position_args = $this->get_substep_position_args();

		// Get substep position
		$step_id = $substep_position_args[ 'step_id' ];
		$substep_priority = $substep_position_args[ 'priority' ];
		$substep_id = 'coderockz_delivery_date';

		// Register substep
		FluidCheckout_Steps::instance()->register_checkout_substep( $step_id, array(
			'substep_id' => $substep_id,
			'substep_title' => $substep_title,
			'priority' => $substep_priority,
			'render_fields_callback' => array( $this, 'output_substep_fields_delivery_options' ),
			'render_review_text_callback' => array( $this, 'output_substep_text_delivery_options' ),
			'is_complete_callback' => array( $this, 'is_substep_complete_delivery_date' ),
		) );

		// Add substep review text fragment
		add_filter( 'fc_substep_delivery_date_text_lines', array( $this, 'add_substep_text_lines_delivery_date' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_delivery_date_text_fragment' ), 10 );
	}

	/**
	 * Get the substep hook and priority values for each position which is displayed as a substep.
	 */
	public function get_substep_position_options() {
		// Only positions which are substeps are defined here.
		$substep_position_priority = array(
			'before_billing'        => array( 'step_id' => 'billing', 'priority' => 9 ),
			'after_billing'         => array( 'step_id' => 'billing', 'priority' => 11 ),
			'before_shipping'       => array( 'step_id' => 'shipping', 'priority' => 9 ),
			'after_shipping'        => array( 'step_id' => 'shipping', 'priority' => 21 ),
			'before_notes'          => array( 'step_id' => 'shipping', 'priority' => 99 ),
			'after_notes'           => array( 'step_id' => 'shipping', 'priority' => 101 ),
			'before_payment'        => array( 'step_id' => 'payment', 'priority' => 89 ), // Intentionally same as for `before_your_order`
			'before_your_order'     => array( 'step_id' => 'payment', 'priority' => 89 ),
		);

		return $substep_position_priority;
	}

	/**
	 * Get the substep position args for the delivery date substep.
	 */
	public function get_substep_position_args() {
		// Get the plugins settings
		$woodelivery_settings = FluidCheckout_Settings::instance()->get_option( 'coderockz_woo_delivery_other_settings' );

		// Get substep position options
		$substep_position_options = $this->get_substep_position_options();
		
		// Get selected position for delivery date
		// or fallback to default position.
		$selected_position = is_array( $woodelivery_settings ) && isset( $woodelivery_settings['field_position'] ) && $woodelivery_settings['field_position'] != '' ? $woodelivery_settings['field_position'] : 'after_notes';
		if ( ! array_key_exists( $selected_position, $substep_position_options ) ) {
			$selected_position = 'after_notes';
		}

		// Bail if selected position is not found
		if ( ! array_key_exists( $selected_position, $substep_position_options ) ) { return false; }

		return $substep_position_options[ $selected_position ];
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Checkout scripts
		wp_register_script( 'fc-checkout-woo-delivery', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/woo-delivery/checkout-woo-delivery' ), array( 'jquery', 'selectWoo', 'flatpickr_js' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-checkout-woo-delivery', 'window.addEventListener("load",function(){CheckoutWooDelivery.init();})' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-checkout-woo-delivery' );
	}

	/**
	 * Maybe enqueue assets.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not at checkout
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		$this->enqueue_assets();
	}



	/**
	 * Maybe set session data for the datepicker fields.
	 *
	 * @param  array  $posted_data   Post data for all checkout fields.
	 */
	public function maybe_set_datepicker_fields_session_values( $posted_data ) {
		$fields = array(
			'coderockz_woo_delivery_delivery_selection_box',
			'coderockz_woo_delivery_date_field',
			'coderockz_woo_delivery_time_field',
			'coderockz_woo_delivery_pickup_date_field',
			'coderockz_woo_delivery_pickup_time_field'
		);

		foreach ( $fields as $field ) {
			if ( array_key_exists( $field, $posted_data ) && ! empty( $posted_data[ $field ] ) ) {
				FluidCheckout_Steps::instance()->set_checkout_field_value_to_session( $field, $posted_data[ $field ] );
			}
		}

		return $posted_data;
	}



	/**
	 * Output custom hidden fields.
	 */
	public function output_custom_hidden_fields() {
		// Get field values from session
		$delivery_date = '';
		$pickup_date = '';
		$delivery_date_session_value = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'coderockz_woo_delivery_date_field' );
		$pickup_date_session_value = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'coderockz_woo_delivery_pickup_date_field' );
		$delivery_time = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'coderockz_woo_delivery_time_field' );
		$pickup_time = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'coderockz_woo_delivery_pickup_time_field' );
		$delivery_type = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'coderockz_woo_delivery_delivery_selection_box' );

		// Convert date values to Y-m-d format
		if ( $delivery_date_session_value ) {
			$delivery_date = date( 'Y-m-d', strtotime( $delivery_date_session_value ) );
		}
		if ( $pickup_date_session_value ) {
			$pickup_date = date( 'Y-m-d', strtotime( $pickup_date_session_value ) );
		}

		// Output hidden fields
		echo '<div id="woo-delivery-custom_hidden_fields" class="form-row fc-no-validation-icon woo-delivery-custom_hidden_fields">';
		echo '<input type="hidden" id="fc_coderockz_woo_delivery_type" name="fc_coderockz_woo_delivery_type" value="' . esc_attr( $delivery_type ) . '">';
		echo '<input type="hidden" id="fc_coderockz_woo_delivery_date" name="fc_coderockz_woo_delivery_date" value="' . esc_attr( $delivery_date ) . '">';
		echo '<input type="hidden" id="fc_coderockz_woo_delivery_time" name="fc_coderockz_woo_delivery_time" value="' . esc_attr( $delivery_time ) . '">';
		echo '<input type="hidden" id="fc_coderockz_woo_pickup_date" name="fc_coderockz_woo_pickup_date" value="' . esc_attr( $pickup_date ) . '">';
		echo '<input type="hidden" id="fc_coderockz_woo_pickup_time" name="fc_coderockz_woo_pickup_time" value="' . esc_attr( $pickup_time ) . '">';
		echo '</div>';
	}



	/**
	 * Determines if all required data for the delivery date substep has been provided.
	 *
	 * @return  boolean  `true` if the user has provided all the required data for this substep, `false` otherwise. Defaults to `true`.
	 */
	public function is_substep_complete_delivery_date() {
		// Initialize variables
		$substep_id = 'coderockz_delivery_date';
		$is_substep_complete = true;

		// Get settings
		$delivery_option_settings = FluidCheckout_Settings::instance()->get_option( 'coderockz_woo_delivery_option_delivery_settings' );
		$delivery_date_settings = FluidCheckout_Settings::instance()->get_option( 'coderockz_woo_delivery_date_settings' );
		$delivery_time_settings = FluidCheckout_Settings::instance()->get_option( 'coderockz_woo_delivery_time_settings' );
		$pickup_date_settings = FluidCheckout_Settings::instance()->get_option( 'coderockz_woo_delivery_pickup_date_settings' );
		$pickup_time_settings = FluidCheckout_Settings::instance()->get_option( 'coderockz_woo_delivery_pickup_settings' );

		// Get field values
		$order_type = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'coderockz_woo_delivery_delivery_selection_box' );
		$delivery_date = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'coderockz_woo_delivery_date_field' );
		$delivery_time = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'coderockz_woo_delivery_time_field' );
		$pickup_date = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'coderockz_woo_delivery_pickup_date_field' );
		$pickup_time = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'coderockz_woo_delivery_pickup_time_field' );

		// Check order type
		// Note that `enable_option_time_pickup` means that the user can choose between delivery and pickup
		if ( is_array( $delivery_option_settings ) && array_key_exists( 'enable_option_time_pickup', $delivery_option_settings ) && true === $delivery_option_settings[ 'enable_option_time_pickup' ] && ! in_array( $order_type, $this->get_allowed_order_type_values() ) ) {
			$is_step_complete = false;
		}

		// Always set set as incomplete if option to select between delivery and pickup is enabled
		// This is a limitation of the since it does not allow to set a default value for that option
		if ( is_array( $delivery_option_settings ) && array_key_exists( 'enable_option_time_pickup', $delivery_option_settings ) && true === $delivery_option_settings[ 'enable_option_time_pickup' ] ) {
			$is_substep_complete = false;
		}

		// Check delivery date is enabled and mandatory
		if ( is_array( $delivery_date_settings ) && array_key_exists( 'enable_delivery_date', $delivery_date_settings ) && true === $delivery_date_settings[ 'enable_delivery_date' ] && true === $delivery_date_settings[ 'delivery_date_mandatory' ] && empty( $delivery_date ) ) {
			$is_substep_complete = false;
		}

		// Check delivery time is enabled and mandatory
		if ( is_array( $delivery_time_settings ) && array_key_exists( 'enable_delivery_time', $delivery_date_settings ) && true === $delivery_time_settings[ 'enable_delivery_time' ] && true === $delivery_time_settings[ 'delivery_time_mandatory' ] && empty( $delivery_time ) ) {
			$is_substep_complete = false;
		}

		// Check pickup date is enabled and mandatory
		if ( is_array( $pickup_date_settings ) && array_key_exists( 'enable_pickup_date', $delivery_date_settings ) && true === $pickup_date_settings[ 'enable_pickup_date' ] && true === $pickup_date_settings[ 'pickup_date_mandatory' ] && empty( $pickup_date ) ) {
			$is_substep_complete = false;
		}

		// Check pickup time is enabled and mandatory
		if ( is_array( $pickup_time_settings ) && array_key_exists( 'enable_pickup_time', $delivery_date_settings ) && true === $pickup_time_settings[ 'enable_pickup_time' ] && true === $pickup_time_settings[ 'pickup_time_mandatory' ] && empty( $pickup_time ) ) {
			$is_substep_complete = false;
		}

		return apply_filters( 'fc_is_substep_complete_' . $substep_id, $is_substep_complete );
	}



	/**
	 * Return the values allowed for the order type field.
	 */
	public function get_allowed_order_type_values() {
		return array( 'delivery', 'pickup' );
	}



	/**
	 * Add the delivery date substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_delivery_date( $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Get settings
		$delivery_option_settings = FluidCheckout_Settings::instance()->get_option( 'coderockz_woo_delivery_option_delivery_settings' );
		$delivery_date_settings = FluidCheckout_Settings::instance()->get_option( 'coderockz_woo_delivery_date_settings' );
		$delivery_time_settings = FluidCheckout_Settings::instance()->get_option( 'coderockz_woo_delivery_time_settings' );
		$pickup_date_settings = FluidCheckout_Settings::instance()->get_option( 'coderockz_woo_delivery_pickup_date_settings' );
		$pickup_time_settings = FluidCheckout_Settings::instance()->get_option( 'coderockz_woo_delivery_pickup_settings' );

		// Get field values
		$order_type = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'coderockz_woo_delivery_delivery_selection_box' );
		$delivery_date = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'coderockz_woo_delivery_date_field' );
		$delivery_time = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'coderockz_woo_delivery_time_field' );
		$pickup_date = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'coderockz_woo_delivery_pickup_date_field' );
		$pickup_time = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'coderockz_woo_delivery_pickup_time_field' );

		// Get field labels
		$delivery_field_label = ( isset( $delivery_option_settings[ 'delivery_label' ] ) && ! empty( $delivery_option_settings[ 'delivery_label' ] ) ) ? stripslashes( $delivery_option_settings[ 'delivery_label' ] ) : __( 'Delivery', 'woo-delivery' );
		$pickup_field_label = ( isset( $delivery_option_settings[ 'pickup_label' ] ) && ! empty( $delivery_option_settings[ 'pickup_label' ] ) ) ? stripslashes( $delivery_option_settings[ 'pickup_label' ] ) : __( 'Pickup', 'woo-delivery' );

		// Check order type
		// Note that `enable_option_time_pickup` means that the user can choose between delivery and pickup
		$is_allowed_order_type = false;
		if ( is_array( $delivery_option_settings ) && true === $delivery_option_settings[ 'enable_option_time_pickup' ] ) {
			$is_allowed_order_type = in_array( $order_type, $this->get_allowed_order_type_values() );
		}

		// Check delivery or pickup
		if (
			( // Delivery
				( ! is_array( $delivery_option_settings ) || ( is_array( $delivery_option_settings ) && true !== $delivery_option_settings[ 'enable_option_time_pickup' ] ) || 'delivery' === $order_type ) // Order type is delivery or not enabled
				&& (
					( is_array( $delivery_date_settings ) && true === $delivery_date_settings[ 'enable_delivery_date' ] && ! empty( $delivery_date ) ) // Delivery date is enabled and has a value
					|| ( is_array( $delivery_time_settings ) && true === $delivery_time_settings[ 'enable_delivery_time' ] && ! empty( $delivery_time ) ) // Delivery time is enabled and has a value
				)
			)
			|| ( // Pickup
				( ! is_array( $delivery_option_settings ) || ( is_array( $delivery_option_settings ) && true !== $delivery_option_settings[ 'enable_option_time_pickup' ] ) || 'pickup' === $order_type ) // Order type is pickup or not enabled
				&& (
					( is_array( $pickup_date_settings ) && true === $pickup_date_settings[ 'enable_pickup_date' ] && ! empty( $pickup_date ) ) // Pickup date is enabled and has a value
					|| ( is_array( $pickup_time_settings ) && true === $pickup_time_settings[ 'enable_pickup_time' ] && ! empty( $pickup_time ) ) // Pickup time is enabled and has a value
				)
			)
		) {
			// Delivery
			if (
				( ! is_array( $delivery_option_settings ) || ( is_array( $delivery_option_settings ) && true !== $delivery_option_settings[ 'enable_option_time_pickup' ] ) || 'delivery' === $order_type ) // Order type is delivery or not enabled
				&& (
					( is_array( $delivery_date_settings ) && true === $delivery_date_settings[ 'enable_delivery_date' ] && ! empty( $delivery_date ) ) // Delivery date is enabled and has a value
					|| ( is_array( $delivery_time_settings ) && true === $delivery_time_settings[ 'enable_delivery_time' ] && ! empty( $delivery_time ) ) // Delivery time is enabled and has a value
				)
			) {
				// Delivery label
				$review_text_lines[] = '<strong>' . esc_html( $delivery_field_label ) . '</strong>';

				// Delivery date is enabled and has a value
				if ( ( is_array( $delivery_date_settings ) && true === $delivery_date_settings[ 'enable_delivery_date' ] && ! empty( $delivery_date ) ) ) {
					$review_text_lines[] = $delivery_date;
				}

				// Delivery time is enabled and has a value
				if ( ( is_array( $delivery_time_settings ) && true === $delivery_time_settings[ 'enable_delivery_time' ] && ! empty( $delivery_time ) ) ) {
					$review_text_lines[] = $delivery_time;
				}
			}

			// Pickup
			if (
				( ! is_array( $delivery_option_settings ) || ( is_array( $delivery_option_settings ) && true !== $delivery_option_settings[ 'enable_option_time_pickup' ] ) || 'pickup' === $order_type ) // Order type is pickup or not enabled
				&& (
					( is_array( $pickup_date_settings ) && true === $pickup_date_settings[ 'enable_pickup_date' ] && ! empty( $pickup_date ) ) // Pickup date is enabled and has a value
					|| ( is_array( $pickup_time_settings ) && true === $pickup_time_settings[ 'enable_pickup_time' ] && ! empty( $pickup_time ) ) // Pickup time is enabled and has a value
				)
			) {
				// Pickup label
				$review_text_lines[] = '<strong>' . esc_html( $pickup_field_label ) . '</strong>';

				// Pickup date is enabled and has a value
				if ( ( is_array( $pickup_date_settings ) && true === $pickup_date_settings[ 'enable_pickup_date' ] && ! empty( $pickup_date ) ) ) {
					$review_text_lines[] = $pickup_date;
				}

				// Pickup time is enabled and has a value
				if ( ( is_array( $pickup_time_settings ) && true === $pickup_time_settings[ 'enable_pickup_time' ] && ! empty( $pickup_time ) ) ) {
					$review_text_lines[] = $pickup_time;
				}
			}

		}
		// Only delivery or pickup option (without date or time)
		else if ( is_array( $delivery_option_settings ) && true === $delivery_option_settings[ 'enable_option_time_pickup' ] && $is_allowed_order_type ) {
			// Delivery
			if ( 'delivery' === $order_type ) {
				$review_text_lines[] = esc_html( $delivery_field_label );
			}
			// Pickup
			else if ( 'pickup' === $order_type ) {
				$review_text_lines[] = esc_html( $pickup_field_label );
			}
		}
		// "No delivery or pickup date" notice.
		else {
			$review_text_lines[] = apply_filters( 'fc_woodelivery_no_delivery_options_order_review_notice', _x( 'None.', 'Notice for no delivery or pickup options provided', 'fluid-checkout' ) );
		}

		return $review_text_lines;
	}

	/**
	 * Get delivery date substep review text.
	 */
	public function get_substep_text_delivery_date() {
		return FluidCheckout_Steps::instance()->get_substep_review_text( 'delivery_date' );
	}

	/**
	 * Add delivery date substep review text as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_delivery_date_text_fragment( $fragments ) {
		$html = $this->get_substep_text_delivery_date();
		$fragments['.fc-step__substep-text-content--delivery_date'] = $html;
		return $fragments;
	}

	/**
	 * Output the substep fields for delivery options.
	 */
	public function output_substep_fields_delivery_options() {
		// Get Woo Delivery instances
		$woodelivery_main = new Coderockz_Woo_Delivery();
		$woodelivery_public = new Coderockz_Woo_Delivery_Public( $woodelivery_main->get_plugin_name(), $woodelivery_main->get_version() );

		// Output fields from the plugin
		$woodelivery_public->coderockz_woo_delivery_add_custom_field();

		// Output custom hidden fields
		$this->output_custom_hidden_fields();
	}

	/**
	 * Output delivery date substep review text.
	 */
	public function output_substep_text_delivery_options() {
		echo $this->get_substep_text_delivery_date();
	}



	/**
	 * Change default delivery date field value, getting it from the persisted fields session.
	 *
	 * @param   mixed    $value   Value of the field.
	 * @param   string   $input   Checkout field key (ie. order_comments ).
	 */
	public function change_default_field_values_from_session( $value, $input ) {
		// Define target fields
		$target_field_ids = array(
			'coderockz_woo_delivery_delivery_selection_box',
			'coderockz_woo_delivery_date_field',
			'coderockz_woo_delivery_time_field',
			'coderockz_woo_delivery_pickup_date_field', 'coderockz_woo_delivery_pickup_time_field',
		);

		// Bail if not a target field
		if ( ! in_array( $input, $target_field_ids ) ) { return $value; }

		// Get field value from session
		$field_session_value = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( $input );

		// Maybe return field value from session
		$date_field_ids = array( 'coderockz_woo_delivery_date_field', 'coderockz_woo_delivery_pickup_date_field' );
		if ( null !== $field_session_value && in_array( $input, $date_field_ids ) ) {
			$date_converted_value = date( 'Y-m-d', strtotime( $field_session_value ) );
			return $date_converted_value;
		}

		return $value;
	}



	/**
	 * Add fields to the optional fields skip class list.
	 */
	public function add_optional_fields_skip_classes( $skip_classes ) {
		$skip_classes = array_merge( $skip_classes, array( 'coderockz_woo_delivery_date_field', 'coderockz_woo_delivery_pickup_date_field' ) );
		return $skip_classes;
	}



	public function change_delivery_date_field_args( $args, $key, $value ) {
		// Bail if not a target field
		$allowed_field_ids = array( 'coderockz_woo_delivery_date_field', 'coderockz_woo_delivery_pickup_date_field' );
		if ( ! in_array( $key, $allowed_field_ids ) ) { return $args; }

		// Maybe add default date attribute
		if ( ! empty( WC()->checkout->get_value( $key ) ) ) {
			$args['custom_attributes']['data-default_date'] = WC()->checkout->get_value( $key );
		}

		return $args;
	}

}

FluidCheckout_WooDelivery::instance();
