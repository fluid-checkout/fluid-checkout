<?php
/**
 * Recurring cart shipping subtotals
 * 
 * Based on the WooCommerce Subscriptions template: /templates/checkout/recurring-subtotals.php
 * 
 * @package fluid-checkout
 * @version 3.2.4
 */

defined( 'ABSPATH' ) || exit;
?>

<tr class="cart-subtotal recurring-total">

<?php if ( $display_heading ) { ?>
	<th rowspan="<?php echo esc_attr( count( $recurring_carts ) ); ?>"><?php esc_html_e( 'Shipment', 'woocommerce-subscriptions' ); ?></th>
	<td data-title="<?php esc_attr_e( 'Shipment', 'woocommerce-subscriptions' ); ?>"><?php echo $shipping_subtotal; ?></td>
<?php } else { ?>
	<td><?php echo $shipping_subtotal; ?></td>
<?php } ?>

</tr> 
