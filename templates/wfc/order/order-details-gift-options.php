<?php
/**
 * Checkout gift options order details section
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/wfc/order/order-details-gift-options.php.
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

<section class="wfc-gift-options--order-details">

	<h2 class="woocommerce-column__title"><?php echo _x( 'Gift message', 'Gift options section title in the order details', 'fluid-checkout' ); ?></h2>

	<figure class="wfc-gift-options__message">

		<?php if ( isset( $gift_options ) && array_key_exists( '_wfc_gift_message', $gift_options ) && ! empty( $gift_options[ '_wfc_gift_message' ] ) ) : ?>
			<blockquote class="wfc-gift-options__message-text">
				<?php echo esc_html( $gift_options[ '_wfc_gift_message' ] ); ?>
			</blockquote>
		<?php endif; ?>
		
		<?php if ( isset( $gift_options ) && array_key_exists( '_wfc_gift_from', $gift_options ) && ! empty( $gift_options[ '_wfc_gift_from' ] ) ) : ?>
			<figcaption class="wfc-gift-options__message-from">
				<span class="screen-reader-text"><?php echo esc_attr( __( 'From:', 'fluid-checkout' ) ); ?></span>
				<?php echo esc_html( $gift_options[ '_wfc_gift_from' ] ); ?>
			</figcaption>
		<?php endif; ?>

	</figure>

</section>
