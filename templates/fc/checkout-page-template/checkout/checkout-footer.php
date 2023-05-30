<?php
/**
 * Checkout footer template file.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/checkout-footer.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package fluid-checkout
 * @version 2.0.2
 */

defined( 'ABSPATH' ) || exit;
?>

<footer class="fc-checkout-footer">
	<div class="fc-widget-area fc-checkout-footer__inner fc-clearfix">

		<?php do_action( 'fc_checkout_footer_widgets' ); ?>

	</div>
</footer>
