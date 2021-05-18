<?php

/**
 * Checkout admin options.
 */
class FluidCheckout_Admin extends FluidCheckout {

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
		// WooCommerce Settings
        add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_pages' ), 50 );
	}



    /**
     * Add new WooCommerce settings pages/tabs.
     */
    public function add_settings_pages( $settings ) {
        $settings[] = include self::$directory_path . 'inc/admin/admin-checkout.php';
        return $settings;
    }

}

FluidCheckout_Admin::instance();
