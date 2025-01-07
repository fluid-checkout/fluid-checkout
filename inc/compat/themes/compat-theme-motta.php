<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Motta (by Uixthemes).
 */
class FluidCheckout_ThemeCompat_Motta extends FluidCheckout {

	/**
	 * Class name for the theme which this compatibility class is related to.
	 */
	public const CLASS_NAME = '\Motta\WooCommerce\Checkout';



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
		// Bail if class method is not available
		if ( ! method_exists( self::CLASS_NAME, 'instance' ) ) { return $is_verified; }

		// Get class object
		$class_object = call_user_func( array( self::CLASS_NAME, 'instance' ) );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// Product thumbnails
		remove_filter( 'woocommerce_cart_item_name', array( $class_object, 'review_product_name_html' ), 10, 3);
	}

}

FluidCheckout_ThemeCompat_Motta::instance();
