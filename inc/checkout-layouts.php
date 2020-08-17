<?php
/**
 * Checkout Layouts Loader
 */
class FluidCheckout_CheckoutLayouts extends FluidCheckout {

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
		$active_checkout_layout_key = $this->get_active_checkout_layout_key();
        
        if ( $active_checkout_layout_key !== 'default' ) {
            $available_checkout_layouts = $this->get_available_checkout_layouts();
            $layout_class_file = $available_checkout_layouts[ $active_checkout_layout_key ];
            
            // Try load layout class file
            if ( file_exists( $layout_class_file ) ) {
                require_once $layout_class_file;
            }
        }
    }



    /**
	 * Return active checkout layout key
	 */
	public function get_available_checkout_layouts() {
        return apply_filters( 'wfc_available_checkout_layouts', array(
            'default' => self::$directory_path . 'inc/layouts/default/checkout-default.php',
            'multi-step' => self::$directory_path . 'inc/layouts/multi-step/checkout-multi-step.php',
            'multi-step-enhanced' => self::$directory_path . 'inc/layouts/multi-step-enhanced/checkout-multi-step-enhanced.php',
        ) );
    }
    


    /**
	 * Return active checkout layout key
	 */
	public function get_active_checkout_layout_key() {
        $available_checkout_layouts = $this->get_available_checkout_layouts();

        // Get selected layout key
		$active_checkout_layout_key = get_option( 'wfc_checkout_layout', 'default' );
        $active_checkout_layout_key = array_key_exists( $active_checkout_layout_key, $available_checkout_layouts ) ? $active_checkout_layout_key : 'default';

        return $active_checkout_layout_key;
    }

}

FluidCheckout_CheckoutLayouts::instance();
