<?php
/**
 * Cart totals shipping section
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-totals-shipping.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package woocommerce-fluid-checkout
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit;
?>

<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>

<?php do_action( 'woocommerce_cart_totals_before_shipping' ); ?>

<?php wc_cart_totals_shipping_html(); ?>

<?php do_action( 'woocommerce_cart_totals_after_shipping' ); ?>

<?php elseif ( WC()->cart->needs_shipping() && 'yes' === get_option( 'woocommerce_enable_shipping_calc' ) ) : ?>

<tr class="shipping">
    <th><?php esc_html_e( 'Shipping', 'woocommerce' ); ?></th>
    <td data-title="<?php esc_attr_e( 'Shipping', 'woocommerce' ); ?>"><?php woocommerce_shipping_calculator(); ?></td>
</tr>

<?php endif; ?>
