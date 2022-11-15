<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Minimog (by ThemeMove).
 */
class FluidCheckout_ThemeCompat_Minimog extends FluidCheckout {

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
		// Scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'deregister_woocommerce_scripts' ), 20 );
		add_action( 'wp_enqueue_scripts', array( FluidCheckout_Enqueue::instance(), 'replace_woocommerce_scripts' ), 20 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false' );
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 20 );

		// Remove checkout payment info heading
		add_filter( 'fc_content_section_class', array( $this, 'remove_checkout_payment_heading' ), 20 );

		
	}

	 /**
	 * remove checkout payment info heading added by the theme
	 */
	public function remove_checkout_payment_heading() {
		remove_action( 'woocommerce_checkout_after_order_review',array(Minimog\Woo\Checkout::instance(),'template_checkout_payment_title'),10 );
	}

	/**
	 * Remove WooCommerce scripts.
	 */
	public function deregister_woocommerce_scripts() {
		wp_deregister_script( 'woocommerce' );
		wp_deregister_script( 'wc-country-select' );
		wp_deregister_script( 'wc-address-i18n' );
		wp_deregister_script( 'wc-checkout' );
	}
	

	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	 public function change_fc_content_section_class( $class ) {

	 	return $class . ' container-wide';
	 }

	
   

}

FluidCheckout_ThemeCompat_Minimog::instance();
