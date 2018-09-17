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
    add_action( 'wp_enqueue_scripts', array( $this, 'scripts_styles' ) );
  }



  /**
   * scripts_styles function.
   *
   * @access public
   * @return void
   */
  public function scripts_styles() {
    // Bail if not on checkout page.
    if( !is_checkout() || is_order_received_page() ){ return; }

    // TODO: Enable js minification.
    // TODO: Move $min to main plugin class (DRY)
    // $min = '.min';
    $min = '';

    if ( defined('SCRIPT_DEBUG') && true === SCRIPT_DEBUG ) {
      $min = '';
    }
      
    wp_enqueue_script( 'fluid-checkout-validation-scripts', untrailingslashit( self::$directory_url )."/js/checkout-validation$min.js", array( 'jquery' ), self::VERSION, true );
  }

}

FluidCheckoutValidation::instance();