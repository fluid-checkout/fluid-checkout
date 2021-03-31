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
			<h1 class="wfc-checkout__title screen-reader-text"><?php echo _x( 'Checkout', 'Checkout page title', 'woocommerce-fluid-checkout' ) ?></h1>
			<?php
			if ( function_exists( 'the_custom_logo' ) && get_theme_mod( 'custom_logo' ) ) {
				the_custom_logo();
			}
			else {
				echo '<span class="wfc-checkout__site-name">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
			}
			?>
		</div>

		<div class="wfc-checkout__cart-link-wrapper">
			<?php do_action( 'wfc_checkout_header_cart_link' ); ?>
			<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="wfc-checkout__cart-link"><?php wc_cart_totals_order_total_html(); ?></a>
		</div>

		<?php if ( has_action( 'wfc_checkout_header_widgets' ) ) : ?>
			<div class="wfc-checkout__header-widgets">
				<?php do_action( 'wfc_checkout_header_widgets' ); ?>
			</div>
		<?php endif; ?>

	</div>
</header>
