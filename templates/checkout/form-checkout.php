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
 * @fc-version 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo '<div class="fc-must-login-notice">';
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	echo '</div>';
	return;
}

?>

<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

	<div id="fc-wrapper" class="fc-wrapper <?php echo esc_attr( apply_filters( 'fc_wrapper_classes', '' ) ); ?>">

		<?php do_action( 'fc_checkout_before', $checkout ); ?>

		<div class="fc-inside">

			<?php do_action( 'fc_checkout_before_steps', $checkout ); ?>

			<div class="fc-checkout-steps">
				<?php do_action( 'fc_checkout_steps', $checkout ); ?>
			</div>

			<?php do_action( 'fc_checkout_after_steps', $checkout ); ?>

		</div>

		<?php do_action( 'fc_checkout_after', $checkout ); ?>

	</div>

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
