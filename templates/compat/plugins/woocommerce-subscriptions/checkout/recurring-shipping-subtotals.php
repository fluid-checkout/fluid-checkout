<?php
/**
 * Recurring cart shipping subtotals
 * 
 * Based on the WooCommerce Subscriptions template: /templates/checkout/recurring-subtotals.php
 * 
 * @package fluid-checkout
 * @version 3.2.5
 */

defined( 'ABSPATH' ) || exit;
?>

<tr class="cart-subtotal recurring-total">

<?php if ( $display_heading ) { ?>
	<th rowspan="<?php echo esc_attr( count( $recurring_carts ) ); ?>"><?php esc_html_e( 'Shipping', 'woocommerce' ); ?></th>
	<td data-title="<?php esc_attr_e( 'Shipping', 'woocommerce' ); ?>"><?php echo $shipping_subtotal; ?></td>
<?php } else { ?>
	<td><?php echo $shipping_subtotal; ?></td>
<?php } ?>

</tr> 

