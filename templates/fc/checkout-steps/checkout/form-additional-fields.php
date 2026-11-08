<?php
/**
 * Checkout shipping additional information form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-additional-fields.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package fluid-checkout
 * @version 3.0.0
 * @wc-version 11.2.0
 * @original plugins/woocommerce/templates/checkout/form-shipping.php
 * @global WC_Checkout $checkout
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-additional-fields">
	<?php do_action( 'woocommerce_before_order_notes', $checkout ); ?>

	<?php $order_fields = $checkout->get_checkout_fields( 'order' ); ?>

	<?php // CHANGE: Keep FC order-comments setting via woocommerce_enable_order_notes_field (WC 11.2+ uses empty order_fields only). ?>
	<?php if ( ! empty( $order_fields ) && apply_filters( 'woocommerce_enable_order_notes_field', 'yes' === FluidCheckout_Settings::instance()->get_option( 'woocommerce_enable_order_comments' ) ) ) : ?>

		<?php // CHANGE: Remove section title "Additional Information" because it is already added as the substep title ?>

		<div class="woocommerce-additional-fields__field-wrapper">
			<?php foreach ( $order_fields as $key => $field ) : ?>
				<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
			<?php endforeach; ?>
		</div>

	<?php endif; ?>

	<?php do_action( 'woocommerce_after_order_notes', $checkout ); ?>
</div>
