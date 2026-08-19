<?php
/**
 * Recurring cart tax totals
 *
 * @author  WooCommerce
 * @package WooCommerce Subscriptions/Templates
 * @version 1.0.0 - Migrated from WooCommerce Subscriptions v3.1.0
 * @fc-version 4.2.7
 */

defined( 'ABSPATH' ) || exit;
// CHANGE: Use rowspan for the tax label so multiple recurring carts match subtotal and shipping layout.
$display_heading = true;

// CHANGE: Count tax rows to set the heading rowspan
$total_tax_rows = 0;
foreach ( $recurring_carts as $recurring_cart ) {
	$tax_amount = wp_kses_post( apply_filters( 'wcs_recurring_cart_tax_totals_html', wcs_cart_price_string( $recurring_cart->get_taxes_total(), $recurring_cart ), $recurring_cart ) );

	if ( empty( $tax_amount ) ) {
		continue;
	}

	$total_tax_rows++;
}

foreach ( $recurring_carts as $recurring_cart_key => $recurring_cart ) {
	/**
	 * Allow third-parties to filter the tax displayed.
	 *
	 * @since 1.0.0 - Migrated from WooCommerce Subscriptions v3.1.0
	 * @param string  $tax_amount     The recurring cart's total tax price string.
	 * @param WC_Cart $recurring_cart The recurring cart.
	 */
	$tax_amount = wp_kses_post( apply_filters( 'wcs_recurring_cart_tax_totals_html', wcs_cart_price_string( $recurring_cart->get_taxes_total(), $recurring_cart ), $recurring_cart ) );

	// Skip the tax if there's nothing to display.
	if ( empty( $tax_amount ) ) {
		continue;
	} ?>

	<tr class="tax-total recurring-total">

	<?php if ( $display_heading ) { ?>
		<?php $display_heading = false; ?>
		<?php // CHANGE: Add rowspan so the tax label spans all recurring carts ?>
		<th rowspan="<?php echo esc_attr( $total_tax_rows ); ?>"><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></th>
		<td data-title="<?php echo esc_attr( WC()->countries->tax_or_vat() ); ?>"><?php echo $tax_amount; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
	<?php } else { ?>
		<?php // CHANGE: Do not output an empty `th` on following tax rows ?>
		<td><?php echo $tax_amount; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
	<?php } ?>
	<?php // CHANGE: Close the table row ?>
	</tr>
	<?php
}
