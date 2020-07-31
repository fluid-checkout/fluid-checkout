<?php
/**
 * Checkout Payment Section
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/place-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @see     templates/checkout/payment.php
 * @package woocommerce-fluid-checkout
 * @version 3.5.3
 */

defined( 'ABSPATH' ) || exit;

?>

<?php do_action( 'wfc_checkout_before_order_review' ); ?>

<div class="wfc-checkout-order-review">

	<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
	<h3 id="order_review_heading"><?php echo esc_html( $order_review_title ); ?></h3>
	
	<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
	<div id="order_review" class="woocommerce-checkout-review-order">
		<?php do_action( 'woocommerce_checkout_order_review' ); ?>
	</div>
	<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

</div>

<?php do_action( 'wfc_checkout_after_order_review' ); ?>
