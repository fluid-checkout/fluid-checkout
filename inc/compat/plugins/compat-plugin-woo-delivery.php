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
	}



	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		if ( class_exists( 'Coderockz_Woo_Delivery' ) && class_exists( 'Coderockz_Woo_Delivery_Public' ) ) {
			// Remove plugin hooks
			$this->remove_action_for_class( 'woocommerce_checkout_billing', array( 'Coderockz_Woo_Delivery_Public', 'coderockz_woo_delivery_add_custom_field' ), 10 );
			$this->remove_action_for_class( 'woocommerce_after_checkout_billing_form', array( 'Coderockz_Woo_Delivery_Public', 'coderockz_woo_delivery_add_custom_field' ), 10 );
			$this->remove_action_for_class( 'woocommerce_checkout_shipping', array( 'Coderockz_Woo_Delivery_Public', 'coderockz_woo_delivery_add_custom_field' ), 10 );
			$this->remove_action_for_class( 'woocommerce_after_checkout_shipping_form', array( 'Coderockz_Woo_Delivery_Public', 'coderockz_woo_delivery_add_custom_field' ), 10 );
			$this->remove_action_for_class( 'woocommerce_before_order_notes', array( 'Coderockz_Woo_Delivery_Public', 'coderockz_woo_delivery_add_custom_field' ), 10 );
			$this->remove_action_for_class( 'woocommerce_after_order_notes', array( 'Coderockz_Woo_Delivery_Public', 'coderockz_woo_delivery_add_custom_field' ), 10 );
			$this->remove_action_for_class( 'woocommerce_review_order_before_payment', array( 'Coderockz_Woo_Delivery_Public', 'coderockz_woo_delivery_add_custom_field' ), 10 );
			$this->remove_action_for_class( 'woocommerce_checkout_before_order_review_heading', array( 'Coderockz_Woo_Delivery_Public', 'coderockz_woo_delivery_add_custom_field' ), 10 );

			// Define substep hook and priority for each position
			$substep_position_priority = array(
				'before_billing' => array( 'fc_output_step_billing', 9 ),
				'after_billing' => array( 'fc_output_step_billing', 11 ),
				'before_shipping' => array( 'fc_output_step_shipping', 9 ),
				'after_shipping' => array( 'fc_output_step_shipping', 21 ),
				'before_notes' => array( 'fc_output_step_shipping', 99 ),
				'after_notes' => array( 'fc_output_step_shipping', 101 ),
				'before_payment' => array( 'fc_output_step_payment', 89 ), // Intentionally same as for `before_your_order`
				'before_your_order' => array( 'fc_output_step_payment', 89 ),
			);

			// Get selected position for delivery date
			$woodelivery_settings = get_option( 'coderockz_woo_delivery_other_settings' );
			$position = is_array( $woodelivery_settings ) && isset( $woodelivery_settings['field_position'] ) && $woodelivery_settings['field_position'] != '' ? $woodelivery_settings['field_position'] : 'after_notes';
			if ( ! array_key_exists( $position, $substep_position_priority ) ) {
				$position = 'after_notes';
			}
			
			// Add delivery date substep at the selected position
			$hook = $substep_position_priority[ $position ][ 0 ];
			$priority = $substep_position_priority[ $position ][ 1 ];
			add_action( $hook, array( $this, 'output_substep_delivery_options' ), $priority );

			// Add substep review text fragment
			add_filter( 'fc_substep_delivery_date_text_lines', array( $this, 'add_substep_text_lines_delivery_date' ), 10 );
			add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_delivery_date_text_fragment' ), 10 );

			// Get delivery date value from session
			add_filter( 'woocommerce_checkout_get_value', array( $this, 'change_default_field_values_from_session' ), 200, 2 );

			// Change delivery date field args
			add_filter( 'woocommerce_form_field_args', array( $this, 'change_delivery_date_field_args' ), 10, 3 );

			// Maybe set step as incomplete
			add_filter( 'fc_is_step_complete_shipping', array( $this, 'maybe_set_step_incomplete' ), 10 );
		}
	}



	/**
	 * Return the values allowed for the order type field.
	 */
	public function get_allowed_order_type_values() {
		return array( 'delivery', 'pickup' );
	}



	/**
	 * Output delivery date substep.
	 *
	 * @param   string  $step_id  Id of the step in which the substep will be rendered.
	 */
	public function output_substep_delivery_options( $step_id ) {
		// Get settings
		$delivery_option_settings = get_option( 'coderockz_woo_delivery_option_delivery_settings' );
		$delivery_date_settings = get_option( 'coderockz_woo_delivery_date_settings' );
		$pickup_date_settings = get_option( 'coderockz_woo_delivery_pickup_date_settings' );

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

		$substep_title = apply_filters( 'fc_woodelivery_substep_title', $substep_title );

		$substep_id = 'coderockz_delivery_date';
		FluidCheckout_Steps::instance()->output_substep_start_tag( $step_id, $substep_id, $substep_title );

		// Get Woo Delivery instances
		$woodelivery_main = new Coderockz_Woo_Delivery();
		$woodelivery_public = new Coderockz_Woo_Delivery_Public( $woodelivery_main->get_plugin_name(), $woodelivery_main->get_version() );

		FluidCheckout_Steps::instance()->output_substep_fields_start_tag( $step_id, $substep_id );
		$woodelivery_public->coderockz_woo_delivery_add_custom_field();
		FluidCheckout_Steps::instance()->output_substep_fields_end_tag();

		// Only output substep text format for multi-step checkout layout
		if ( FluidCheckout_Steps::instance()->is_checkout_layout_multistep() ) {
			FluidCheckout_Steps::instance()->output_substep_text_start_tag( $step_id, $substep_id );
			$this->output_substep_text_delivery_options();
			FluidCheckout_Steps::instance()->output_substep_text_end_tag();
		}

		FluidCheckout_Steps::instance()->output_substep_end_tag( $step_id, $substep_id, $substep_title );
	}



	/**
	 * Set the related checkout step as incomplete depending on the `woo-delivery` plugin settings and its field values.
	 *
	 * @param   bool  $is_step_complete  Whether the step is complete or not.
	 */
	public function maybe_set_step_incomplete( $is_step_complete ) {
		// Bail if step is already incomplete
		if ( ! $is_step_complete ) { return $is_step_complete; }

		// Get settings
		$delivery_option_settings = get_option( 'coderockz_woo_delivery_option_delivery_settings' );
		$delivery_date_settings = get_option( 'coderockz_woo_delivery_date_settings' );
		$delivery_time_settings = get_option( 'coderockz_woo_delivery_time_settings' );
		$pickup_date_settings = get_option( 'coderockz_woo_delivery_pickup_date_settings' );
		$pickup_time_settings = get_option( 'coderockz_woo_delivery_pickup_settings' );

		// Get field values
		$order_type = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'coderockz_woo_delivery_delivery_selection_box' );
		$delivery_date = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'coderockz_woo_delivery_date_field' );
		$delivery_time = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'coderockz_woo_delivery_time_field' );
		$pickup_date = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'coderockz_woo_delivery_pickup_date_field' );
		$pickup_time = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'coderockz_woo_delivery_pickup_time_field' );

		// Check order type
		// Note that `enable_option_time_pickup` means that the user can choose between delivery and pickup
		if ( is_array( $delivery_option_settings ) && true === $delivery_option_settings[ 'enable_option_time_pickup' ] && ! in_array( $order_type, $this->get_allowed_order_type_values() ) ) {
			$is_step_complete = false;
		}

		// Always set set as incomplete if option to select between delivery and pickup is enabled
		// This is a limitation of the since it does not allow to set a default value for that option
		if ( is_array( $delivery_option_settings ) && true === $delivery_option_settings[ 'enable_option_time_pickup' ] ) {
			$is_step_complete = false;
		}

		// Check delivery date is enabled and mandatory
		if ( is_array( $delivery_date_settings ) && true === $delivery_date_settings[ 'enable_delivery_date' ] && true === $delivery_date_settings[ 'delivery_date_mandatory' ] && empty( $delivery_date ) ) {
			$is_step_complete = false;
		}

		// Check delivery time is enabled and mandatory
		if ( is_array( $delivery_time_settings ) && true === $delivery_time_settings[ 'enable_delivery_time' ] && true === $delivery_time_settings[ 'delivery_time_mandatory' ] && empty( $delivery_time ) ) {
			$is_step_complete = false;
		}

		// Check pickup date is enabled and mandatory
		if ( is_array( $pickup_date_settings ) && true === $pickup_date_settings[ 'enable_pickup_date' ] && true === $pickup_date_settings[ 'pickup_date_mandatory' ] && empty( $pickup_date ) ) {
			$is_step_complete = false;
		}

		// Check pickup time is enabled and mandatory
		if ( is_array( $pickup_time_settings ) && true === $pickup_time_settings[ 'enable_pickup_time' ] && true === $pickup_time_settings[ 'pickup_time_mandatory' ] && empty( $pickup_time ) ) {
			$is_step_complete = false;
		}

		return $is_step_complete;
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
		$delivery_option_settings = get_option( 'coderockz_woo_delivery_option_delivery_settings' );
		$delivery_date_settings = get_option( 'coderockz_woo_delivery_date_settings' );
		$delivery_time_settings = get_option( 'coderockz_woo_delivery_time_settings' );
		$pickup_date_settings = get_option( 'coderockz_woo_delivery_pickup_date_settings' );
		$pickup_time_settings = get_option( 'coderockz_woo_delivery_pickup_settings' );

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
		// Bail if not a CodeRockz field
		$allowed_field_ids = array( 'coderockz_woo_delivery_delivery_selection_box', 'coderockz_woo_delivery_date_field', 'coderockz_woo_delivery_time_field', 'coderockz_woo_delivery_pickup_date_field', 'coderockz_woo_delivery_pickup_time_field' );
		if ( ! in_array( $input, $allowed_field_ids ) ) { return $value; }

		// Get field value from session
		$field_session_value = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( $input );

		// Maybe return field value from session
		$date_field_ids = array( 'coderockz_woo_delivery_date_field', 'coderockz_woo_delivery_pickup_date_field' );
		if ( $field_session_value !== null && in_array( $input, $date_field_ids ) ) {
			$date_converted_value = date( 'Y-m-d', strtotime( $field_session_value ) );
			return $date_converted_value;
		}

		return $value;
	}



	/**
	 * Change the fields argments of the delivery date field to set the default date of the date picker component with the value previously selected by the users.
	 * 
	 * @param  mixed   $args   Arguments.
	 * @param  string  $key    Key.
	 * @param  string  $value  (default: null).
	 */
	public function change_delivery_date_field_args( $args, $key, $value ) {
		// Bail if not a CodeRockz field
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
