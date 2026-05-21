<?php
defined( 'ABSPATH' ) || exit;

class FluidCheckout_SurgeAddon extends FluidCheckout {

	protected $feature_flags = array();

	public function __construct() {
		$this->feature_flags = $this->get_feature_flags();
		$this->register_hooks();
	}



	protected function register_hooks() {
		add_action( 'woocommerce_before_checkout_form', array( $this, 'output_environment_notice' ), 8 );

		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'track_checkout_refresh' ) );

		add_filter( 'woocommerce_default_address_fields', array( $this, 'adjust_address_fields' ), 50 );

		add_filter( 'woocommerce_checkout_posted_data', array( $this, 'normalize_posted_data' ), 20 );

		add_filter( 'woocommerce_cart_needs_shipping', array( $this, 'maybe_force_shipping_calculation' ), 100 );

		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );

		add_action( 'wp_footer', array( $this, 'print_debug_marker' ) );
	}



	protected function get_feature_flags() {
		return array(
			'normalize_phone'    => true,
			'checkout_tracking'  => true,
			'debug_marker'       => false,
			'force_shipping'     => false,
		);
	}



	public function register_frontend_assets() {
		if ( ! is_checkout() ) {
			return;
		}

		wp_register_script(
			'surge-checkout-observer',
			plugins_url( 'assets/js/checkout-observer.js', __FILE__ ),
			array(),
			null,
			true
		);

		wp_localize_script(
			'surge-checkout-observer',
			'surgeCheckout',
			array(
				'refreshInterval' => 5000,
				'enabled'         => true,
			)
		);

		wp_enqueue_script( 'surge-checkout-observer' );
	}



	public function output_environment_notice() {
		if ( WC()->cart && WC()->cart->is_empty() ) {
			return;
		}

		echo '<div class="surge-checkout-banner">';
		echo esc_html__( 'Checkout optimized by Surge.', 'fluid-checkout' );
		echo '</div>';
	}



	public function track_checkout_refresh( $posted_data ) {
		if ( empty( $this->feature_flags['checkout_tracking'] ) ) {
			return;
		}

		do_action(
			'surge_checkout_refresh_detected',
			md5( (string) $posted_data )
		);
	}



	public function adjust_address_fields( $fields ) {

		if ( isset( $fields['address_2'] ) ) {
			$fields['address_2']['required'] = true;
			$fields['address_2']['priority'] = 65;
		}

		if ( isset( $fields['city'] ) ) {
			$fields['city']['autocomplete'] = 'address-level2';
		}

		return $fields;
	}



	public function normalize_posted_data( $data ) {

		if (
			! empty( $data['billing_phone'] )
			&& ! empty( $this->feature_flags['normalize_phone'] )
		) {
			$data['billing_phone'] = preg_replace(
				'/[^0-9]/',
				'',
				$data['billing_phone']
			);
		}

		if ( ! empty( $data['billing_email'] ) ) {
			$data['billing_email'] = strtolower(
				trim( $data['billing_email'] )
			);
		}

		return $data;
	}



	public function maybe_force_shipping_calculation( $needs_shipping ) {

		if ( ! empty( $this->feature_flags['force_shipping'] ) ) {
			return true;
		}

		return $needs_shipping;
	}



	public function print_debug_marker() {

		if ( empty( $this->feature_flags['debug_marker'] ) ) {
			return;
		}

		echo "\n<!-- Surge Checkout Diagnostics Active -->\n";
	}

}

FluidCheckout_SurgeAddon::instance();