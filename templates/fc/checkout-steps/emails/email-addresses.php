<?php
/**
 * Email Addresses
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-addresses.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 5.6.0
 * @fc-version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$text_align = is_rtl() ? 'right' : 'left';
// CHANGE: Replace variables for formatted addresses with filters to allow plugins to change the displayed addresses
$billing_address_formatted = apply_filters( 'fc_pro_order_details_customer_billing_address_formatted', $order->get_formatted_billing_address( esc_html__( 'N/A', 'woocommerce' ) ), $order );
$shipping_address_formatted = apply_filters( 'fc_pro_order_details_customer_shipping_address_formatted', $order->get_formatted_shipping_address( esc_html__( 'N/A', 'woocommerce' ) ), $order );

$show_shipping = ! wc_ship_to_billing_address_only() && $order->needs_shipping_address();

// CHANGE: Add filter to allow plugins to define whether to show shipping address
$show_shipping = apply_filters( 'fc_pro_order_details_customer_information_show_shipping', $show_shipping, $order );

// CHANGE: Define labels for shipping and billing columns
$billing_address_label = apply_filters( 'fc_pro_order_details_customer_billing_address_label', FluidCheckout_Steps::instance()->get_substep_title( 'billing_address' ), $order );
$shipping_address_label = apply_filters( 'fc_pro_order_details_customer_shipping_address_label', FluidCheckout_Steps::instance()->get_substep_title( 'shipping_address' ), $order );

?><table id="addresses" cellspacing="0" cellpadding="0" style="width: 100%; vertical-align: top; margin-bottom: 40px; padding:0;" border="0">
	<?php // CHANGE: Move addresses titles to a separate row ?>
	<tr>
		<th style="text-align:<?php echo esc_attr( $text_align ); ?>; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; border:0; padding:0;" valign="top" width="50%">
			<h2><?php echo esc_html( $billing_address_label ); ?></h2>
		</th>

		<?php // CHANGE: Change criteria for showing the shipping address section ?>
		<?php if ( $show_shipping ) : ?>
			<th style="text-align:<?php echo esc_attr( $text_align ); ?>; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; padding:0;" valign="top" width="50%">
				<h2><?php echo esc_html( $shipping_address_label ); ?></h2>
			</th>
		<?php endif; ?>
	</tr>
	<?php // CHANGE: END - Move addresses titles to a separate row ?>

	<tr>
		<td class="billing-address" style="text-align:<?php echo esc_attr( $text_align ); ?>; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; border:0; padding:0;" valign="top" width="50%">
			<?php // CHANGE: Move addresses titles to a separate row ?>

			<?php // CHANGE: Add address type class ?>
			<address class="address billing-address">
				<?php // CHANGE: Use filtered billing address to display ?>
				<?php echo wp_kses_post( $billing_address_formatted ); ?>

				<?php // CHANGE: Only output the phone number if it is not already included in the formatted address ?>
				<?php if ( $order->get_billing_phone() && -1 === strpos( $billing_address_formatted, $order->get_billing_phone() ) ) : ?>
					<br/><?php echo wc_make_phone_clickable( $order->get_billing_phone() ); ?>
				<?php endif; ?>
				<?php if ( $order->get_billing_email() ) : ?>
					<br/><?php echo esc_html( $order->get_billing_email() ); ?>
				<?php endif; ?>
				<?php
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
				do_action( 'woocommerce_email_customer_address_section', 'billing', $order, $sent_to_admin, false );
				?>
			</address>
		</td>

		<?php // CHANGE: Change criteria for showing the shipping address section ?>
		<?php if ( $show_shipping ) : ?>
			<?php // CHANGE: Add address type class ?>
			<td class="shipping-address" style="text-align:<?php echo esc_attr( $text_align ); ?>; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; padding:0;" valign="top" width="50%">
				<?php // CHANGE: Move addresses titles to a separate row ?>

				<?php // CHANGE: Add address type class ?>
				<address class="address shipping-address">
					<?php // CHANGE: Use filtered shipping address to display ?>
					<?php echo wp_kses_post( $shipping_address_formatted ); ?>

					<?php // CHANGE: Only output the phone number if it is not already included in the formatted address ?>
					<?php if ( $order->get_shipping_phone() && -1 === strpos( $shipping_address_formatted, $order->get_shipping_phone() ) ) : ?>
						<br /><?php echo wc_make_phone_clickable( $order->get_shipping_phone() ); ?>
					<?php endif; ?>
					<?php
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
					do_action( 'woocommerce_email_customer_address_section', 'shipping', $order, $sent_to_admin, false );
					?>
				</address>
			</td>
		<?php endif; ?>
	</tr>
</table>
