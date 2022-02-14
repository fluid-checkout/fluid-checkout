<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Flexible Shipping (by WP Desk).
 */
class FluidCheckout_FlexibleShipping extends FluidCheckout {

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
		// Replace shipping method description
		$this->remove_action_for_class( 'woocommerce_after_shipping_rate', array( 'WPDesk\FS\TableRate\ShippingMethod\MethodDescription', 'display_description_if_present' ), 10 );
		add_action( 'woocommerce_after_shipping_rate', array( $this, 'display_description_if_present' ), 10, 2 );

		// Add substep review text lines
		add_filter( 'fc_substep_shipping_method_text_lines', array( $this, 'add_substep_text_lines_shipping_method' ), 10 );
	}



	/**
	 * Get the shipping method description.
	 * 
	 * @param  WC_Shipping_Rate  $method  The shipping method object.
	 */
	public function get_method_description( $method ) {
		$meta_data = $method->get_meta_data();

		if ( isset( $meta_data[ WPDesk\FS\TableRate\ShippingMethod\RateCalculator::DESCRIPTION_BASE64ENCODED ] ) && ! empty( $meta_data[ WPDesk\FS\TableRate\ShippingMethod\RateCalculator::DESCRIPTION_BASE64ENCODED ] ) ) {
			$description = base64_decode( $meta_data[ WPDesk\FS\TableRate\ShippingMethod\RateCalculator::DESCRIPTION_BASE64ENCODED ] );

			if ( $description ) {
				return $description;
			}
		}

		if ( isset( $meta_data[ WPDesk\FS\TableRate\ShippingMethod\RateCalculator::DESCRIPTION ] ) ) {
			return $meta_data[ WPDesk\FS\TableRate\ShippingMethod\RateCalculator::DESCRIPTION ];
		}

		return '';
	}



	/**
	 * Check whether the shipping method description should be displayed.
	 * 
	 * @param  WC_Shipping_Rate  $method  The shipping method object.
	 */
	public function should_display_method_description( $method ) {
		return in_array(
			$method->get_method_id(),
			array(
				WPDesk_Flexible_Shipping::METHOD_ID,
				WPDesk\FS\TableRate\ShippingMethodSingle::SHIPPING_METHOD_ID,
			),
			true
		);
	}



	/**
	 * Output the shipping method display description.
	 * 
	 * @param  WC_Shipping_Rate  $method  The shipping method object.
	 * @param  int               $index   The shipping method index.
	 */
	public function display_description_if_present( $method, $index ) {
		if ( ! $method instanceof WC_Shipping_Rate || ! $this->should_display_method_description( $method ) ) {
			return;
		}

		$description = $this->get_method_description( $method );

		if ( '' !== $description ) {
			$method_description_element = apply_filters( 'fc_shipping_method_description_html_element', 'small' );
			echo wp_kses_post( "<{$method_description_element} class=\"shipping-method__option-description shipping-method-description\">{$description}</{$method_description_element}>" );
		}
	}



	/**
	 * Add the shipping methods substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_shipping_method( $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		$packages = WC()->shipping()->get_packages();

		foreach ( $packages as $i => $package ) {
			$available_methods = $package['rates'];
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			$method = $available_methods && array_key_exists( $chosen_method, $available_methods ) ? $available_methods[ $chosen_method ] : null;
			
			if ( $method ) {
				$description = $this->get_method_description( $method );
				if ( ! empty( $description ) ) {
					$review_text_lines[] = $this->get_method_description( $method );;
				}
			}
		}

		return $review_text_lines;
	}

}

FluidCheckout_FlexibleShipping::instance();
