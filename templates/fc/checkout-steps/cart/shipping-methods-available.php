<?php
/**
 * Cart shipping methods available
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/shipping-methods-available.php.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package fluid-checkout
 * @version 4.0.1
 * @wc-version 3.6.0
 * @wc-original cart/cart-shipping.php
 */

defined( 'ABSPATH' ) || exit;
$formatted_destination    = isset( $formatted_destination ) ? $formatted_destination : WC()->countries->get_formatted_address( $package['destination'], ', ' );
$has_calculated_shipping  = ! empty( $has_calculated_shipping );
?>

<?php // CHANGE: Remove shipping totals table row elements ?>

<div class="shipping shipping-method__package" data-title="<?php echo esc_attr( $package_name ); ?>" data-package-index="<?php echo esc_attr( $package_index ); ?>">

	<?php if ( ( is_checkout() && ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'woocommerce_shipping_cost_requires_address' ) || $has_calculated_shipping ) ) || ( FluidCheckout_Steps::instance()->is_cart_page_or_fragment() && WC()->cart->show_shipping() ) ) : ?>

		<?php // CHANGE: Conditionally add the shipping package name ?>
		<?php if ( FluidCheckout_Steps::instance()->is_shipping_package_name_display_enabled() ) : ?>
			<p class="shipping-method__package-name"><?php echo esc_html( $package_name ); ?></p>
		<?php endif; ?>

		<?php if ( count( $available_methods ) > 0 ) : ?>

			<?php // CHANGE: Add filter to let developers change the shipping methods wrapper element markup ?>
			<?php echo apply_filters( 'fc_shipping_method_option_start_tag_markup', '<ul id="shipping_method" class="shipping-method__options">' ); ?>

			<?php // CHANGE: Add shipping methods elements markup ?>
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
					sprintf( '<li class="shipping-method__option"><input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s />
						<label for="shipping_method_%1$d_%2$s" class="shipping-method__option-label has-price %7$s"><div class="shipping-method__option-label-wrapper">%5$s</div>%8$s%6$s</label>
					</li>',
					$package_index,
					// The function `sanitize_title` is used below to convert the string into a CSS-class-like string
					sanitize_title( $method->id ),
					esc_attr( $method->id ),
					checked( $checked_method, true, false ),
					FluidCheckout_Steps::instance()->get_cart_shipping_methods_label( $method ),
					$after_shipping_rate,
					$label_extra_classes,
					FluidCheckout_Steps::instance()->get_cart_shipping_methods_description( $method )
				), $method, $package_index, $chosen_method, $first );

				$first = false;
			endforeach; ?>

			<?php // CHANGE: Add filter to let developers change the shipping methods wrapper element closing tag ?>
			<?php echo apply_filters( 'fc_shipping_method_option_end_tag_markup', '</ul>' ); ?>

			<?php // CHANGE: Remove shipping calculator and related messages, moved to template file `fc-pro/cart/cart/shipping-methods-calculate-shipping.php` ?>

			<?php if ( $show_package_details ) : ?>
			<?php echo '<p class="woocommerce-shipping-contents"><small>' . esc_html( $package_details ) . '</small></p>'; ?>
			<?php endif; ?>

		<?php endif; ?>

	<?php endif; ?>

	<?php // CHANGE: Conditionally display message for when no shipping methods are available for the package, only on the checkout page ?>
	<?php if ( is_checkout() && 0 === count( $available_methods ) && ( $has_calculated_shipping || 'yes' !== FluidCheckout_Settings::instance()->get_option( 'woocommerce_shipping_cost_requires_address' ) ) ) : ?>
		<div class="fc-shipping-method__no-shipping-methods shipping-method__package">
			<div class="shipping-method__options">
				<?php echo wp_kses_post( apply_filters( 'woocommerce_no_shipping_available_html', __( 'There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.', 'woocommerce' ) ) ); ?>
			</div>
		</div>
	<?php endif; ?>

	<?php // CHANGE: Conditionally display message for when shipping costs are calculated during checkout, but shipping address has not yet been provided ?>
	<?php if ( is_checkout() && 'yes' === FluidCheckout_Settings::instance()->get_option( 'woocommerce_shipping_cost_requires_address' ) && ! $has_calculated_shipping ) : ?>
		<div class="fc-shipping-method__incomplete-address shipping-method__package">
			<div class="shipping-method__options">
				<?php echo wp_kses_post( apply_filters( 'woocommerce_shipping_may_be_available_html', __( 'Enter your address to view shipping options.', 'woocommerce' ) ) ); ?>
			</div>
		</div>
	<?php endif; ?>

	<?php // CHANGE: Output the current shipping method to detect changes to this value later ?>
	<input type="hidden" name="fc_previous_selected_shipping_method[<?php echo esc_attr( $package_index ); ?>]" value="<?php echo esc_attr( $chosen_method ); ?>" />

</div>

<?php // CHANGE: Remove shipping totals table row closing elements ?>
