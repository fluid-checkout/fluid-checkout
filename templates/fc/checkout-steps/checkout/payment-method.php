<?php
/**
 * Output a single payment method
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/payment-method.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     3.5.0
 * @fc-version  3.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// CHANGE: Define extra classes for the payment method list item.
$extra_classes = array();

// CHANGE: Maybe add class for payment method with payment box.
if ( $gateway->has_fields() || $gateway->get_description() ) {
	$extra_classes[] = 'has-payment-box';
}

// CHANGE: Get the payment method icon as html.
// This avoids breaking update checkout AJAX calls when
// the payment method plugin outputs HTML out of place while trying to get the icon.
ob_start();
$icon_html = $gateway->get_icon(); // WPCS: XSS ok.
$icon_html_from_output_buffer = ob_get_clean();
if ( ( null !== $icon_html_from_output_buffer && ! empty( $icon_html_from_output_buffer ) ) || ( null !== $icon_html && ! empty( trim( $icon_html ) ) ) ) {
	$extra_classes[] = 'has-icon';
}
?>
<?php // CHANGE: Output extra classes to the payment method list item. ?>
<li class="wc_payment_method payment_method_<?php echo esc_attr( $gateway->id ); ?> <?php echo esc_attr( implode( ' ', $extra_classes ) ); ?>">
	<input id="payment_method_<?php echo esc_attr( $gateway->id ); ?>" type="radio" class="input-radio" name="payment_method" value="<?php echo esc_attr( $gateway->id ); ?>" <?php checked( $gateway->chosen, true ); ?> data-order_button_text="<?php echo esc_attr( $gateway->order_button_text ); ?>" />

	<label for="payment_method_<?php echo esc_attr( $gateway->id ); ?>">
        <?php // CHANGE: Add `span` element for the payment method label text and icon, and output icons from the variable `$icon_html` instead of calling `get_icon` function directly. ?>
		<span class="payment-method__label-text"><?php echo $gateway->get_title(); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></span> <span class="payment-method__label-icon"><?php echo $icon_html; /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></span>
	</label>
	<?php if ( $gateway->has_fields() || $gateway->get_description() ) : ?>
		<div class="payment_box payment_method_<?php echo esc_attr( $gateway->id ); ?>" <?php if ( ! $gateway->chosen ) : /* phpcs:ignore Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace */ ?>style="display:none;"<?php endif; /* phpcs:ignore Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace */ ?>>
			<?php $gateway->payment_fields(); ?>
		</div>
	<?php endif; ?>
</li>
