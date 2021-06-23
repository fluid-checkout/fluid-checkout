<?php
/**
 * Checkout gift options order details section
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/fc/order/order-details-gift-options.php.
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

<section class="fc-gift-options--order-details">

	<h2 class="woocommerce-column__title"><?php echo esc_html( _x( 'Gift message', 'Gift options section title in the order details', 'fluid-checkout' ) ); ?></h2>

	<figure class="fc-gift-options__message">

		<?php if ( isset( $gift_options ) && array_key_exists( '_fc_gift_message', $gift_options ) && ! empty( $gift_options[ '_fc_gift_message' ] ) ) : ?>
			<blockquote class="fc-gift-options__message-text">
				<?php echo esc_html( $gift_options[ '_fc_gift_message' ] ); ?>
			</blockquote>
		<?php endif; ?>

		<?php if ( isset( $gift_options ) && array_key_exists( '_fc_gift_from', $gift_options ) && ! empty( $gift_options[ '_fc_gift_from' ] ) ) : ?>
			<figcaption class="fc-gift-options__message-from">
				<span class="screen-reader-text"><?php echo esc_attr( _x( 'From:', 'Label on the order details for person sending the gift', 'fluid-checkout' ) ); ?></span>
				<?php echo esc_html( $gift_options[ '_fc_gift_from' ] ); ?>
			</figcaption>
		<?php endif; ?>

	</figure>

</section>
