<?php
/**
 * Checkout header template file.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/checkout-header.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see       https://docs.woocommerce.com/document/template-structure/
 * @package   woocommerce-fluid-checkout
 * @version   1.2.0
 */

defined( 'ABSPATH' ) || exit;

$method = $available_methods && array_key_exists( $chosen_method, $available_methods ) ? $available_methods[ $chosen_method ] : null;
?>
<header class="wfc-checkout__header">
	<?php
	if ( 'true' === get_option( 'wfc_hide_site_header_at_checkout', 'true' ) ) :
		if ( function_exists( 'the_custom_logo' ) ) {
			the_custom_logo();
		}
	endif;
	?>

	<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="wfc-checkout__cart-link" aria-label="<?php _e( 'Open the order summary', 'woocommerce-fluid-checkout' ); ?>"><?php wc_cart_totals_order_total_html(); ?></a>
</header>
