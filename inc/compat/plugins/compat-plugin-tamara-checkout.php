<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Tamara Checkout (by Tamara Solution).
 */
class FluidCheckout_TamaraCheckout extends FluidCheckout {

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
		// Payment methods
		add_filter( 'woocommerce_gateway_icon', array( $this, 'maybe_change_payment_gateway_icon_html' ), 20, 2 ); // Use 20 as priority to run after the plugin's filter
	}



	/**
	 * Maybe change the payment method icons.
	 * 
	 * @param  string  $icon_html  Payment method icon HTML.
	 * @param  string  $id         Payment method ID.
	 */
	public function maybe_change_payment_gateway_icon_html( $icon_html, $id = null ) {
		// Bail if target payment method is not selected
		if ( false === strpos( $id, 'tamara' ) ) { return $icon_html; }

		// Bail if required class is not available
		$class_name = 'Tamara\Wp\Plugin\Services\WCTamaraGateway';
		if ( ! class_exists( $class_name ) ) { return $icon_html; }

		// Get class object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $class_name );

		// Bail if class object or method is not available
		if ( ! is_object( $class_object ) || ! method_exists( $class_object, 'getContainer' ) ) { return $icon_html; }

		// Use buffer in case if the plugin outputs HTML out of place
		ob_start();
		// COPIED AND ADAPTED FROM: `WCTamaraGateway::setTamaraIconForPaymentGateway`
		$class_object->getContainer()->getServiceView()->render( 'views/woocommerce/checkout/tamara-checkout-icon', array(
				'siteLocale' => substr( get_locale(), 0, 2 ) ?? 'en',
			)
		);
		$icon_html_buffer = ob_get_clean();

		// Maybe replace the icon HTML with the buffer content
		if ( ! empty( $icon_html_buffer ) ) {
			$icon_html = $icon_html_buffer;
		}

		return $icon_html;
	}

}

FluidCheckout_TamaraCheckout::instance();
