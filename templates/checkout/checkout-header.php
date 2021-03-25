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
?>
<header class="wfc-checkout-header">
	<div class="wfc-checkout-header__inner">

		<div class="wfc-checkout__branding">
			<?php
			if ( function_exists( 'the_custom_logo' ) && get_theme_mod( 'custom_logo' ) ) {
				the_custom_logo();
			}
			else {
				echo '<h1 class="wfc-checkout__site-name">' . esc_html( get_bloginfo( 'name' ) ) . '</h1>';
			}
			?>
		</div>

		<div class="wfc-checkout__cart-link-wrapper">
			<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="wfc-checkout__cart-link" aria-label="<?php _e( 'Open the order summary', 'woocommerce-fluid-checkout' ); ?>"><?php wc_cart_totals_order_total_html(); ?></a>
		</div>

		<?php if ( is_active_sidebar( 'wfc_header_trust' ) ) : ?>
			<div class="wfc-checkout__header-trust">
				<?php dynamic_sidebar( 'wfc_header_trust' ); ?>
			</div>
		<?php endif; ?>

	</div>
</header>
