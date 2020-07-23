<?php

/**
 * Customizations to the checkout page.
 */
class FluidCheckoutFieldTypes extends FluidCheckout {

  /**
   * __construct function.
   */
  public function __construct() {
    $this->hooks();
  }



  /**
   * Initialize hooks.
   */
  public function hooks() {
    // Checkout field types enhancement for mobile
    add_filter( 'woocommerce_checkout_fields' , array( $this, 'change_number_field_types' ), 5 );
  }



  /**
   * Change the types of number input fields
   * to display a more appropriate keyboard on mobile devices.
   */
  public function change_number_field_types( $fields ) {
    $fields['billing']['billing_email']['type'] = 'email';
    $fields['billing']['billing_phone']['type'] = 'tel';
    $fields['billing']['billing_postcode']['type'] = apply_filters( 'wfc_postcode_field_type', 'tel' );
    $fields['shipping']['shipping_postcode']['type'] = apply_filters( 'wfc_postcode_field_type', 'tel' );

    return $fields;
  }

}

FluidCheckoutFieldTypes::instance();