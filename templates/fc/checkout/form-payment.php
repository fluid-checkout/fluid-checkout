<?php
/**
 * Checkout payment form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/fc/checkout/form-payment.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package fluid-checkout
 * @version 1.2.0
 */

defined( 'ABSPATH' ) || exit;
?>

<?php do_action( 'fc_checkout_before_step_payment_fields' ); ?>

<?php do_action( 'fc_checkout_payment' ); ?>

<?php do_action( 'fc_checkout_after_step_payment_fields' ); ?>
