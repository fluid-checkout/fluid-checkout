<?php

/**
 * Customizations to the checkout page.
 */
class FluidCheckoutValidation extends FluidCheckout {

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
    add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
  }



  /**
   * Enqueue scripts and styles.
   */
  public function enqueue() {
    // Bail if not on checkout page.
    if( ! is_checkout() || is_order_received_page() ){ return; }

    wp_enqueue_script( 'fluid-checkout-validation-scripts', self::$directory_url.'js/checkout-validation'.self::$asset_version.'.js', array( 'jquery', 'wc-checkout' ), NULL, true );
    wp_localize_script( 
      'fluid-checkout-validation-scripts', 
      'fluidCheckoutValidationVars', 
      apply_filters( 'fluid_checkout_validation_script_settings', 
        array( 
          'required_field_message'  => __( 'This is a required field.', 'woocommerce-fluid-checkout' ),
          'email_field_message'  => __( 'This is not a valid email address.', 'woocommerce-fluid-checkout' ),
          'confirmation_field_message'  => __( 'This does not match the related field value.', 'woocommerce-fluid-checkout' ),
        )
      )
    );

    wp_enqueue_style( 'fluid-checkout-validation-style', self::$directory_url.'css/checkout-validation'.self::$asset_version.'.css', null, NULL );
  }

}

FluidCheckoutValidation::instance();