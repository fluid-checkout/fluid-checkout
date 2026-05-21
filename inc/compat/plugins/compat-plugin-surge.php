<?php
defined( 'ABSPATH' ) || exit;

class FluidCheckout_SurgeAddon extends FluidCheckout {

	public function __construct() {
		$this->hooks();
	}



	public function hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		add_filter( 'fc_checkout_wrapper_inside_element_custom_attributes', array( $this, 'add_customer_details_element_attributes' ) );

		add_filter( 'surge_get_option_array', array( $this, 'force_change_theme_options' ), 10, 3 );

		add_filter( 'woocommerce_checkout_fields', array( $this, 'label_fields_customization' ), 1100 );
	}



	public function register_assets() {
		wp_register_script(
			'fc-compat-surge-addon-woo-common-input-event-handler',
			FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/surge-addon/woo-common-input-event-handler' ),
			array(),
			NULL,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		wp_add_inline_script(
			'fc-compat-surge-addon-woo-common-input-event-handler',
			'window.addEventListener("load",function(){WooCommonInputEventHandler.init();});'
		);

		wp_register_script(
			'surge-checkout-labels-as-placeholders',
			FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/surge-addon/checkout-labels-as-placeholders' ),
			array( 'jquery', 'surge-addon-js' ),
			NULL,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	public function enqueue_assets() {
		wp_enqueue_script( 'fc-compat-surge-addon-woo-common-input-event-handler' );
	}

	public function maybe_enqueue_assets() {
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) {
			return;
		}

		if ( ! function_exists( 'surge_get_option' ) || 'modern' !== surge_get_option( 'woo-input-style-type' ) ) {
			return;
		}

		$this->enqueue_assets();
	}



	public function force_change_theme_options( $theme_options, $option, $default ) {
		$theme_options['checkout-content-width'] = 'default';
		$theme_options['checkout-layout-type'] = 'default';
		$theme_options['two-step-checkout'] = false;
		$theme_options['checkout-coupon-display'] = false;
		$theme_options['checkout-persistence-form-data'] = false;

		$theme_options['checkout-order-notes-display'] = 'yes';

		$theme_options['order-summary-background-color'] = '';
		$theme_options['payment-option-content-background-color'] = '';

		return $theme_options;
	}



	public function label_fields_customization( $fields ) {
		if ( ! function_exists( 'surge_get_option' ) ) {
			return $fields;
		}

		if (
			FluidCheckout_Steps::instance()->is_checkout_page_or_fragment()
			&& ! is_wc_endpoint_url( 'order-received' )
			&& 'modern' === surge_get_option( 'woo-input-style-type' )
		) {

			$field_types = array(
				'billing',
				'shipping',
				'account',
				'order',
			);

			foreach ( $field_types as $type ) {

				if ( isset( $fields[ $type ] ) && is_array( $fields[ $type ] ) ) {

					foreach ( $fields[ $type ] as $key => $field ) {

						if ( empty( $fields[ $type ][ $key ]['placeholder'] ) ) {
							$fields[ $type ][ $key ]['placeholder'] = $fields[ $type ][ $key ]['label'];

							if (
								isset( $fields[ $type ][ $key ]['required'] )
								&& $fields[ $type ][ $key ]['required']
							) {
								$fields[ $type ][ $key ]['placeholder'] .= ' *';
							}
						}

						if ( isset( $fields[ $type ][ $key ]['label_class'] ) ) {
							$fields[ $type ][ $key ]['label_class'] = array_diff(
								$fields[ $type ][ $key ]['label_class'],
								array( 'screen-reader-text' )
							);
						}
					}
				}
			}
		}

		return $fields;
	}



	public function add_customer_details_element_attributes( $custom_attributes ) {
		if ( ! function_exists( 'surge_get_option' ) ) {
			return $custom_attributes;
		}

		if ( true !== surge_get_option( 'checkout-labels-as-placeholders' ) ) {
			return $custom_attributes;
		}

		$custom_attributes['id'] = 'customer_details';

		return $custom_attributes;
	}

}

FluidCheckout_SurgeAddon::instance();