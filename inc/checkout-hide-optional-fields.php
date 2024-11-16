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
	 * Check whether the feature is enabled or not.
	 */
	public function is_feature_enabled() {
		// Bail if feature is not enabled
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_enable_checkout_hide_optional_fields' ) ) { return false; }

		return true;
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Bail if not on front end
		if ( is_admin() ) { return; }

		// Bail if feature is not enabled
		if( ! $this->is_feature_enabled() ) { return; }

		// WooCommerce fields output
		add_filter( 'woocommerce_form_field', array( $this, 'add_optional_form_field_link_button' ), 100, 4 );
	}



	/**
	 * Undo hooks.
	 */
	public function undo_hooks() {
		// WooCommerce fields output
		remove_filter( 'woocommerce_form_field', array( $this, 'add_optional_form_field_link_button' ), 100, 4 );
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
		if ( 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_hide_optional_fields_skip_address_2' ) ) {
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
		// Use loose comparison for `required` attribute to allow type casting as some plugins use `1` instead of `true` to set fields as required.
		if ( array_key_exists( 'required', $args ) && true == $args[ 'required' ] ) { return $field; }

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

		// Prepare field label for the toggle
		$form_field_label = $args[ 'label' ];

		// Maybe set field label as lowercase
		if ( ( ! array_key_exists( 'optional_expand_link_lowercase', $args ) || false !== $args[ 'optional_expand_link_lowercase' ] ) && 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_optional_fields_link_label_lowercase' ) ) {
			$form_field_label = strtolower( $form_field_label );
		}

		// Get toggle label
		/* translators: %s: Form field label */
		$toggle_label = array_key_exists( 'optional_expand_link_label', $args ) ? sanitize_text_field( $args[ 'optional_expand_link_label' ] ) : sprintf( __( 'Add %s', 'fluid-checkout' ), $form_field_label );

		// Filter to allow developer to change the optional field expansible toggle label
		$toggle_label = apply_filters( "fc_expansible_section_toggle_label_{$key}", $toggle_label );

		// Maybe add "optional" to toggle label
		if ( true === apply_filters( 'fc_expansible_section_toggle_label_add_optional_text', true ) && true === apply_filters( "fc_expansible_section_toggle_label_{$key}_add_optional_text", true ) ) {
			$toggle_label .= ' (' . __( 'optional', 'woocommerce' ) . ')';
		}

		FluidCheckout_Steps::instance()->output_expansible_form_section_start_tag( $key, $toggle_label, $expansible_section_args );
		echo $field; // WPCS: XSS ok.
		FluidCheckout_Steps::instance()->output_expansible_form_section_end_tag();

		// Get value and clear buffer
		$field = ob_get_clean();

		return $field;
	}

}

FluidCheckout_CheckoutHideOptionalFields::instance();
