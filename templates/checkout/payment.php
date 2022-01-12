<?php
/**
 * Checkout Payment Section
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/payment.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 6.1.0
 * @fc-version 1.4.3
 */

defined( 'ABSPATH' ) || exit;

if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_before_payment' );
}
?>
<div id="payment" class="woocommerce-checkout-payment">

	<?php // CHANGE: Added hook for before the payment section ?>
	<?php do_action( 'fc_checkout_before_payment', $checkout ); ?>

	<?php if ( WC()->cart->needs_payment() ) : ?>
		<div class="fc-payment-methods__wrapper">
			<ul class="wc_payment_methods payment_methods methods">
				<?php
				if ( ! empty( $available_gateways ) ) {
					foreach ( $available_gateways as $gateway ) {
						wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
					}
				} else {
					echo '<li class="woocommerce-notice woocommerce-notice--info woocommerce-info">' . apply_filters( 'woocommerce_no_available_payment_methods_message', WC()->customer->get_billing_country() ? esc_html__( 'Sorry, it seems that there are no available payment methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ) : esc_html__( 'Please fill in your details above to see available payment methods.', 'woocommerce' ) ) . '</li>'; // @codingStandardsIgnoreLine
				}
				?>
			</ul>
		</div>
	<?php // CHANGE: Display info message when payment is not needed ?>
	<?php else: ?>
		<div class="woocommerce-info"><?php echo apply_filters( 'fc_payment_not_needed_message', sprintf( esc_html( __( 'Your order has a total amount due of %s. No&nbsp;further payment is needed.', 'fluid-checkout' ) ), wc_price( 0 ) ) ); ?></div>
	<?php endif; ?>

	<?php // CHANGE: Removed place order section, moved to templates/fc/checkout/place-order.php ?>

	<?php // CHANGE: Added hook for after the payment section ?>
	<?php do_action( 'fc_checkout_after_payment', $checkout ); ?>

</div>


<?php
if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_after_payment' );
}
