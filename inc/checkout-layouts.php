<?php
/**
 * Checkout Layouts Loader
 */
class FluidCheckoutLayouts extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->init();
	}



	/**
	 * Dynamically loads checkout layout
	 */
	public function init() {
        $available_checkout_layouts = apply_filters( 'wfc_available_checkout_layouts', array(
            'default' => self::$directory_path . 'inc/layouts/default/checkout-layout.php',
            'multi-step-1' => self::$directory_path . 'inc/layouts/multi-step-1/checkout-layout.php',
        ) );
        
        // Get selected layout key and file path
		$selected_checkout_layout_key = get_option( 'wfc_checkout_layout', 'default' );
        $selected_checkout_layout_key = array_key_exists( $selected_checkout_layout_key, $available_checkout_layouts ) ? $selected_checkout_layout_key : 'default';
        $layout_class_file = $available_checkout_layouts[ $selected_checkout_layout_key ];
        
        // Try load layout class file
        if ( file_exists( $layout_class_file ) ) {
            require_once $layout_class_file;
        }
        // Load default layout class file not found
        else {
            require_once $available_checkout_layouts[ 'default' ];
        }
	}

}

FluidCheckoutLayouts::instance();