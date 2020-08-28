<?php
/**
 * Thankyou page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.7.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-order">

	<?php
	if ( $order ) :

		do_action( 'woocommerce_before_thankyou', $order->get_id() );
		?>

        <?php if ( $order->has_status( 'failed' ) ) : ?>
            
            <?php // CHANGE: Added new hook and moved code from this section into it's own file order-received-failed.php ?>
            <?php do_action( 'wfc_order_received_failed', $order ); ?>

        <?php else : ?>
            
            <?php // CHANGE: Added new hook and moved code from this section into it's own file order-received-successful.php ?>
            <?php do_action( 'wfc_order_received_successful', $order ); ?>

		<?php endif; ?>

        <?php // CHANGE: Moved hook `woocommerce_thankyou_<payment_method>` to a function hooked to `woocommerce_thankyou` ?>
		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>

	<?php else : ?>

        <?php // CHANGE: Added new hook and moved code from this section into it's own file order-received-no-order-details.php ?>
        <?php do_action( 'wfc_order_received_successful_no_order_details' ); ?>

	<?php endif; ?>

</div>
