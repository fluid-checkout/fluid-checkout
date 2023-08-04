<?php
/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.5.0
 * @fc-version 3.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	// CHANGE: Add element wrapping "must login" message
	echo '<div class="fc-must-login-notice">';
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	echo '</div>';
	// CHANGE: END - Add element wrapping "must login" message
	return;
}

// CHANGE: Prepare wrapper inside element custom attributes
$custom_attributes = apply_filters( 'fc_checkout_wrapper_inside_element_custom_attributes', array() );
$custom_attributes_prep = array();
$custom_attributes_esc = '';
if ( ! empty( $custom_attributes ) && is_array( $custom_attributes ) ) {
	foreach ( $custom_attributes as $attribute => $attribute_value ) {
		if ( true === $attribute_value ) {
			$custom_attributes_prep[] = esc_attr( $attribute );
		}
		else {
			$custom_attributes_prep[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
		}
	}

	$custom_attributes_esc = implode( ' ', $custom_attributes_prep );
}
// CHANGE: END - Prepare wrapper inside element custom attributes
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

	<?php // CHANGE: Replace checkout fields and order summary output with new page structure and hooks ?>
	<div id="fc-wrapper" class="fc-wrapper <?php echo esc_attr( apply_filters( 'fc_wrapper_classes', '' ) ); ?>">

		<?php do_action( 'fc_checkout_before', $checkout ); ?>

		<div class="fc-inside" <?php echo $custom_attributes_esc; ?>>

			<?php do_action( 'fc_checkout_before_steps', $checkout ); ?>

			<div class="fc-checkout-steps">
				<?php do_action( 'fc_checkout_steps', $checkout ); ?>
			</div>

			<?php do_action( 'fc_checkout_after_steps', $checkout ); ?>

		</div>

		<?php do_action( 'fc_checkout_after', $checkout ); ?>

	</div>
	<?php // CHANGE: END - Replace checkout fields and order summary output with new page structure and hooks ?>

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
