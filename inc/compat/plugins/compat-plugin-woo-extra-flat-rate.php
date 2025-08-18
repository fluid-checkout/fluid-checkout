<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Flat Rate Shipping Method for WooCommerce (by Dotstore).
 */
class FluidCheckout_WooExtraFlatRate extends FluidCheckout {

	/**
	 * Class name for the plugin which this compatibility class is related to.
	 */
	public const PUBLIC_CLASS_NAME = 'Advanced_Flat_Rate_Shipping_For_WooCommerce_Pro_Public';


	/**
	 * Plugin's public class object.
	 */
	public $public_class_object;



	/**
	 * __construct function.
	 */
	public function __construct( ) {
		// Maybe set class object from the plugin
		if ( class_exists( self::PUBLIC_CLASS_NAME ) ) {
			// Get object
			$this->public_class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( self::PUBLIC_CLASS_NAME );
		}

		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Bail if class object is not available
		if ( ! is_object( $this->public_class_object ) ) { return; }

		// Shipping method tooltip
		$this->maybe_change_shipping_method_tooltip_position();

		// Shipping method subtitle
		add_action( 'woocommerce_after_shipping_rate', array( $this, 'maybe_add_shipping_method_subtitle' ), 10 );

		// Shipping method description
		add_filter( 'fc_shipping_method_option_description' , array( $this, 'maybe_add_estimated_delivery_to_shipping_method_description' ), 15, 2 ); // Set 15 as priority to add after WooCommerce description

		// Order summary
		remove_filter( 'woocommerce_cart_shipping_method_full_label', array( $this->public_class_object, 'afrsm_pro_wc_cart_shipping_method_label_callback' ), 10, 2 );
	}



	/**
	 * Maybe Change shipping method tooltip position.
	 */
	public function maybe_change_shipping_method_tooltip_position() {
		// Bail if class object or its method is not available
		if ( ! is_object( $this->public_class_object ) || ! method_exists( $this->public_class_object, 'afrsm_add_tooltip_and_subtitle_callback' ) ) { return; }

		// Change tooltip position
		remove_filter( 'woocommerce_after_shipping_rate', array( $this->public_class_object, 'afrsm_add_tooltip_and_subtitle_callback' ), 10 );
		add_filter( 'fc_shipping_method_option_label_markup', array( $this, 'maybe_add_tooltip_icon_to_shipping_method_label' ), 10, 2 );
	}



	/**
	 * Get tooltip type for shipping method.
	 *
	 * @param  object  $method  The shipping method object.
	 */
	public function get_tooltip_type_for_shipping_method( $method ) {
		// Define default tooltip type
		$tooltip_type = 'tooltip';

		// Get shipping method ID parts
		$method_id_parts = explode( ':', $method->id );

		// Maybe get tooltip type from post meta
		if ( isset( $method_id_parts[ 1 ] ) ) {
			$tooltip_type = get_post_meta( $method_id_parts[ 1 ], 'sm_tooltip_type', true );
		}

		return $tooltip_type;
	}



	/**
	 * Maybe add shipping method subtitle.
	 * Required to re-add the subtitle functionality removed by the `maybe_change_shipping_method_tooltip_position` function.
	 *
	 * @param  object  $method  The shipping method object.
	 */
	public function maybe_add_shipping_method_subtitle( $method ) {
		// Bail if not a target shipping method
		if ( false === strpos( $method->id, 'advanced_flat_rate_shipping' ) ) { return; }

		// Get tooltip type
		$tooltip_type = $this->get_tooltip_type_for_shipping_method( $method );

		// Bail if tooltip type is not subtitle
		if ( 'subtitle' !== $tooltip_type ) { return; }

		// Bail if class object or its method is not available
		if ( ! is_object( $this->public_class_object ) || ! method_exists( $this->public_class_object, 'afrsm_add_tooltip_and_subtitle_callback' ) ) { return; }

		// Output the subtitle
		$this->public_class_object->afrsm_add_tooltip_and_subtitle_callback( $method );
	}



	/**
	 * Maybe Add tooltip icon to shipping method label.
	 *
	 * @param  string  $label   The shipping method label.
	 * @param  object  $method  The shipping method object.
	 */
	public function maybe_add_tooltip_icon_to_shipping_method_label( $label, $method ) {
		// Bail if class object or its method is not available
		if ( ! is_object( $this->public_class_object ) || ! method_exists( $this->public_class_object, 'afrsm_add_tooltip_and_subtitle_callback' ) ) { return $label; }

		// Maybe get tooltip type from post meta
		$tooltip_type = $this->get_tooltip_type_for_shipping_method( $method );

		// Bail if not a tooltip
		if ( 'tooltip' !== $tooltip_type ) { return $label; }

		// Initialize variable
		$tooltip = '';

		// Get tooltip HTML from the plugin
		ob_start();
		$this->public_class_object->afrsm_add_tooltip_and_subtitle_callback( $method );
		$tooltip = ob_get_clean();

		// Insert tooltip before closing `span` tag
		if ( ! empty( $tooltip ) ) {
			$label = str_replace( '</span>', $tooltip . '</span>', $label );
		}

		return $label;
	}



	/**
	 * Maybe add estimated delivery to shipping method description.
	 * 
	 * @param  string            $shipping_method_description  Shipping method description.
	 * @param  WC_Shipping_Rate  $method                       Shipping method rate data.
	 */
	public function maybe_add_estimated_delivery_to_shipping_method_description( $shipping_method_description, $method ) {
		// Bail if not a target shipping method
		if ( false === strpos( $method->id, 'advanced_flat_rate_shipping' ) ) { return $shipping_method_description; }

		// Get shipping method ID parts
		$method_id_parts = explode( ':', $method->id );

		// Maybe get estimated delivery from post meta
		$estimated_delivery = '';
		if ( isset( $method_id_parts[ 1 ] ) ) {
			$estimated_delivery = get_post_meta( $method_id_parts[ 1 ], 'sm_estimation_delivery', true );
		}

		// Maybe add estimated delivery to shipping method description
		if ( ! empty( $estimated_delivery ) ) {
			// Maybe add line break to existing description
			if ( ! empty( $shipping_method_description ) ) {
				$shipping_method_description .= ' <br>'; // Intentionally add a space before `<br>`
			}

			// Add estimated delivery
			$shipping_method_description .= $estimated_delivery;
		}

		return $shipping_method_description;
	}

}

FluidCheckout_WooExtraFlatRate::instance();
