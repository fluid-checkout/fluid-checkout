<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Packlink PRO Shipping (by Packlink Shipping S.L.).
 */
class FluidCheckout_PacklinkPROShipping extends FluidCheckout {

	/**
	 * The shipping method id.
	 */
	public const SHIPPING_METHOD_ID = 'packlink_shipping_method';



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
		// Shipping methods
		add_filter( 'fc_shipping_method_option_image_html', array( $this, 'maybe_change_shipping_method_option_image_html' ), 10, 2 );

		// Alter class method from Packlink PRO
		$this->remove_action_for_class( 'woocommerce_after_shipping_rate', array( 'Packlink\WooCommerce\Components\Checkout\Checkout_Handler', 'after_shipping_rate' ), 10 );
		add_action( 'woocommerce_after_shipping_rate', array( $this, 'alter_packlink_after_shipping_rate' ), 10, 2 );
	}



	/**
	 * Alter `after_shipping_rate` method from Packlink.
	 * 
	 * @param  WC_Shipping_Rate  $rate   The shipping rate.
	 * @param  int               $index  The index of the shipping rate.
	 */
	function alter_packlink_after_shipping_rate( $rate, $index ) {
		// Bail if class is not available
		if ( ! class_exists( 'Packlink\WooCommerce\Components\Checkout\Checkout_Handler' ) ) { return; }

		$handler = new Packlink\WooCommerce\Components\Checkout\Checkout_Handler();

		// Bail if method is not available
		if ( ! method_exists( $handler, 'after_shipping_rate' ) ) { return; }

		// Get default method output
		ob_start();
		$handler->after_shipping_rate( $rate, $index );
		$output = ob_get_clean();

		// Remove image and hidden input field
		$output = preg_replace( '/<img[^>]+>/', '', $output );
		$output = preg_replace( '/<input[^>]+name="packlink_image_url"[^>]+>/', '', $output );

		// Print the output
		echo $output;
	}



	/**
	 * Check whether the shipping method ID is Packlink PRO.
	 * 
	 * @param  string  $shipping_method_id  The shipping method ID.
	 */
	public function is_shipping_method_packlink( $shipping_method_id ) {
		return 0 === strpos( $shipping_method_id, self::SHIPPING_METHOD_ID );
	}



	/**
	 * Check whether Packlink Pro is selected as a shipping method.
	 */
	public function is_shipping_method_selected() {
		$is_selected = false;

		// Check chosen shipping method
		$packages = WC()->shipping()->get_packages();
		foreach ( $packages as $i => $package ) {
			// Check if a target shipping method is selected
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			if ( $chosen_method && $this->is_shipping_method_packlink( $chosen_method ) ) {
				$is_selected = true;
				break;
			}
		}

		return $is_selected;
	}



	/**
	 * Maybe change the shipping method option image HTML.
	 * 
	 * @param  string  $html     The HTML of the shipping method option image.
	 * @param  object  $method   The shipping method object.
	 */
	public function maybe_change_shipping_method_option_image_html( $html, $method ) {
		// Bail if not a shipping method from this plugin
		if ( ! $this->is_shipping_method_packlink( $method->id ) ) { return $html; }

		// Bail if class is not available
		if ( ! class_exists( 'Packlink\WooCommerce\Components\ShippingMethod\Shipping_Method_Helper' ) ) { return $html; }

		// Bail if method is not available
		if ( ! method_exists( 'Packlink\WooCommerce\Components\ShippingMethod\Shipping_Method_Helper', 'get_packlink_shipping_method' ) ) { return $html; }

		// Get plugin's shipping method object
		$method_id = $method->get_instance_id();
		$packling_method = Packlink\WooCommerce\Components\ShippingMethod\Shipping_Method_Helper::get_packlink_shipping_method( $method_id );

		// Bail if method is not available
		if ( ! method_exists( $packling_method, 'isDisplayLogo' ) ) { return $html; }

		// Bail if image should not be displayed
		if ( ! $packling_method->isDisplayLogo() ) { return $html; }

		// Get image URL for the chosen carrier
		$image_url = $packling_method->getLogoUrl();

		// If no image is available, use the default one
		if ( ! $image_url ) {
			$image_url = trailingslashit( plugins_url() ) . 'packlink-pro-shipping/resources/images/box.svg';
		}

		// Define image HTML
		$html = '<img class="shipping_logo" src="' . $image_url . '" alt="Packlink PRO Shipping"/>';

		return $html;
	}

}

FluidCheckout_PacklinkPROShipping::instance();
