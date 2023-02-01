<?php
use ElementorPro\Modules\Woocommerce\Widgets\Base_Widget;
use ElementorPro\Plugin;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Border;
use Elementor\Repeater;
use Elementor\Group_Control_Background;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class FluidCheckout_ElementorPRO_Checkout extends Base_Widget {

	private $reformatted_form_fields;

	public function get_name() {
		return 'woocommerce-checkout-page';
	}

	public function get_title() {
		return esc_html__( 'Checkout', 'elementor-pro' );
	}

	public function get_icon() {
		return 'eicon-checkout';
	}

	public function get_keywords() {
		return [ 'woocommerce', 'checkout' ];
	}

	public function get_categories() {
		return [ 'woocommerce-elements' ];
	}

	public function get_help_url() {
		return 'https://go.elementor.com/widget-woocommerce-checkout';
	}

	public function get_script_depends() {
		return [
			'wc-checkout',
			'wc-password-strength-meter',
			'selectWoo',
		];
	}

	public function get_style_depends() {
		return [ 'select2' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'General', 'elementor-pro' ),
			]
		);

		// CHANGE: Replace all widget controls with a notice
		$this->add_control(
			'important_note',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw' => wp_kses_post( __( '<p>This widget has been replaced by Fluid Checkout. </p><p>When using Fluid Checkout, the checkout page needs to use the original WooCommerce shortcode <code>[woocommerce_checkout]</code> to work properly.</p><p>For more information, visit the Fluid Checkout documentation.</p>', 'fluid-checkout' ) ),
			]
		);
		// CHANGE: END - Replace all widget controls with a notice

		$this->end_controls_section();
	}


	/**
	 * Reformat Address Field Defaults
	 *
	 * Used with the `get_..._field_defaults()` methods.
	 * Takes the address array and converts it into the format expected by the repeater controls.
	 *
	 * @since 3.5.0
	 *
	 * @param $address
	 * @return array
	 */
	private function reformat_address_field_defaults( $address ) {
		$defaults = [];
		foreach ( $address as $key => $value ) {
			$defaults[] = [
				'field_key' => $key,
				'field_label' => $value['label'],
				'label' => $value['label'],
				'placeholder' => $value['label'],
				'repeater_state' => $value['repeater_state'],
			];
		}

		return $defaults;
	}


	/**
	 * Get Reformatted Form Fields.
	 *
	 * Combines the 3 relevant repeater settings arrays into a one level deep associative array
	 * with the keys that match those that WooCommerce uses for its form fields.
	 *
	 * The result is cached so the conversion only ever happens once.
	 *
	 * @since 3.5.0
	 *
	 * @return array
	 */
	private function get_reformatted_form_fields() {
		if ( ! isset( $this->reformatted_form_fields ) ) {
			$instance = $this->get_settings_for_display();

			// Reformat form repeater field into one usable array.
			$repeater_fields = [
				'billing_details_form_fields',
				'shipping_details_form_fields',
				'additional_information_form_fields',
			];

			$this->reformatted_form_fields = [];

			// Apply other modifications to inputs.
			foreach ( $repeater_fields as $repeater_field ) {
				if ( isset( $instance[ $repeater_field ] ) ) {
					foreach ( $instance[ $repeater_field ] as $item ) {
						if ( ! isset( $item['field_key'] ) ) {
							continue;
						}
						$this->reformatted_form_fields[ $item['field_key'] ] = $item;
					}
				}
			}
		}

		return $this->reformatted_form_fields;
	}

	protected function render() {
		// CHANGE: Removed simulation of guest customer
		// CHANGE: Removed extra actions and filters

		// Display our Widget.
		echo do_shortcode( '[woocommerce_checkout]' );

		// CHANGE: Removed extra actions and filters
		// CHANGE: Removed simulation of guest customer
	}

	public function get_group_name() {
		return 'woocommerce';
	}
}
