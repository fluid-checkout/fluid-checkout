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
            'multi-step' => self::$directory_path . 'inc/layouts/multi-step/checkout-layout.php',
            'multi-step-enhanced' => self::$directory_path . 'inc/layouts/multi-step-enhanced/checkout-layout.php',
        ) );
        
        // Get selected layout key and file path
		$selected_checkout_layout_key = get_option( 'wfc_checkout_layout', 'default' );
        $selected_checkout_layout_key = array_key_exists( $selected_checkout_layout_key, $available_checkout_layouts ) ? $selected_checkout_layout_key : 'default';
        
        if ( $selected_checkout_layout_key !== 'default' ) {
            $layout_class_file = $available_checkout_layouts[ $selected_checkout_layout_key ];
            
            // Try load layout class file
            if ( file_exists( $layout_class_file ) ) {
                require_once $layout_class_file;
            }
        }
	}

}

FluidCheckoutLayouts::instance();