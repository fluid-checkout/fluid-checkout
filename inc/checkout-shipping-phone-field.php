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
		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'add_replacement_field_shipping_phone' ), 10, 2 );
		add_filter( 'woocommerce_localisation_address_formats', array( $this, 'add_shipping_phone_to_formats' ), 10 );
		add_filter( 'fc_substep_shipping_address_text', array( $this, 'add_shipping_phone_to_substep_text_format' ), 10 );
		
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
			// Remove shipping phone field from shipping address
			remove_filter( 'fc_substep_shipping_address_text', array( $this, 'add_shipping_phone_to_substep_text_format' ), 10 );

			// Add shipping phone to contact fields
			add_filter( 'fc_checkout_contact_step_field_ids', array( $this, 'add_shipping_phone_field_to_contact_fields' ), 10 );
			add_filter( 'fc_substep_contact_text', array( $this, 'add_shipping_phone_to_substep_text_format' ), 10 );
		}

		// TODO: Move to a plugin compatibility class
		// Support for plugin "Brazilian Market on WooCommerce"
		add_filter( 'wcbcf_shipping_fields', array( $this, 'add_shipping_phone_field' ), 5 );
		add_filter( 'wcbcf_shipping_fields' , array( $this, 'change_shipping_company_field_args' ), 10 );
	}



	/**
	 * Return Checkout Steps class instance.
	 */
	public function checkout_steps() {
		return FluidCheckout_Steps::instance();
	}

	/**
	 * Return Checkout Fields class instance.
	 */
	public function checkout_fields() {
		return FluidCheckout_CheckoutFields::instance();
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
		$fields['shipping_phone'] = $this->get_shipping_phone_field();

		$field_args = $this->checkout_fields()->get_checkout_field_args();
		foreach( $field_args as $field => $values ) {
			if ( array_key_exists( $field, $fields ) ) { $fields[ $field ] = array_merge( $fields[ $field ], $values ); }
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
	 * Add replacement for shipping phone.
	 *
	 * @param   array  $replacements Contains replacements values.
	 * @param   array  $address Contains address fields.
	 */
	public function add_replacement_field_shipping_phone( $replacements, $address ) {
		$replacements['{shipping_phone}'] = isset( $address['shipping_phone'] ) ? $address['shipping_phone'] : '';
		return $replacements;
	}



	/**
	 * Add replacement for shipping phone to address formats localisation.
	 *
	 * @param   array  $formats  Country address formats.
	 */
	public function add_shipping_phone_to_formats( $formats ) {
		foreach ( $formats as $locale => $format ) {
			$formats[ $locale ] .= "\n{shipping_phone}";
		}
		return $formats;
	}



	/**
	 * Add replacement for shipping phone to address formats localisation.
	 *
	 * @param   array  $formats  Country address formats.
	 */
	public function add_shipping_phone_to_substep_text_format( $html ) {
		$shipping_phone = $this->checkout_steps()->get_checkout_field_value_from_session( 'shipping_phone' );

		// Insert the phone field in the text
		if ( $shipping_phone != null && ! empty( $shipping_phone ) ) {
			$shipping_phone_text = '<div class="fc-step__substep-text-line">' . $shipping_phone . '</div>';
			$last_div_position = strrpos( $html, '</div>' );
			$html = substr_replace( $html, $shipping_phone_text, $last_div_position, 0 );
		}

		return $html;
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
