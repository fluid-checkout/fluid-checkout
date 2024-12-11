<?php
/**
 * Email Addresses (plain)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/email-addresses.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 8.6.0
 * @fc-version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

// CHANGE: Add filter to allow plugins to define whether to show shipping address
$show_shipping = ! wc_ship_to_billing_address_only() && $order->needs_shipping_address();
$show_shipping = apply_filters( 'fc_pro_order_details_customer_information_show_shipping', $show_shipping, $order );

// CHANGE: Define labels for shipping and billing columns
$billing_address_label = apply_filters( 'fc_pro_order_details_customer_billing_address_label', FluidCheckout_Steps::instance()->get_substep_title( 'billing_address' ), $order );
$shipping_address_label = apply_filters( 'fc_pro_order_details_customer_shipping_address_label', FluidCheckout_Steps::instance()->get_substep_title( 'shipping_address' ), $order );

// CHANGE: Replace variables for formatted addresses with filters to allow plugins to change the displayed addresses
$billing_address_formatted = apply_filters( 'fc_pro_order_details_customer_billing_address_formatted', $order->get_formatted_billing_address( esc_html__( 'N/A', 'woocommerce' ) ), $order );
$shipping_address_formatted = apply_filters( 'fc_pro_order_details_customer_shipping_address_formatted', $order->get_formatted_shipping_address( esc_html__( 'N/A', 'woocommerce' ) ), $order );

// CHANGE: Use the section title from from the variables above
echo "\n" . esc_html( wc_strtoupper( esc_html( $billing_address_label ) ) ) . "\n\n";
// CHANGE: Use filtered billing address to display
echo preg_replace( '#<br\s*/?>#i', "\n", $billing_address_formatted ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

// CHANGE: Only output the phone number if it is not already included in the formatted address
if ( $order->get_billing_phone() && -1 === strpos( $billing_address_formatted, $order->get_billing_phone() ) ) {
	echo $order->get_billing_phone() . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

if ( $order->get_billing_email() ) {
	echo $order->get_billing_email() . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Fires after the core address fields in emails.
 *
 * @since 8.6.0
 *
 * @param string $type Address type. Either 'billing' or 'shipping'.
 * @param WC_Order $order Order instance.
 * @param bool $sent_to_admin If this email is being sent to the admin or not.
 * @param bool $plain_text If this email is plain text or not.
 */
do_action( 'woocommerce_email_customer_address_section', 'billing', $order, $sent_to_admin, true );

// CHANGE: Change criteria to show shipping addresses
if ( $show_shipping ) {
	// CHANGE: Remove code to retrieve and criteria to display shipping address as it has already been retrieved

	// CHANGE: Use the section title from from the variables above
	echo "\n" . esc_html( wc_strtoupper( esc_html( $shipping_address_label ) ) ) . "\n\n";

	// CHANGE: Display the formatted shipping address from the variable
	echo preg_replace( '#<br\s*/?>#i', "\n", $shipping_address_formatted ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	// CHANGE: Only output the phone number if it is not already included in the formatted address
	if ( $order->get_shipping_phone() && -1 === strpos( $shipping_address_formatted, $order->get_shipping_phone() ) ) {
		echo strpos( $shipping_address_formatted, $order->get_shipping_phone() );
		echo $order->get_shipping_phone() . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Fires after the core address fields in emails.
	 *
	 * @since 8.6.0
	 *
	 * @param string $type Address type. Either 'billing' or 'shipping'.
	 * @param WC_Order $order Order instance.
	 * @param bool $sent_to_admin If this email is being sent to the admin or not.
	 * @param bool $plain_text If this email is plain text or not.
	 */
	do_action( 'woocommerce_email_customer_address_section', 'shipping', $order, $sent_to_admin, true );
}
