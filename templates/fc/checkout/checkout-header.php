<?php
/**
 * Checkout header template file.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/fc/checkout/checkout-header.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package fluid-checkout
 * @version 1.4.3
 */

defined( 'ABSPATH' ) || exit;
?>

<header class="fc-checkout-header">
	<div class="fc-checkout-header__inner">

		<div class="fc-checkout__branding">
			<?php
			$site_title = get_bloginfo( 'name' );
			$homepage_link_description = esc_attr( __( 'Go to the home page', 'fluid-checkout' ) );
			
			$logo_html = '';
			ob_start();

			if ( ! empty( get_option( 'fc_checkout_logo_image', '' ) ) ) {
				echo sprintf(
					'<a href="%1$s" class="custom-logo-link" rel="home">%2$s</a>',
					esc_url( apply_filters( 'fc_checkout_header_logo_home_url', home_url( '/' ) ) ),
					wp_get_attachment_image( get_option( 'fc_checkout_logo_image', '' ), 'full' )
				);
			}
			else if ( has_action( 'fc_checkout_header_logo' ) ) {
				do_action( 'fc_checkout_header_logo' );
			}
			else if ( function_exists( 'the_custom_logo' ) && get_theme_mod( 'custom_logo' ) ) {
				the_custom_logo();
			}
			else {
				echo sprintf(
					'<a href="%1$s" class="custom-logo-link" rel="home">%2$s</a>',
					esc_url( apply_filters( 'fc_checkout_header_logo_home_url', home_url( '/' ) ) ),
					'<span class="fc-checkout__site-name">' . esc_html( get_bloginfo( 'name' ) ) . '</span>'
				);
			}

			$logo_html = ob_get_clean();
			
			// Maybe add `aria-description`
			if ( ! strpos( $logo_html, ' aria-description=' ) ) {
				$logo_html = str_replace( 'rel="home"', 'rel="home" aria-description="' . $homepage_link_description . '"', $logo_html );
			}

			// Maybe add image `alt` attribute
			if ( strpos( $logo_html, ' alt=""' ) ) {
				$logo_html = str_replace( 'alt=""', 'alt="' . $site_title . '"', $logo_html );
			}
			
			echo $logo_html;
			?>
		</div>

		<?php do_action( 'fc_checkout_header_widgets' ); ?>

		<?php if ( has_action( 'fc_checkout_header_cart_link' ) ) : ?>
		<div class="fc-checkout__cart-link-wrapper">
			<?php do_action( 'fc_checkout_header_cart_link' ); ?>
		</div>
		<?php endif; ?>

	</div>
</header>
