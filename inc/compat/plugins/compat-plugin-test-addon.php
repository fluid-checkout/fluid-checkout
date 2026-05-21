<?php
defined( 'ABSPATH' ) || exit;

class FluidCheckout_TestAddon extends FluidCheckout
{

	public function __construct() {
		$this->hooks();
	}

	public function hooks()
	{
		add_action(
			'wp_enqueue_scripts',
			array( $this, 'register_assets' ),
			5
		);

		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_assets' ], 10 );

		add_filter(
			'fc_checkout_wrapper_inside_element_custom_attributes',
			array( $this, 'add_customer_details_element_attributes' )
		);

		add_filter( 'test_get_option_array', array(
			$this,
			'force_change_theme_options'
		), 10, 3 );

		add_filter(
			'woocommerce_checkout_fields',
			array( $this, 'label_fields_customization' ),
			1100
		);
	}

	public function maybe_enqueue_assets()
	{
		$is_checkout = FluidCheckout_Steps::instance()->is_checkout_page_or_fragment();

		if ( ! $is_checkout ) {
			return;
		}

		if (
			! function_exists( 'test_get_option' ) ||
			test_get_option( 'woo-input-style-type' ) !== 'modern'
		) {
			return;
		}

		$this->enqueue_assets();
	}

	public function enqueue_assets() {
		wp_enqueue_script(
			'fc-compat-test-addon-woo-common-input-event-handler'
		);
	}

	public function register_assets()
	{
		$enqueue = FluidCheckout_Enqueue::instance();

		wp_register_script(
			'fc-compat-test-addon-woo-common-input-event-handler',
			$enqueue->get_script_url(
				'js/compat/plugins/test-addon/woo-common-input-event-handler'
			),
			array(),
			null,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		wp_add_inline_script(
			'fc-compat-test-addon-woo-common-input-event-handler',
			'window.addEventListener("load",function(){WooCommonInputEventHandler.init();});'
		);

		wp_register_script(
			'test-checkout-labels-as-placeholders',
			$enqueue->get_script_url(
				'js/compat/plugins/test-addon/checkout-labels-as-placeholders'
			),
			array( 'jquery', 'test-addon-js' ),
			null,
			array(
				'in_footer' => true,
				'strategy' => 'defer',
			)
		);
	}

	public function force_change_theme_options(
		$theme_options,
		$option,
		$default
	) {
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

	public function label_fields_customization( $fields )
	{
		if ( ! function_exists( 'test_get_option' ) ) {
			return $fields;
		}

		if (
			! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment()
			|| is_wc_endpoint_url( 'order-received' )
			|| test_get_option( 'woo-input-style-type' ) !== 'modern'
		) {
			return $fields;
		}

		$groups = array(
			'billing',
			'shipping',
			'account',
			'order',
		);

		foreach ( $groups as $group ) {

			if (
				! isset( $fields[ $group ] ) ||
				! is_array( $fields[ $group ] )
			) {
				continue;
			}

			foreach ( $fields[ $group ] as $field_key => $field_data ) {

				if ( empty( $field_data['placeholder'] ) ) {
					$placeholder = $field_data['label'];

					if (
						! empty( $field_data['required'] )
					) {
						$placeholder .= ' *';
					}

					$fields[ $group ][ $field_key ]['placeholder'] = $placeholder;
				}

				if ( isset( $field_data['label_class'] ) ) {
					$fields[ $group ][ $field_key ]['label_class']
						= array_diff(
							$field_data['label_class'],
							array( 'screen-reader-text' )
						);
				}
			}
		}

		return $fields;
	}

	public function add_customer_details_element_attributes(
		$custom_attributes
	) {
		if ( ! function_exists( 'test_get_option' ) ) {
			return $custom_attributes;
		}

		if ( test_get_option( 'checkout-labels-as-placeholders' ) !== true ) {
			return $custom_attributes;
		}

		$custom_attributes['id'] = 'customer_details';

		return $custom_attributes;
	}
}

FluidCheckout_TestAddon::instance();
