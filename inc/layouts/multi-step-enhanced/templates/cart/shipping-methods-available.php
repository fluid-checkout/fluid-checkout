<?php
/**
 * Cart shipping methods available
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/shipping-methods-available.php.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package woocommerce-fluid-checkout
 * @version 1.1.0
 */

defined( 'ABSPATH' ) || exit;
?>
<p class="shipping shipping_method__package" data-title="<?php echo esc_attr( $package_name ); ?>" data-package-index="<?php echo esc_attr( $index ); ?>">

<?php if ( sizeof( $available_methods ) > 0 ) : ?>

	<ul id="shipping_method" class="shipping_method__options">
	<?php foreach ( $available_methods as $method ) :
		$checked_method = sizeof( $available_methods ) === 1 || $method->id == $chosen_method;

		printf( '<li class="shipping_method__option"><input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s />
			<label for="shipping_method_%1$d_%2$s" class="shipping_method__option has-price">%5$s</label></li>',
			$index,
			sanitize_title( $method->id ),
			esc_attr( $method->id ),
			checked( $checked_method, true, false ),
			FluidCheckoutLayout_MultiStepEnhanced::instance()->get_cart_shipping_methods_label( $method )
		);

		do_action( 'woocommerce_after_shipping_rate', $method, $index );
	endforeach; ?>
	</ul>

	<?php if ( $show_package_details ) : ?>
	<?php echo '<p class="woocommerce-shipping-contents"><small>' . esc_html( $package_details ) . '</small></p>'; ?>
	<?php endif; ?>

	<?php if ( ! empty( $show_shipping_calculator ) ) : ?>
	<?php woocommerce_shipping_calculator(); ?>
	<?php endif; ?>

<?php else: ?>

	<?php echo apply_filters( 'woocommerce_cart_no_shipping_available_html', wpautop( __( 'There are no shipping methods available. Please ensure that your address has been entered correctly, or contact us if you need any help.', 'woocommerce-fluid-checkout' ) ) ); ?>

<?php endif; ?>

</p>
