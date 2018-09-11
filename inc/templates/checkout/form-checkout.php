<?php
/**
 * WooCommerce Fluid Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you (the theme developer).
 * will need to copy the new files to your theme to maintain compatibility. We try to do this.
 * as little as possible, but it does happen. When this occurs the version of the template file will.
 * be bumped and the readme will list any important changes.
 *
 * @see 	    http://docs.woothemes.com/document/template-structure/
 * @package 	WooCommerce/Templates
 * @version   2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $woocommerce;

// If checkout registration is disabled and not logged in, the user cannot checkout
if ( ! $checkout->enable_signup && ! $checkout->enable_guest_checkout && ! is_user_logged_in() ) {
	echo apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce-fluid-checkout' ) );
	return;
}

?>

<?php wc_print_notices(); ?>

<?php do_action( 'woocommerce_before_checkout_form', $checkout ); ?>

<div id="wfc-wrapper" class="<?php echo esc_attr( apply_filters( 'wfc_wrapper_classes', '' ) ); ?>">
  <div class="wfc-inside">
	  
	  <div class="wfc-row wfc-header">
	    <div id="wfc-progressbar"></div>
	  </div> 

		<?php do_action( 'wfc_before_checkout_form' ); ?>

		<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

			<?php do_action( 'wfc_before_fields' ); ?>

			<?php if ( sizeof( $checkout->checkout_fields ) > 0 ) : ?>

				<section class="wfc-frame" data-label="<?php esc_attr_e( 'Billing', 'woocommerce-fluid-checkout' ) ?>">
					
					<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
					
					<div class="wfc-row">
						<?php do_action( 'woocommerce_checkout_billing' ); ?>
					</div>

					<?php // TODO: Remove svg icon from button ?>
					<a href="#wfc-wrapper" class="wfc-next button button-success-clear button-icon button-icon--right button--big"><?php _e('Proceed To Shipping', 'woocommerce-fluid-checkout') ; ?> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg></a>

				</section>

				<?php do_action( 'wfc_after_billing' ); ?>

				<section class="wfc-frame" data-label="<?php esc_attr_e( 'Shipping', 'woocommerce-fluid-checkout' ) ?>">
					<div class="wfc-row">
						<?php do_action( 'woocommerce_checkout_shipping' ); ?>
					</div>
					
					<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

					<?php // TODO: Remove svg icon from button ?>
					<a href="#wfc-wrapper" class="wfc-next button button-success-clear button-icon button-icon--right button--big"><?php _e('Proceed to Secure Payment', 'woocommerce-fluid-checkout') ; ?> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg></a>
				</section>

				<?php do_action( 'wfc_after_shipping' ); ?>

			<?php endif; ?>

			<section class="wfc-frame" data-label="<?php esc_attr_e( 'Payment', 'woocommerce-fluid-checkout' ) ?>">
				<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
					<div class="wfc-row">
						<h3 id="order_review_heading">
							<?php _e( 'Your order', 'woocommerce-fluid-checkout' ); ?>
						</h3>

						<div id="order_review" class="woocommerce-checkout-review-order">
							<?php do_action( 'woocommerce_checkout_order_review' ); ?>
						</div>
					</div>

				<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

			</section>

	    </form>

		<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>

  </div><!-- .wfc-inside -->
    
</div><!-- #wfc-wrapper -->