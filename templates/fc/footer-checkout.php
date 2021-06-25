<?php
/**
 * The template for displaying the footer for the checkout page.
 *
  * This template can be overridden by copying it to yourtheme/woocommerce/fc/checkout/footer-checkout.php.
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

</main>

<?php do_action( 'fc_checkout_footer' ); ?>

<?php wp_footer(); ?>

</body>
</html>
