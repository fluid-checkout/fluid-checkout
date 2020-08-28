<?php
/**
 * Order received page content for successful orders
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/order-received-successful.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * This template is a partial copy from woocommerce/templates/checkout/thankyou.php, therefore the package
 * and version number are kept from WooCommerce for reference when updating the template file.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.7.0
 */

defined( 'ABSPATH' ) || exit;
?>

<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'woocommerce' ), $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>

<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">

    <li class="woocommerce-order-overview__order order">
        <?php esc_html_e( 'Order number:', 'woocommerce' ); ?>
        <strong><?php echo $order->get_order_number(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
    </li>

    <li class="woocommerce-order-overview__date date">
        <?php esc_html_e( 'Date:', 'woocommerce' ); ?>
        <strong><?php echo wc_format_datetime( $order->get_date_created() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
    </li>

    <?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) : ?>
        <li class="woocommerce-order-overview__email email">
            <?php esc_html_e( 'Email:', 'woocommerce' ); ?>
            <strong><?php echo $order->get_billing_email(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
        </li>
    <?php endif; ?>

    <li class="woocommerce-order-overview__total total">
        <?php esc_html_e( 'Total:', 'woocommerce' ); ?>
        <strong><?php echo $order->get_formatted_order_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
    </li>

    <?php if ( $order->get_payment_method_title() ) : ?>
        <li class="woocommerce-order-overview__payment-method method">
            <?php esc_html_e( 'Payment method:', 'woocommerce' ); ?>
            <strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
        </li>
    <?php endif; ?>

</ul>
