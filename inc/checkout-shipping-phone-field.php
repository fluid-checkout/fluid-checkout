<?php
defined( 'ABSPATH' ) || exit;

/**
 * Add shipping phone field to the checkout page.
 */
class FluidCheckout_CheckoutShippingPhoneField extends FluidCheckout {

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
		// Add shipping phone field
		add_filter( 'woocommerce_shipping_fields', array( $this, 'add_shipping_phone_field' ), 5 );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_order_meta_with_shipping_phone' ), 10 );
		
		// Admin fields
		if ( is_admin() ) {
			add_filter( 'woocommerce_admin_shipping_fields', array( $this, 'add_shipping_phone_to_admin_screen' ), 10 );
			add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'output_order_formatted_shipping_address_with_phone' ), 1, 2 );
		}

		// Change shipping field args
		add_filter( 'woocommerce_shipping_fields', array( $this, 'maybe_set_shipping_phone_required' ), 100 );
		add_filter( 'woocommerce_shipping_fields' , array( $this, 'change_shipping_company_field_args' ), 100 );

		// Move shipping phone to contact step
		if ( 'contact' === get_option( 'fc_shipping_phone_field_position', 'shipping_address' ) ) {
			// Add shipping phone to contact fields
			add_filter( 'fc_checkout_contact_step_field_ids', array( $this, 'add_shipping_phone_field_to_contact_fields' ), 10 );

			// Remove phone field from shipping address data
			add_filter( 'fc_shipping_substep_text_address_data', array( $this, 'remove_phone_address_data' ), 10 );
		}
	}



	/**
	 * Get shipping phone field for address forms.
	 *
	 * @return  array $args Arguments for adding shipping phone field.
	 */
	public function get_shipping_phone_field() {
		return apply_filters( 'fc_shipping_phone_field_args', array(
			'label'        => __( 'Shipping phone', 'fluid-checkout' ),
			'description'  => __( 'Only used for shipping-related questions.', 'fluid-checkout' ),
			'required'     => get_option( 'fc_shipping_phone_field_visibility', 'no' ) === 'required',
			'validate'     => array( 'phone' ),
			'class'        => array( 'form-row-first' ),
			'priority'     => 25,
			'autocomplete' => 'shipping tel',
			'type'         => 'tel',
			'clear'        => true
		) );
	}



	/**
	 * Change shipping phone `required` argument when billing phone field is required.
	 *
	 * @param   array  $field_args  Contains shipping field arguments.
	 */
	public function maybe_set_shipping_phone_required( $field_args ) {
		// Bail if shipping phone not present, or billing phone field not required
		if ( ! array_key_exists( 'shipping_phone', $field_args ) || get_option( 'woocommerce_checkout_phone_field', 'required' ) !== 'required' ) { return $field_args; }

		// Set shipping phone as required
		$field_args['shipping_phone']['required'] = true;

		return $field_args;
	}



	/**
	 * Change shipping company field arguments to accomodate the shipping phone field.
	 *
	 * @param   array  $field_args  Contains shipping field arguments.
	 */
	public function change_shipping_company_field_args( $field_args ) {
		// Bail if not hidding optional fields behind a link button
		if ( get_option( 'fc_enable_checkout_hide_optional_fields', 'yes' ) === 'yes' && array_key_exists( 'shipping_phone', $field_args ) && array_key_exists( 'required', $field_args['shipping_phone'] ) && $field_args['shipping_phone']['required'] != true ) { return $field_args; }

		if ( array_key_exists( 'shipping_company', $field_args ) ) {
			$field_args['shipping_company']['class'] = array( 'form-row-last' );
		}

		return $field_args;
	}



	/**
	 * Add shipping phone field to edit address fields.
	 *
	 * @param   array  $fields  Fields used in checkout.
	 */
	public function add_shipping_phone_field( $fields ) {
		$field_key = 'shipping_phone';
		$fields[ $field_key ] = $this->get_shipping_phone_field();

		// Maybe apply customizations from the Checkout Fields feature
		if ( class_exists( 'FluidCheckout_CheckoutFields' ) ) {
			$new_fields_args = FluidCheckout_CheckoutFields::instance()->get_checkout_field_args();
			
			// Check if field args exists
			if ( array_key_exists( $field_key, $new_fields_args ) ) {
				$fields[ $field_key ] = FluidCheckout_CheckoutFields::instance()->merge_form_field_args( $fields[ $field_key ], $new_fields_args[ $field_key ] );
			}
		}

		return $fields;
	}



	/**
	 * Update the order meta with shipping phone.
	 *
	 * @param   int  $order_id  Order ID.
	 */
	public function update_order_meta_with_shipping_phone( $order_id ) {
		$shipping_phone = isset( $_POST['shipping_phone'] ) ? sanitize_text_field( $_POST['shipping_phone'] ) : '';
		update_post_meta( $order_id, '_shipping_phone', $shipping_phone );
	}

	/**
	 * Add the shipping phone field to admin screen.
	 */
	public function add_shipping_phone_to_admin_screen( $shipping_fields ) {
		$shipping_fields[ 'phone' ] = array(
			'label'         => __( 'Phone', 'woocommerce' ),
			'wrapper_class' => 'form-field-wide',
			'show'          => false,
		);

		return $shipping_fields;
	}



	/**
	 * Output shipping phone to the address details on order view.
	 *
	 * @param   array  $address Contains address fields.
	 * @param   WC_Order   $order   The Order object.
	 */
	public function output_order_formatted_shipping_address_with_phone( $address, $order ) {
		$shipping_phone = get_post_meta( $order->get_id(), '_shipping_phone', true );
		if ( ! empty( $shipping_phone ) ) { $address['shipping_phone'] = $shipping_phone; }
		return $address;
	}



	/**
	 * Remove phone from address data.
	 *
	 * @param   array  $html  HTML for the substep text.
	 */
	public function remove_phone_address_data( $address_data ) {
		unset( $address_data[ 'phone' ] );
		return $address_data;
	}



	/**
	 * Add the shipping phone to the list of fields to display on the contact step.
	 *
	 * @param   array  $display_fields  List of fields to display on the contact step.
	 */
	public function add_shipping_phone_field_to_contact_fields( $display_fields ) {
		$display_fields[] = 'shipping_phone';
		return $display_fields;
	}

}

FluidCheckout_CheckoutShippingPhoneField::instance();
