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
				'after_shipping' => array( 'fc_output_step_shipping', 11 ),
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
			add_action( $hook, array( $this, 'output_substep_delivery_date' ), $priority );

			// Add substep review text fragment
			add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_delivery_date_text_fragment' ), 10 );

			// Get delivery date value from session
			add_filter( 'woocommerce_checkout_get_value', array( $this, 'change_default_delivery_date_value_from_session' ), 10, 2 );

			// Change delivery date field args
			add_filter( 'woocommerce_form_field_args', array( $this, 'change_delivery_date_field_args' ), 10, 3 );
		}
	}



	/**
	 * Return Checkout Steps class instance.
	 */
	public function checkout_steps() {
		return FluidCheckout_Steps::instance();
	}



	/**
	 * Output delivery date substep.
	 *
	 * @param   string  $step_id  Id of the step in which the substep will be rendered.
	 */
	public function output_substep_delivery_date( $step_id ) {
		$substep_id = 'coderockz_delivery_date';
		$this->checkout_steps()->output_substep_start_tag( $step_id, $substep_id, __( 'Delivery Date', 'fluid-checkout' ) );

		// Get Woo Delivery instances
		$woodelivery_main = new Coderockz_Woo_Delivery();
		$woodelivery_public = new Coderockz_Woo_Delivery_Public( $woodelivery_main->get_plugin_name(), $woodelivery_main->get_version() );

		$this->checkout_steps()->output_substep_fields_start_tag( $step_id, $substep_id );		
		$woodelivery_public->coderockz_woo_delivery_add_custom_field();
		$this->checkout_steps()->output_substep_fields_end_tag();

		// Only output substep text format for multi-step checkout layout
		if ( $this->checkout_steps()->is_checkout_layout_multistep() ) {
			$this->checkout_steps()->output_substep_text_start_tag( $step_id, $substep_id );
			$this->output_substep_text_delivery_date();
			$this->checkout_steps()->output_substep_text_end_tag();
		}

		$this->checkout_steps()->output_substep_end_tag( $step_id, $substep_id );
	}



	/**
	 * Output gift options substep in text format for when the step is completed.
	 */
	public function get_substep_text_delivery_date() {
		// Get delivery date value
		$delivery_date = $this->checkout_steps()->get_checkout_field_value_from_session( 'coderockz_woo_delivery_date_field' );

		$html = '<div class="fc-step__substep-text-content fc-step__substep-text-content--delivery-date">';
		
		// The delivery date value
		if ( $delivery_date !== null && ! empty( $delivery_date ) ) {
			$html .= '<div class="fc-step__substep-text-line">' . esc_html( $delivery_date ) . '</div>';
		}
		// "No delivery date" notice.
		else {
			$html .= '<div class="fc-step__substep-text-line">' . esc_html( apply_filters( 'fc_no_woodelivery_delivery_date_order_review_notice', _x( 'None.', 'Notice for no delivery date provided', 'fluid-checkout' ) ) ) . '</div>';
		}
		
		$html .= '</div>';

		return apply_filters( 'fc_substep_delivery_date_text', $html );
	}

	/**
	 * Add gift options text format as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_delivery_date_text_fragment( $fragments ) {
		$html = $this->get_substep_text_delivery_date();
		$fragments['.fc-step__substep-text-content--delivery-date'] = $html;
		return $fragments;
	}

	/**
	 * Output gift options substep in text format for when the step is completed.
	 */
	public function output_substep_text_delivery_date() {
		echo $this->get_substep_text_delivery_date();
	}



	
	/**
	 * Change default delivery date field value, getting it from the persisted fields session.
	 *
	 * @param   mixed    $value   Value of the field.
	 * @param   string   $input   Checkout field key (ie. order_comments ).
	 */
	public function change_default_delivery_date_value_from_session( $value, $input ) {
		if ( $input !== 'coderockz_woo_delivery_date_field' ) {
			return $value;
		}
		
		// Get field value from session
		$field_session_value = $this->checkout_steps()->get_checkout_field_value_from_session( $input );

		// Maybe return field value from session
		if ( $field_session_value !== null ) {
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
		if ( ! empty( WC()->checkout->get_value('coderockz_woo_delivery_date_field') ) ) {
			$args['custom_attributes']['data-default_date'] = WC()->checkout->get_value('coderockz_woo_delivery_date_field');
		}

		return $args;
	}

}

FluidCheckout_WooDelivery::instance();
