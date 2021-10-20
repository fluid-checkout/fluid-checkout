<?php
defined( 'ABSPATH' ) || exit;

/**
 * Add customizations of the checkout page for Local Pickup shipping methods.
 */
class FluidCheckout_CheckoutLocalPickup extends FluidCheckout {

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
		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Bail if not on checkout or cart page or doing AJAX call
		if ( ! function_exists( 'is_checkout' ) || ( ! is_checkout() && ! is_cart() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) ) { return; }

		// Hide shipping address for local pickup
		if ( $this->is_local_pickup_available() ) {
			remove_action( 'fc_output_step_shipping', array( FluidCheckout_Steps::instance(), 'output_substep_shipping_address' ), 10 );
			remove_action( 'fc_output_step_shipping', array( FluidCheckout_Steps::instance(), 'output_substep_shipping_method' ), 20 );
			add_action( 'fc_output_step_shipping', array( FluidCheckout_Steps::instance(), 'output_substep_shipping_method' ), 10 );
			add_action( 'fc_output_step_shipping', array( FluidCheckout_Steps::instance(), 'output_substep_shipping_address' ), 20 );
			add_filter( 'fc_substep_shipping_address_attributes', array( $this, 'change_substep_attributes_shipping_address' ), 10 );
			add_action( 'fc_checkout_after_step_shipping_fields', array( $this, 'output_substep_state_hidden_fields_shipping_fields' ), 10 );
			add_action( 'fc_checkout_after_step_shipping_fields', array( $this, 'maybe_output_shipping_address_text' ), 10 );
			add_filter( 'woocommerce_cart_needs_shipping_address', array( $this, 'maybe_change_needs_shipping_address' ), 10 );
			add_filter( 'fc_substep_title_shipping_address', array( $this, 'maybe_change_shipping_address_substep_title' ), 50 );
			add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_shipping_address_substep_title_fragment' ), 10 );
			add_filter( 'fc_substep_shipping_address_text', array( $this, 'change_substep_text_shipping_address' ), 50 );
		}
	}



	/**
	 * Determines if a shipping address is needed depending on the shipping method selected.
	 *
	 * @param   bool  $needs_shipping_address  Whether the cart needs a shipping address or not.
	 */
	public function maybe_change_needs_shipping_address( $needs_shipping_address ) {
		// Hides shipping addresses for `local_pickup`.
		if ( $this->is_shipping_method_local_pickup_selected() ) {
			return false;
		}

		return $needs_shipping_address;
	}



	/**
	 * Change the shipping address substep attributes to make it non-editable when a local pickup shipping method is selected.
	 *
	 * @param   array  $substep_attributes  HTML attributes for the substep element.
	 */
	public function change_substep_attributes_shipping_address( $substep_attributes ) {
		$substep_attributes = array_merge( $substep_attributes, array(
			'data-substep-editable' => ! $this->is_shipping_method_local_pickup_selected() ? 'yes' : 'no',
		) );

		return $substep_attributes;
	}
	
	/**
	 * Output delivery date step fields.
	 */
	public function output_substep_state_hidden_fields_shipping_fields() {
		$substep_editable_value = ! $this->is_shipping_method_local_pickup_selected() ? 'yes' : 'no';
		echo '<input class="fc-substep-editable-state" type="hidden" value="' . $substep_editable_value . '" />';
	}



	/**
	 * Output the shipping address substep as text when "Local pickup" is selected for the shipping method.
	 */
	public function maybe_output_shipping_address_text() {
		// Bail if shipping method is not `local_pickup`
		if ( ! $this->is_shipping_method_local_pickup_selected() ) { return; }
		
		FluidCheckout_Steps::instance()->output_substep_text_shipping_address();
	}



	/**
	 * Determines if the currently selected shipping method is `local_pickup`.
	 *
	 * @return  boolean  `true` if the selected shipping method is `local_pickup`. Defaults to `false`.
	 */
	public function is_shipping_method_local_pickup_selected() {
		$checkout = WC()->checkout();
		$is_shipping_method_local_pickup_selected = false;
		
		// Make sure chosen shipping method is set
		WC()->cart->calculate_shipping();
		
		// Check chosen shipping method
		$packages = WC()->shipping()->get_packages();
		foreach ( $packages as $i => $package ) {
			$available_methods = $package['rates'];
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';

			if ( $chosen_method && 0 === strpos( $chosen_method, 'local_pickup' ) ) {
				$is_shipping_method_local_pickup_selected = true;
				break;
			}
		}

		return apply_filters( 'fc_is_shipping_method_local_pickup_selected', $is_shipping_method_local_pickup_selected );
	}



	/**
	 * Determines if the any `local_pickup` shipping method is available.
	 *
	 * @return  boolean  `true` if the user has provided all the required data for this step, `false` otherwise. Defaults to `false`.
	 */
	public function is_local_pickup_available() {
		// Bail if not on checkout or cart page or doing AJAX call
		if ( ! function_exists( 'is_checkout' ) || ! ( is_checkout() || is_cart() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) ) { return false; }

		$checkout = WC()->checkout();
		$is_local_pickup_available = false;

		// Make sure chosen shipping method is set
		WC()->cart->calculate_shipping();

		// Check available shipping methods
		$packages = WC()->shipping()->get_packages();
		foreach ( $packages as $i => $package ) {
			$available_methods = $package['rates'];
			foreach ( $available_methods as $method_id => $shipping_method ) {
				if ( 0 === strpos( $method_id, 'local_pickup' ) ) {
					$is_local_pickup_available = true;
					break;
				}
			}
		}

		return apply_filters( 'fc_is_local_pickup_available', $is_local_pickup_available );
	}



	/**
	 * Add shipping address title as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_shipping_address_substep_title_fragment( $fragments ) {
		$substep_id = 'shipping_address';
		$substep_title = $this->maybe_change_shipping_address_substep_title( __( 'Shipping to', 'fluid-checkout' ) );
		$html = FluidCheckout_Steps::instance()->get_substep_title_html( $substep_id, $substep_title );
		$fragments['.fc-step__substep-title--shipping_address'] = $html;
		return $fragments;
	}

	/**
	 * Change the Shipping Address substep title.
	 */
	public function maybe_change_shipping_address_substep_title( $substep_title ) {
		// Change substep title for `local_pickup` shipping methods
		if ( $this->is_shipping_method_local_pickup_selected() ) {
			$substep_title = apply_filters( 'fc_shipping_address_local_pickup_point_title', __( 'Pickup point', 'fluid-checkout' ) );
		}

		return $substep_title;
	}



	/**
	 * Output shipping address substep in text format for when the step is completed.
	 */
	public function change_substep_text_shipping_address( $html ) {
		// Use store base address for `local_pickup`
		if ( $this->is_shipping_method_local_pickup_selected() ) {
			$address_data = array(
				'address_1' => WC()->countries->get_base_address(),
				'address_2' => WC()->countries->get_base_address_2(),
				'city' => WC()->countries->get_base_city(),
				'state' => WC()->countries->get_base_state(),
				'country' => WC()->countries->get_base_country(),
				'postcode' => WC()->countries->get_base_postcode(),
			);

			$html = '<div class="fc-step__substep-text-content fc-step__substep-text-content--shipping-address">';
			$html .= '<div class="fc-step__substep-text-line">' . WC()->countries->get_formatted_address( $address_data ) . '</div>'; // WPCS: XSS ok.
			$html .= '</div>';
		}

		return $html;
	}
	
}

FluidCheckout_CheckoutLocalPickup::instance();
