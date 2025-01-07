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
		// Privacy data managers
		// Shipping phone data export and erasure is handled by the core WooCommerce privacy class.

		// Shipping phone
		$this->shipping_phone_hooks();
	}

	/**
	 * Add or remove shipping phone hooks.
	 */
	public function shipping_phone_hooks() {
		// Bail if feature is not enabled
		if( ! FluidCheckout_Steps::instance()->is_shipping_phone_enabled() ) { return; }

		// Add shipping phone field
		add_filter( 'woocommerce_shipping_fields', array( $this, 'add_shipping_phone_field' ), 5 );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_order_meta_with_shipping_phone' ), 10 );

		// Admin fields
		if ( is_admin() ) {
			add_filter( 'woocommerce_admin_shipping_fields', array( $this, 'add_shipping_phone_to_admin_screen' ), 10 );
			add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'output_order_formatted_shipping_address_with_phone' ), 1, 2 );
		}

		// Change shipping field args
		add_filter( 'woocommerce_billing_fields', array( $this, 'maybe_set_billing_phone_required' ), 100 );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'maybe_set_shipping_phone_required' ), 100 );
		add_filter( 'woocommerce_shipping_fields' , array( $this, 'change_shipping_company_field_args' ), 100 );

		// Move shipping phone to contact step
		if ( 'contact' === FluidCheckout_Settings::instance()->get_option( 'fc_shipping_phone_field_position' ) ) {
			// Add shipping phone to contact fields
			add_filter( 'fc_checkout_contact_step_field_ids', array( $this, 'add_shipping_phone_field_to_contact_fields' ), 10 );
			add_filter( 'woocommerce_shipping_fields', array( $this, 'maybe_change_shipping_phone_field_args_for_contact' ), 10 );

			// Remove phone field from shipping address data
			add_filter( 'fc_shipping_substep_text_address_data', array( FluidCheckout_Steps::instance(), 'maybe_remove_phone_address_data' ), 10 );
		}
	}



	/**
	 * Undo hooks that are run early.
	 * 
	 * Needs to run before hook `wp` priority `100`.
	 * At that priority, changes might have already been added into cache and removing some hooks would not take affect.
	 */
	public function undo_hooks_early() {
		// Add shipping phone field
		remove_filter( 'woocommerce_shipping_fields', array( $this, 'add_shipping_phone_field' ), 5 );
	}

	/**
	 * Undo hooks.
	 */
	public function undo_hooks() {
		// Add shipping phone field
		remove_filter( 'woocommerce_shipping_fields', array( $this, 'add_shipping_phone_field' ), 5 );
		remove_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_order_meta_with_shipping_phone' ), 10 );

		// Admin fields
		if ( is_admin() ) {
			remove_filter( 'woocommerce_admin_shipping_fields', array( $this, 'add_shipping_phone_to_admin_screen' ), 10 );
			remove_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'output_order_formatted_shipping_address_with_phone' ), 1, 2 );
		}

		// Change shipping field args
		remove_filter( 'woocommerce_billing_fields', array( $this, 'maybe_set_billing_phone_required' ), 100 );
		remove_filter( 'woocommerce_shipping_fields', array( $this, 'maybe_set_shipping_phone_required' ), 100 );
		remove_filter( 'woocommerce_shipping_fields' , array( $this, 'change_shipping_company_field_args' ), 100 );
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
			'required'     => 'required' === FluidCheckout_Settings::instance()->get_option( 'fc_shipping_phone_field_visibility' ),
			'validate'     => array( 'phone' ),
			'class'        => array( 'form-row-first' ),
			'priority'     => 25,
			'autocomplete' => 'shipping tel',
			'type'         => 'tel',
			'clear'        => true
		) );
	}


	
	/**
	 * Change billing phone `required` argument when shipping phone field is required.
	 *
	 * @param   array  $billing_fields  The billing fields arguments.
	 */
	public function maybe_set_billing_phone_required( $billing_fields ) {
		// Bail if NOT billing before shipping
		if ( ! FluidCheckout_Steps::instance()->is_billing_address_before_shipping_address() ) { return $billing_fields; }

		// Bail if billing phone not present, or shipping phone field not required
		if ( ! array_key_exists( 'billing_phone', $billing_fields ) || 'required' !== FluidCheckout_Settings::instance()->get_option( 'fc_shipping_phone_field_visibility' ) || 'shipping_address' !== FluidCheckout_Settings::instance()->get_option( 'fc_shipping_phone_field_position' ) ) { return $billing_fields; }

		// Set billing phone as required
		$billing_fields['billing_phone']['required'] = true;

		return $billing_fields;
	}

	/**
	 * Change shipping phone `required` argument when billing phone field is required.
	 *
	 * @param   array  $shipping_fields  The shipping fields arguments.
	 */
	public function maybe_set_shipping_phone_required( $shipping_fields ) {
		// Bail if billing before shipping
		if ( FluidCheckout_Steps::instance()->is_billing_address_before_shipping_address() ) { return $shipping_fields; }

		// Bail if shipping phone not present, or billing phone field not required
		if ( ! array_key_exists( 'shipping_phone', $shipping_fields ) || 'required' !== FluidCheckout_Settings::instance()->get_option( 'woocommerce_checkout_phone_field' ) || 'billing_address' !== FluidCheckout_Settings::instance()->get_option( 'fc_billing_phone_field_position' ) ) { return $shipping_fields; }

		// Set shipping phone as required
		$shipping_fields['shipping_phone']['required'] = true;

		return $shipping_fields;
	}



	/**
	 * Change shipping company field arguments to accomodate the shipping phone field.
	 *
	 * @param   array  $field_args  Contains shipping field arguments.
	 */
	public function change_shipping_company_field_args( $field_args ) {
		// Bail if not hidding optional fields behind a link button
		// Use loose comparison for `required` attribute to allow type casting as some plugins use `1` instead of `true` to set fields as required.
		if ( class_exists( 'FluidCheckout_CheckoutHideOptionalFields' ) && FluidCheckout_CheckoutHideOptionalFields::instance()->is_feature_enabled() && array_key_exists( 'shipping_phone', $field_args ) && array_key_exists( 'required', $field_args['shipping_phone'] ) && true != $field_args['shipping_phone']['required'] ) { return $field_args; }

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
		// Get shipping phone
		$shipping_phone = isset( $_POST['shipping_phone'] ) ? sanitize_text_field( $_POST['shipping_phone'] ) : '';

		// Bail if shipping phone was not provided
		if ( empty( $shipping_phone ) ) { return; }

		// Get the order object
		$order = wc_get_order( $order_id );

		// Bail if order was not found
		if ( ! $order ) { return; }

		// Bail if order does not need shipping address
		if ( ! $order->needs_shipping_address() ) { return; }

		// Update shipping phone value
		if ( is_callable( array( $order, 'set_shipping_phone' ) ) ) {
			$order->set_shipping_phone( $shipping_phone );
		}
		else {
			$order->update_meta_data( '_shipping_phone', $shipping_phone );
		}

		// Update order
		$order->save();
	}

	/**
	 * Add the shipping phone field to admin screen.
	 */
	public function add_shipping_phone_to_admin_screen( $shipping_fields ) {
		$shipping_fields[ 'phone' ] = array(
			'label'         => __( 'Phone', 'woocommerce' ),
			'wrapper_class' => 'form-field-wide',
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
		// Bail if order parameter is invalid
		if ( ! $order instanceof WC_Order ) { return $address; }

		// Get shipping phone
		$shipping_phone = null;
		if ( is_callable( array( $order, 'get_shipping_phone' ) ) ) {
			$shipping_phone = $order->get_shipping_phone();
		}
		else {
			$shipping_phone = $order->get_meta( '_shipping_phone', true );
		}

		// Maybe add the shipping phone to the address data
		if ( ! empty( $shipping_phone ) ) { $address['phone'] = $shipping_phone; }

		return $address;
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

	/**
	 * Maybe change the shipping phone field args when displayed on the contact step.
	 *
	 * @param   array  $fields  The shipping fields.
	 */
	public function maybe_change_shipping_phone_field_args_for_contact( $fields ) {
		// Define variables
		$field_key = 'shipping_phone';

		// Bail if field is not present
		if ( ! array_key_exists( $field_key, $fields ) ) { return $fields; }

		// Bail if field is not set to be displayed on the contact step
		if ( ! in_array( $field_key, FluidCheckout_Steps::instance()->get_contact_step_display_field_ids() ) ) { return $fields; }

		// Change field args
		$fields[ $field_key ][ 'priority' ] = 30;

		// Maybe change the class of the field
		// if the billing field is also present in the contact step
		$fields[ $field_key ] = FluidCheckout_CheckoutFields::instance()->merge_form_field_args( $fields[ $field_key ], array( 'class' => array( 'form-row-last' ) ) );

		return $fields;
	}

}

FluidCheckout_CheckoutShippingPhoneField::instance();
