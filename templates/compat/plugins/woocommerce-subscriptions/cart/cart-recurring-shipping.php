<?php
/**
 * Recurring Shipping Methods Display
 *
 * Based on the WooCommerce core template: /woocommerce/templates/cart/cart-shipping.php
 *
 * @author  Prospress
 * @package WooCommerce Subscriptions/Templates
 * @version 1.0.0 - Migrated from WooCommerce Subscriptions v2.6.0
 */

 // CHANGE: This template has been modified to align the shipping methods for subscription plans with Fluid Checkout's original shipping methods.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$packages = WC()->shipping()->get_packages();
$package_index = array_search( $package, $packages );
?>

<div class="shipping shipping-method__package recurring-total <?php echo esc_attr( $recurring_cart_key ); ?>">

	<?php if ( FluidCheckout_Steps::instance()->is_shipping_package_name_display_enabled() ) : ?>
		<p class="shipping-method__package-name"><?php echo esc_html( $package_name ); ?></p>
	<?php endif; ?>

	<?php echo apply_filters( 'fc_shipping_method_option_start_tag_markup', '<ul id="shipping_method" class="shipping-method__options">' ); ?>

	<?php if ( 1 < count( $available_methods ) ) : ?>
		
		<?php 
		$first = true;
		foreach ( $available_methods as $method ) : 
			$checked_method = $chosen_method && $method->id == $chosen_method;

			// Get contents after the shipping rate
			ob_start();
			do_action( 'woocommerce_after_shipping_rate', $method, $package_index );
			$after_shipping_rate = ob_get_clean();
			if ( ! empty( $after_shipping_rate ) ) {
				$after_shipping_rate = '<div class="shipping-method__after-shipping-rate">' . $after_shipping_rate . '</div>';
			}

			// Maybe add extra class
			$label_extra_classes = '';
			if (
				( WC()->cart->display_prices_including_tax() && $method->get_shipping_tax() > 0 && ! wc_prices_include_tax() )
				|| ( ! WC()->cart->display_prices_including_tax() && $method->get_shipping_tax() > 0 && wc_prices_include_tax() )
			) {
				$label_extra_classes = 'has-tax-notes';
			}

			echo apply_filters( 'fc_shipping_method_option_markup',
				sprintf( '<li class="shipping-method__option"><input type="radio" name="shipping_method[%1$s]" data-index="%1$s" id="shipping_method_%1$s_%2$s" value="%3$s" class="shipping_method" %4$s />
					<label for="shipping_method_%1$s_%2$s" class="shipping-method__option-label has-price %7$s"><div class="shipping-method__option-label-wrapper">%5$s</div>%8$s%6$s</label>
				</li>',
				$index,
				// The function `sanitize_title` is used below to convert the string into a CSS-class-like string
				sanitize_title( $method->id ),
				esc_attr( $method->id ),
				checked( $checked_method, true, false ),
				FluidCheckout_WooCommerceSubscriptions::instance()->get_recurring_shipping_methods_label( $recurring_cart, $method ),
				$after_shipping_rate,
				$label_extra_classes,
				FluidCheckout_Steps::instance()->get_cart_shipping_methods_description( $method )
			), $method, $package_index, $chosen_method, $first );

			$first = false;
		endforeach; ?>

	<?php endif; ?>

	<?php echo apply_filters( 'fc_shipping_method_option_end_tag_markup', '</ul>' ); ?>

	<?php if ( FluidCheckout_WooCommerceSubscriptions::instance()->get_all_packages_count() > 1 ) : ?>
		<?php echo '<p class="woocommerce-shipping-contents"><small>' . esc_html( FluidCheckout_WooCommerceSubscriptions::instance()->get_package_details( $package ) ) . '</small></p>'; ?>
	<?php endif; ?>

</div>
