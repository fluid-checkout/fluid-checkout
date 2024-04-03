<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Flexible Checkout Fields (by WP Desk).
 */
class FluidCheckout_FlexibleCheckoutFields extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct( ) {
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Substep review text
		add_filter( 'fc_substep_text_display_value_inspirecheckbox', array( $this, 'change_custom_checkbox_field_display_value' ), 10, 4 );
	}



	/**
	 * Change substep review text display value for custom checkbox fields.
	 */
	public function change_custom_checkbox_field_display_value( $field_display_value, $field_value, $field_key, $field_args ) {
		// Checkbox display value
		$field_label = isset( $field_args['label'] ) ? $field_args['label'] : '';
		$field_display_value = FluidCheckout_Steps::instance()->get_field_display_value_with_pattern( $field_display_value, $field_key, $field_args, $field_label, apply_filters( "fc_substep_text_display_value_show_field_label_checkbox", true ) );

		return $field_display_value;
	}

}

FluidCheckout_FlexibleCheckoutFields::instance(); 
