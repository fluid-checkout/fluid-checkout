<?php

/**
 * Customizations to the checkout page.
 */
class FluidCheckoutPage extends FluidCheckout {

  public function __construct() {
    $this->hooks();
  }

  public function hooks() {
    
    add_filter( 'woocommerce_locate_template', array( $this, 'wfc_woocommerce_locate_template' ), 10, 3 );
    
    add_filter( 'woocommerce_checkout_fields' , array( $this, 'change_number_field_types' ), 5 );
    
    
    // TODO: Move these hook changes to a site specific plugin
    add_filter( 'woocommerce_checkout_fields' , array( $this, 'set_fields_description_placeholder' ), 5 );
    add_filter( 'woocommerce_checkout_fields' , array( $this, 'change_billing_fields' ), 10 );
    add_filter( 'woocommerce_checkout_fields' , array( $this, 'change_shipping_fields' ), 10 );

    // TODO: Move these hook changes to a site specific plugin
    // Move login for to inside it's step
    remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
    add_action( 'wfc_checkout_login_form', 'woocommerce_checkout_login_form', 10 );

    // TODO: Move this hook to a site specific plugin
    // add_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
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
   * Change Shipping Fields.
   */
  public function change_billing_fields( $fields ) {
    // TODO: Move this function to a site specific plugin

    // Set fields order
    $billing_details_order = array(
      'billing_first_name', 
      'billing_last_name', 
      'billing_company', 
      'billing_phone',
      'billing_email',
    );
    $billing_address_order = array(
      'billing_address_1', 
      'billing_address_2',
      'billing_city',
      'billing_state',
      'billing_postcode', 
      'billing_country',
    );

    // Get fields in the order set above
    $billing_details = array();
    foreach($billing_details_order as $field) {
      $billing_details[$field] = $fields['billing'][$field];
    }

    // Get fields in the order set above
    $billing_address = array();
    foreach($billing_address_order as $field) {
      $billing_address[$field] = $fields['billing'][$field];
    }

    // Add new field sections for billing fields
    $fields['billing_details'] = $billing_details;
    $fields['billing_address'] = $billing_address;

    // Remove original section for billind fields
    unset($fields['billing']);

    // Fix: Make sure country display in the correct order
    $fields['billing_address']['billing_country']['priority'] = 100;

    return $fields;
  }



  /**
   * Change Shipping Fields.
   */
  public function change_shipping_fields( $fields ) {
    // TODO: Move this function to a site specific plugin
    
    // Set fields order
    $shipping_order = array(
      'shipping_first_name',
      'shipping_last_name',
      'shipping_company',
      'shipping_address_1',
      'shipping_address_2',
      'shipping_city',
      'shipping_state',
      'shipping_postcode',
      'shipping_country',
    );

    // Get fields in the order set above
    $shipping_fields = array();
    foreach($shipping_order as $field) {
      $shipping_fields[$field] = $fields['shipping'][$field];
    }

    // Replace fields
    $fields['shipping'] = $shipping_fields;

    // Fix: Make sure country display in the correct order
    $fields['shipping']['shipping_country']['priority'] = 100;

    return $fields;
  }



  /**
   * Change the type of number input fields
   * to display a more appropriate keyboard on mobile devices.
   */
  public function change_number_field_types( $fields ) {
    $fields['billing']['billing_email']['type'] = 'email';
    $fields['billing']['billing_phone']['type'] = 'tel';
    $fields['billing']['billing_postcode']['type'] = 'tel';
    $fields['shipping']['shipping_postcode']['type'] = 'tel';

    return $fields;
  }



  /**
   * Add description to some fields.
   */
  public function set_fields_description_placeholder( $fields ) {
    // TODO: Move this function to a site specific plugin
    $fields['billing']['billing_email']['description'] = __( 'Order and Tracking number will be sent to this email address.', 'la' );
    $fields['billing']['billing_address_2']['description'] = __( 'Important: Do not forget appartment, condo or BLD number if applicable.', 'la' );
    $fields['shipping']['shipping_address_2']['description'] = __( 'Important: Do not forget appartment, condo or BLD number if applicable.', 'la' );
    $fields['billing']['billing_address_2']['placeholder'] = __( 'Apt, condo or BLD numbers', 'la' );

    return $fields;
  }

}

$wsc_checkout_page = new FluidCheckoutPage();