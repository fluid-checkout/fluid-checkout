<?php
/**
 * Cart errors page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/cart-errors.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.5.0
 * @fc-version 2.2.0
 */

defined( 'ABSPATH' ) || exit;
?>

<?php // CHANGE: Remove cart error message and return button, the normal checkout page should be displayed with the cart error message ?>

<?php // CHANGE: Output the checkout page contents normally ?>
<?php
$non_js_checkout = ! empty( $_POST['woocommerce_checkout_update_totals'] ); // WPCS: input var ok, CSRF ok.

if ( wc_notice_count( 'error' ) === 0 && $non_js_checkout ) {
    wc_add_notice( __( 'The order totals have been updated. Please confirm your order by pressing the "Place order" button at the bottom of the page.', 'woocommerce' ) );
}

wc_get_template( 'checkout/form-checkout.php', array( 'checkout' => $checkout ) );
?>
