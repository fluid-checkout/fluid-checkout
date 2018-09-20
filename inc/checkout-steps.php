<?php

/**
 * Customizations to the checkout page.
 */
class FluidCheckoutSteps extends FluidCheckout {

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
    add_filter( 'woocommerce_order_button_html', array( $this, 'add_back_button_order_button_html' ), 20 );
  }



  /**
   * scripts_styles function.
   *
   * @access public
   * @return void
   */
  public function scripts_styles() {
    // Bail if not on checkout page.
    if( ! is_checkout() || is_order_received_page() ){ return; }

    // TODO: Enable js minification.
    // $min = '.min';
    $min = ''; 

    if ( defined('SCRIPT_DEBUG') && true === SCRIPT_DEBUG ) {
      $min = '';
    }
      
    wp_enqueue_script( 'fluid-checkout-steps-scripts', untrailingslashit( self::$directory_url )."/js/checkout-steps$min.js", array( 'jquery' ), self::VERSION, true );

    wp_enqueue_style( 'fluid-checkout-steps-style', untrailingslashit( self::$directory_url )."/css/checkout-steps$min.css", null, self::VERSION );
  }



  /**
   * Add back button html to place order button on checkout.
   * @param [String] $button_html Place Order button html.
   */
  public function add_back_button_order_button_html( $button_html ) {
    // TODO: Remove svg icon and theme specific classes from button
    $actions_html = '<div class="wfc-actions"><a href="#wfc-wrapper" class="wfc-prev button button-grey-clear button-icon button-icon--left button--big">Back <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-left"><polyline points="15 18 9 12 15 6"></polyline></svg></a> ' . $button_html . '</div>';
    return $actions_html;
  }
  

}

FluidCheckoutSteps::instance();