<?php

/**
 * Customizations to the checkout page.
 */
class FluidCheckoutPage extends FluidCheckout {

  public function __construct() {
    $this->hooks();
  }

  public function hooks() {
    
    add_filter( 'woocommerce_locate_template', array( $this, 'checkout_woocommerce_locate_template' ), 10, 3 );

    // Move login for to inside it's step
    remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
    add_action( 'wfc_before_login_form', 'woocommerce_checkout_login_form', 10 );


    // add_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
  }



  /*
   * Use our custom woo checkout form template
   */
  public function checkout_woocommerce_locate_template( $template, $template_name, $template_path ) {
   
    global $woocommerce;
   
    $_template = $template;
   
    if ( ! $template_path ) $template_path = $woocommerce->template_url;
   
    // Get plugin path
    $plugin_path  = untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/';
   
    // Look within passed path within the theme
    $template = locate_template(
      array(
        $template_path . $template_name,
        $template_name
      )
    );
   
    // Modification: Get the template from this plugin, if it exists
    if ( file_exists( $plugin_path . $template_name ) ) {
      $template = $plugin_path . $template_name;
    }
   
    // Use default template
    if ( ! $template ){
      $template = $_template;
    }
   
    // Return what we found
    return $template;
  }

}

$wsc_checkout_page = new FluidCheckoutPage();