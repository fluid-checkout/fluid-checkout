<?php
/**
 * WooCommerce Fluid Checkout Form
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
 * @package WooCommerce/Templates
 * @version 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

do_action( 'woocommerce_before_checkout_form', $checkout );


// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
  echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
  return;
}

?>

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

          <div class="wfc-actions">
            <button class="wfc-next"><?php _e('Proceed To Shipping', 'woocommerce-fluid-checkout') ; ?></button>
          </div>

        </section>

        <?php do_action( 'wfc_after_billing' ); ?>

        <section class="wfc-frame" data-label="<?php esc_attr_e( 'Shipping', 'woocommerce-fluid-checkout' ) ?>">
          <div class="wfc-row">
            <?php do_action( 'woocommerce_checkout_shipping' ); ?>
          </div>
          
          <?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

          <div class="wfc-actions">
            <button class="wfc-prev"><?php _e( 'Back', 'woocommerce-fluid-checkout' ) ; ?></button>

            <button class="wfc-next"><?php _e('Proceed to Secure Payment', 'woocommerce-fluid-checkout') ; ?></button>
          </div>
        </section>

        <?php do_action( 'wfc_after_shipping' ); ?>

      <?php endif; ?>

      <section class="wfc-frame" data-label="<?php esc_attr_e( 'Payment', 'woocommerce-fluid-checkout' ) ?>">
        <?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
          <div class="wfc-row">
            <h3 id="order_review_heading">
              <?php _e( 'Your Order', 'woocommerce-fluid-checkout' ); ?>
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