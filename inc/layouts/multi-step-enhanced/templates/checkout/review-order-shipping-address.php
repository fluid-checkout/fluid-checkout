<?php
/**
 * Shipping adress for order review
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/review-order-shipping-adress.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package woocommerce-fluid-checkout
 * @version 1.1.0
 */

defined( 'ABSPATH' ) || exit;
?>
<tr class="woocommerce-shipping-address">
	<th class="woocommerce-shipping-address__label"><?php _e( 'Shipping Address', 'woocommerce-fluid-checkout' ); ?></th>
	<td class="woocommerce-shipping-address__value address-book__entry-label"><?php echo apply_filters( 'wfc_review_order_shipping_address_markup', FluidCheckout_AddressBook::instance()->get_shipping_address_entry_display_label( $shipping_address ), $shipping_address ); ?></td>
</tr>
