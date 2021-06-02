<?php
/**
 * Checkout gift options form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/fc/checkout/form-gift-options.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package fluid-checkout
 * @version 1.2.0
 */

defined( 'ABSPATH' ) || exit;

// Maybe separate gift message fields
$gift_message_fields = array_filter( $gift_options_fields, function( $key ) {
	return in_array( $key, FluidCheckout_GiftOptions::instance()->get_gift_message_field_ids() );
}, ARRAY_FILTER_USE_KEY );

// Remove gift message fields from options fields to avoid duplicate fields
$gift_options_fields = array_filter( $gift_options_fields, function( $key ) {
	return ! in_array( $key, FluidCheckout_GiftOptions::instance()->get_gift_message_field_ids() );
}, ARRAY_FILTER_USE_KEY );
?>

<div id="fc-gift-options">

	<?php
	do_action( 'fc_checkout_gift_options_before_fields' );

	// Output gift options fields, except gift message fields
	foreach ( $gift_options_fields as $key => $field ) {
		$field_value = array_key_exists( $key, $gift_options ) ? $gift_options[ $key ] : '';
		woocommerce_form_field( $key, $field, $field_value );
	}
	
	// Get initial state for gift options expansible form section
	$expansible_section_args = array();
	
	// Set expansible form section as initially expanded if has a gift message
	$has_required_message = array_key_exists( '_fc_gift_message', $gift_message_fields ) && array_key_exists( 'required', $gift_message_fields['_fc_gift_message'] ) && ( $gift_message_fields['_fc_gift_message']['required'] === true );
	if ( ! empty( $gift_options[ '_fc_gift_message' ] ) || $has_required_message || get_option( 'fc_default_gift_options_expanded', 'no' ) === 'yes' ) {
		$expansible_section_args[ 'initial_state' ] = 'expanded';
	}

	// Output gift options expansible form section
	FluidCheckout_Steps::instance()->output_expansible_form_section_start_tag( 'gift_options', apply_filters( 'fc_expansible_section_toggle_label_gift_options', __( 'Add a gift message', 'fluid-checkout' ) ), $expansible_section_args );

	// Output gift message fields
	foreach ( $gift_message_fields as $key => $field ) {
		$field_value = array_key_exists( $key, $gift_options ) ? $gift_options[ $key ] : null;
		woocommerce_form_field( $key, $field, $field_value );
	}

	// Close expansible section tag
	FluidCheckout_Steps::instance()->output_expansible_form_section_end_tag();
	
	do_action( 'fc_checkout_gift_options_after_fields' );
	?>

</div>
