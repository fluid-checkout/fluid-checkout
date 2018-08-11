<?php

/**
 * Customizations to the checkout page.
 */
class FluidCheckoutPage extends FluidCheckout {

  public function __construct() {
    $this->hooks();
  }

  public function hooks() {
    
    // Template loader
    add_filter( 'woocommerce_locate_template', array( $this, 'wfc_woocommerce_locate_template' ), 10, 3 );
    
    // Checkout field types enhancement for mobile
    add_filter( 'woocommerce_checkout_fields' , array( $this, 'change_number_field_types' ), 5 );
  }



  /*
   * Use our custom woo checkout form template
   */
  public function wfc_woocommerce_locate_template( $template, $template_name, $template_path ) {
   
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



  /**
   * Change the types of number input fields
   * to display a more appropriate keyboard on mobile devices.
   */
  public function change_number_field_types( $fields ) {
    $fields['billing']['billing_email']['type'] = 'email';
    $fields['billing']['billing_phone']['type'] = 'tel';
    $fields['billing']['billing_postcode']['type'] = 'tel';
    $fields['shipping']['shipping_postcode']['type'] = 'tel';

    return $fields;
  }



}

$wsc_checkout_page = new FluidCheckoutPage();