<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Hungarian Pickup Points & Shipping Labels for WooCommerce (by Viszt Péter).
 */
class FluidCheckout_HungarianPickupPointsForWooCommerce extends FluidCheckout {

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
		// Bail if Hungarian Pickup Points for WooCommerce class does not exist
		if ( ! class_exists( 'VP_Woo_Pont' ) ) { return; }

		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 150 );

		// Maybe set step as incomplete
		add_filter( 'fc_is_step_complete_shipping', array( $this, 'maybe_set_step_incomplete_shipping' ), 10 );

		// Shipping address
		add_action( 'fc_checkout_after_step_shipping_fields_inside', array( $this, 'output_substep_state_hidden_fields_shipping_address' ), 10 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Pickup point selection
		remove_action( 'woocommerce_review_order_after_shipping', array( VP_Woo_Pont::instance(), 'checkout_ui' ), 10 );
		add_action( 'fc_shipping_methods_after_packages_inside', array( $this, 'output_pickup_point_selection_ui' ), 10 );
		add_filter( 'fc_substep_shipping_method_text_lines', array( $this, 'maybe_change_substep_text_lines_shipping_methods' ), 10 );

		// When shipping method is selected
		if ( $this->is_shipping_method_vp_pont_selected() ) {
			// Shipping Address
			add_filter( 'fc_substep_shipping_address_attributes', array( $this, 'maybe_change_substep_attributes_shipping_address' ), 10 );
		}

		// When pickup point is selected
		if ( $this->is_shipping_method_vp_pont_selected() && $this->is_vp_pont_location_selected() ) {
			// Change shipping option label on the checkout page
			remove_filter( 'woocommerce_cart_shipping_method_full_label', array( VP_Woo_Pont::instance(), 'change_shipping_method_label' ), 10, 2 );
		}
	}



	/**
	 * Maybe evaluate Hungarian Pickup Points shipping methods as local pickup.
	 */
	public function is_shipping_method_vp_pont( $shipping_method_id ) {
		return 0 === strpos( $shipping_method_id, 'vp_pont' );
	}



	/**
	 * Maybe evaluate Hungarian Pickup Points shipping methods as local pickup.
	 */
	public function is_shipping_method_vp_pont_selected() {
		$is_vp_pont_selected = false;

		// Check chosen shipping method
		$packages = WC()->shipping()->get_packages();
		foreach ( $packages as $i => $package ) {
			// Check if `vp_pont` shipping method is selected
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			if ( $chosen_method && $this->is_shipping_method_vp_pont( $chosen_method ) ) {
				$is_vp_pont_selected = true;
				break;
			}
		}

		return $is_vp_pont_selected;
	}

	/**
	 * Define whether a pickup point is selected.
	 */
	public function is_vp_pont_location_selected() {
		// Get selected pont
		$selected_vp_pont = WC()->session->get( 'selected_vp_pont' );

		// Get shipping cost
		$shipping_costs = VP_Woo_Pont_Helpers::calculate_shipping_costs();
		$shipping_cost = VP_Woo_Pont_Helpers::get_shipping_cost();

		// Define whether a pickup point is selected
		$is_vp_pont_selected = $selected_vp_pont && ! empty( $shipping_cost );

		return $is_vp_pont_selected;
	}



	/**
	 * Maybe change the pickup point substep text to display the selected Hungarian Pickup Points information.
	 */
	public function output_pickup_point_selection_ui( $show_title = true ) {
		// Bail if selected shipping method is not a Hungarian Pickup Points shipping method
		if ( ! $this->is_shipping_method_vp_pont_selected() ) { return; }

		// Get selected pont
		$selected_vp_pont = WC()->session->get( 'selected_vp_pont' );

		// Get shipping cost
		$shipping_costs = VP_Woo_Pont_Helpers::calculate_shipping_costs();
		$shipping_cost = VP_Woo_Pont_Helpers::get_shipping_cost();

		// Convert shippings costs so it works as a data attribute
		$shipping_costs_json = wp_json_encode( $shipping_costs );
		$shipping_costs_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $shipping_costs_json ) : _wp_specialchars( $shipping_costs_json, ENT_QUOTES, 'UTF-8', true );
		$button_label = __( 'Select a pick-up point', 'vp-woo-pont' );
		if ( '' != get_option('vp_woo_pont_custom_button_label', '' ) ) {
			$button_label = get_option( 'vp_woo_pont_custom_button_label' );
		}

		// Start html
		$html = '<div class="vp-woo-pont-pickup-location">';

		// Maybe output the section title
		if ( true === $show_title || '' === $show_title ) {
			$html .= '<h3 class="vp-woo-pont-pickup-location__title">' . __( 'Pickup point', 'fluid-checkout' ) . '</h3>';
		}

		// Does not have selected pickup point
		if ( ! $selected_vp_pont || empty( $shipping_cost ) ) {
			$html .= '<div class="vp-woo-pont-review-order-not-selected">';
			$html .= '<a href="#" id="vp-woo-pont-show-map" class="button" data-shipping-costs="' . $shipping_costs_attr . '">' . esc_html( $button_label ) . '</a>';
			$html .= '</div>';
		}
		// Has selected pickup point
		else {
			$html .= '<div class="vp-woo-pont-review-order-selected">';

			// Icon
			$html .= '<i class="vp-woo-pont-provider-icon vp-woo-pont-provider-icon-' . esc_attr( $selected_vp_pont[ 'provider' ] ) . '"></i>';

			// Name
			$html .= '<strong>' . esc_html( $selected_vp_pont[ 'name' ] ) . ':</strong>';

			// Costs
			if ( WC()->cart->display_prices_including_tax() ) {
				$html .= '<strong>' . $shipping_cost[ 'formatted_gross' ] . '</strong><br>';
			}
			else {
				$html .= '<strong>' . $shipping_cost['formatted_net'] . '</strong><br>';
			}

			// Address
			$html .= '<div>' . esc_html( $selected_vp_pont[ 'addr' ] ) . ', ';
			$html .= esc_html( $selected_vp_pont[ 'zip' ] ) . ' ';
			$html .= esc_html( $selected_vp_pont[ 'city' ] );
			$html .= '</div>';

			// Button
			$html .= '<a href="#" id="vp-woo-pont-show-map" data-shipping-costs="' . $shipping_costs_attr . '">' . esc_html( 'Modify', 'vp-woo-pont' ) . '</a>';

			$html .= '</div>';
		}
		
		$html .= '</div>';

		// Add substep text lines
		echo $html; // WPCS: XSS ok.
	}

	/**
	 * Maybe change the shipping methods substep text to display the selected Hungarian Pickup Points information.
	 */
	public function maybe_change_substep_text_lines_shipping_methods( $text_lines ) {
		// Bail if select shipping method is not a Hungarian Pickup Points shipping method
		if ( ! $this->is_shipping_method_vp_pont_selected() ) { return $text_lines; }

		// Has pickup location selected
		if ( $this->is_vp_pont_location_selected() ) {
			// Get HTML for the pickup point selection UI
			ob_start();
			$this->output_pickup_point_selection_ui( false );
			$html = ob_get_clean();
			
			// Add pickup point to substep review text lines
			$text_lines[] = '<strong>' . __( 'Pickup point:', 'fluid-checkout' ) . '</strong>';
			$text_lines[] = $html;
		}
		// Does not have pickup location selected
		else {
			// Add pickup point to substep review text lines
			$text_lines[] = __( 'Pickup point not selected yet.', 'fluid-checkout' );
		}


		return $text_lines;
	}



	/**
	 * Set the shipping step as incomplete when shipping method is Hungarian Pickup Points and no pickup point is selected.
	 *
	 * @param   bool  $is_step_complete  Whether the step is complete or not.
	 */
	public function maybe_set_step_incomplete_shipping( $is_step_complete ) {
		// Bail if step is already incomplete
		if ( ! $is_step_complete ) { return $is_step_complete; }
		
		// Bail if Hungarian Pickup Points shipping method is not selected
		if ( ! $this->is_shipping_method_vp_pont_selected() ) { return $is_step_complete; }

		// Maybe set step as incomplete if a Hungarian Pickup Points location is not yet selected
		if ( ! $this->is_vp_pont_location_selected() ) {
			$is_step_complete = false;
		}

		return $is_step_complete;
	}



	/**
	 * Output substep state hidden fields for shipping address.
	 */
	public function output_substep_state_hidden_fields_shipping_address() {
		$substep_visible = $this->is_shipping_method_vp_pont_selected() ? 'no' : 'yes';
		echo '<input class="fc-substep-visible-state" type="hidden" value="' . $substep_visible . '" />';
	}



	/**
	 * Change the shipping address substep attributes.
	 *
	 * @param   array  $substep_attributes  HTML attributes for the substep element.
	 */
	public function maybe_change_substep_attributes_shipping_address( $substep_attributes ) {
		$substep_attributes = array_merge( $substep_attributes, array(
			'data-substep-visible' => $this->is_shipping_method_vp_pont_selected() ? 'no' : 'yes',
		) );

		return $substep_attributes;
	}

}

FluidCheckout_HungarianPickupPointsForWooCommerce::instance();
