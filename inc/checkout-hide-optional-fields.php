<?php
defined( 'ABSPATH' ) || exit;

/**
 * Customizations to the checkout optional fields.
 */
class FluidCheckout_CheckoutHideOptionalFields extends FluidCheckout {

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
		// Bail if not on front end
		if ( is_admin() ) { return; }

		// WooCommerce fields output
		add_filter( 'woocommerce_form_field', array( $this, 'add_optional_form_field_link_button' ), 100, 4 );
	}



	/**
	 * Return Checkout Steps class instance.
	 */
	public function checkout_steps() {
		return FluidCheckout_Steps::instance();
	}


	/**
	 * Get the list of field ids to skip hidding when hide optional fields is enabled.
	 *
	 * @return  Array  List of field ids to skip hidding.
	 */
	public function get_hide_optional_fields_skip_list() {
		// Always skip these fields
		$skip_list = array( 'state', 'billing_state', 'shipping_state' );

		// Maybe skip "address line 2" fields
		if ( get_option( 'fc_hide_optional_fields_skip_address_2', 'no' ) === 'yes' ) {
			$skip_list[] = 'address_2';
			$skip_list[] = 'shipping_address_2';
			$skip_list[] = 'billing_address_2';
		}

		return apply_filters( 'fc_hide_optional_fields_skip_list', $skip_list );
	}



	/**
	 * Get the checkout fields args.
	 *
	 * @param   string  $field  Field html markup to be changed.
	 * @param   string  $key    Field key.
	 * @param   array   $args   Field args.
	 * @param   mixed   $value  Value of the field. Defaults to `null`.
	 */
	public function add_optional_form_field_link_button( $field, $key, $args, $value ) {
		// Bail if field is required
		if ( array_key_exists( 'required', $args ) && $args[ 'required' ] == true ) { return $field; }

		// Bail if field is not empty
		if ( ! empty( $value ) ) { return $field; }

		// Bail if field has already been hidden
		// Needed for compatibility with plugins that call `woocommerce_form_field` multiple times for the same field
		if ( false !== strpos( $field, 'id="fc-expansible-form-section__toggle--' . $key ) ) { return $field; }

		// Maybe skip optional field by type
		if ( in_array( $args[ 'type' ], apply_filters( 'fc_hide_optional_fields_skip_types', array( 'state', 'country', 'select', 'checkbox', 'radio', 'hidden' ) ) ) ) { return $field; }

		// Maybe skip optional field by class
		$skip_field_container_classes = apply_filters( 'fc_hide_optional_fields_skip_by_class', array( 'fc-skip-hide-optional-field' ) );
		foreach ( $skip_field_container_classes as $skip_class ) {
			foreach ( $args[ 'class' ] as $field_class ) {
				if ( -1 < strpos( $field_class, $skip_class ) ) { return $field; }
			}
		}

		// Maybe skip optional field by id
		if ( in_array( $key, $this->get_hide_optional_fields_skip_list() ) ) { return $field; }

		// Set attribute `data-autofocus` to focus on the optional field when expanding the section
		$field = str_replace( 'name="'. esc_attr( $key ) .'"', 'name="'. esc_attr( $key ) .'" data-autofocus', $field );

		// Move container classes to expansible block
		$container_class_esc = esc_attr( implode( ' ', $args['class'] ) );
		$expansible_section_args = array(
			'section_attributes' => array(
				'class' => 'form-row ' . $container_class_esc,
			),
		);

		// Remove the container class from the field element
		$field = str_replace( 'form-row '. $container_class_esc, 'form-row ', $field );

		// Start buffer
		ob_start();

		// Add expansible block markup for the field
		$form_field_label = get_option( 'fc_optional_fields_link_label_lowercase', 'yes' ) === 'yes' ? strtolower( $args['label'] ) : $args['label'];
		/* translators: %s: Form field label */
		$toggle_label = apply_filters( 'fc_expansible_section_toggle_label_'.$key, sprintf( __( 'Add %s', 'fluid-checkout' ), $form_field_label ) );
		$this->checkout_steps()->output_expansible_form_section_start_tag( $key, $toggle_label, $expansible_section_args );
		echo $field; // WPCS: XSS ok.
		$this->checkout_steps()->output_expansible_form_section_end_tag();

		// Get value and clear buffer
		$field = ob_get_clean();

		return $field;
	}

}

FluidCheckout_CheckoutHideOptionalFields::instance();
