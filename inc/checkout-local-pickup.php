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
		add_action( 'wp', array( $this, 'very_late_hooks_before' ), 90 );
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Bail if not on checkout or cart page or doing AJAX call
		if ( ! function_exists( 'is_checkout' ) || ( ! is_checkout() && ! is_cart() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) ) { return; }

		// Local Pickup substep
		add_action( 'fc_output_step_shipping', array( $this, 'output_substep_pickup_point' ), $this->get_pickup_point_hook_priority() );
		add_filter( 'fc_substep_pickup_point_attributes', array( $this, 'change_substep_attributes_pickup_point' ), 10 );
		add_filter( 'fc_substep_pickup_point_text_lines', array( $this, 'add_substep_text_lines_pickup_point' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_pickup_point_fields_fragment' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_pickup_point_text_fragment' ), 10 );

		// Shipping Address
		add_filter( 'fc_substep_shipping_address_attributes', array( $this, 'change_substep_attributes_shipping_address' ), 10 );
		add_action( 'fc_checkout_after_step_shipping_fields_inside', array( $this, 'output_substep_state_hidden_fields_shipping_address' ), 10 );
	}

	/**
	 * Add or remove very late hooks before most other very late hooks.
	 */
	public function very_late_hooks_before() {
		// Bail if not on checkout or cart page or doing AJAX call
		if ( ! function_exists( 'is_checkout' ) || ( ! is_checkout() && ! is_cart() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) ) { return; }

		// Needs shipping address
		add_filter( 'woocommerce_cart_needs_shipping_address', array( $this, 'maybe_change_needs_shipping_address' ), 10 );
	}
	



	/**
	 * Get hook priority for the pickup point substep.
	 */
	public function get_pickup_point_hook_priority() {
		$priority = 25;

		// Change priority depending on the settings
		if ( 'before_shipping_address' === get_option( 'fc_shipping_methods_substep_position', 'after_shipping_address' ) ) {
			$priority = 15;
		}

		return $priority;
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
	 * Determines if the currently selected shipping method is `local_pickup`.
	 *
	 * @return  boolean  `true` if the selected shipping method is `local_pickup`. Defaults to `false`.
	 */
	public function is_shipping_method_local_pickup_selected() {
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
	 * Change the shipping address substep attributes.
	 *
	 * @param   array  $substep_attributes  HTML attributes for the substep element.
	 */
	public function change_substep_attributes_shipping_address( $substep_attributes ) {
		$substep_attributes = array_merge( $substep_attributes, array(
			'data-substep-visible' => $this->is_shipping_method_local_pickup_selected() ? 'no' : 'yes',
		) );

		return $substep_attributes;
	}
	
	/**
	 * Output substep state hidden fields for shipping address.
	 */
	public function output_substep_state_hidden_fields_shipping_address() {
		$substep_visible = $this->is_shipping_method_local_pickup_selected() ? 'no' : 'yes';
		echo '<input class="fc-substep-visible-state" type="hidden" value="' . $substep_visible . '" />';
	}



	/**
	 * Change the pickup point substep attributes.
	 *
	 * @param   array  $substep_attributes  HTML attributes for the substep element.
	 */
	public function change_substep_attributes_pickup_point( $substep_attributes ) {
		$substep_attributes = array_merge( $substep_attributes, array(
			'data-substep-visible' => $this->is_shipping_method_local_pickup_selected() ? 'yes' : 'no',
			'data-substep-editable' => 'no',
		) );

		return $substep_attributes;
	}
	
	/**
	 * Output substep state hidden fields for pickup point.
	 */
	public function output_substep_state_hidden_fields_pickup_point() {
		$substep_visible = $this->is_shipping_method_local_pickup_selected() ? 'yes' : 'no';
		echo '<input class="fc-substep-visible-state" type="hidden" value="' . $substep_visible . '" />';
		echo '<input class="fc-substep-editable-state" type="hidden" value="no" />';
	}



	/**
	 * Output pickup point substep.
	 *
	 * @param   string  $step_id  Id of the step in which the substep will be rendered.
	 */
	public function output_substep_pickup_point( $step_id ) {
		$substep_id = 'pickup_point';
		$substep_title = __( 'Pickup point', 'fluid-checkout' );
		FluidCheckout_Steps::instance()->output_substep_start_tag( $step_id, $substep_id, $substep_title );

		FluidCheckout_Steps::instance()->output_substep_fields_start_tag( $step_id, $substep_id );
		$this->output_substep_pickup_point_fields();
		FluidCheckout_Steps::instance()->output_substep_fields_end_tag();

		// Only output substep text format for multi-step checkout layout
		if ( FluidCheckout_Steps::instance()->is_checkout_layout_multistep() ) {
			FluidCheckout_Steps::instance()->output_substep_text_start_tag( $step_id, $substep_id );
			$this->output_substep_text_pickup_point();
			FluidCheckout_Steps::instance()->output_substep_text_end_tag();
		}

		FluidCheckout_Steps::instance()->output_substep_end_tag( $step_id, $substep_id, $substep_title );
	}

	/**
	 * Output the pickup point fields section.
	 */
	public function output_substep_pickup_point_fields() {
		echo '<div class="fc-substep-fields--pickup_point">';
		$this->output_substep_text_pickup_point();
		$this->output_substep_state_hidden_fields_pickup_point();
		echo '</div>';
	}

	/**
	 * Get pickup point step fields html.
	 */
	public function get_substep_pickup_point_fields() {
		ob_start();
		$this->output_substep_pickup_point_fields();
		return ob_get_clean();
	}

	/**
	 * Add pickup point fields as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_pickup_point_fields_fragment( $fragments ) {
		$html = $this->get_substep_pickup_point_fields();
		$fragments['.fc-substep-fields--pickup_point'] = $html;
		return $fragments;
	}



	/**
	 * Add the pickup point substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_pickup_point( $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }
		
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

			$review_text_lines[] = WC()->countries->get_formatted_address( $address_data ); // WPCS: XSS ok.
		}

		return $review_text_lines;
	}
	
	/**
	 * Get pickup point substep review text.
	 */
	public function get_substep_text_pickup_point() {
		return FluidCheckout_Steps::instance()->get_substep_review_text( 'pickup_point' );
	}

	/**
	 * Add pickup point substep review text as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_pickup_point_text_fragment( $fragments ) {
		$html = $this->get_substep_text_pickup_point();
		$fragments['.fc-step__substep-text-content--pickup_point'] = $html;
		return $fragments;
	}

	/**
	 * Output the pickup point substep review text.
	 */
	public function output_substep_text_pickup_point() {
		echo $this->get_substep_text_pickup_point();
	}
	
}

FluidCheckout_CheckoutLocalPickup::instance();
