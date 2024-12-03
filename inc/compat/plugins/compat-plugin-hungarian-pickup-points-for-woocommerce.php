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

		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Checkout validation settings
		add_filter( 'fc_checkout_validation_script_settings', array( $this, 'change_js_settings_checkout_validation' ), 10 );

		// Maybe set substep as incomplete
		add_filter( 'fc_is_substep_complete_pickup_point', array( $this, 'maybe_set_substep_incomplete_pickup_point' ), 10 );

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
		add_filter( 'fc_shipping_method_substep_text_chosen_method_label', array( $this, 'maybe_change_shipping_method_substep_text_chosen_method_label' ), 10, 2 );
		add_filter( 'fc_substep_shipping_method_text_lines', array( $this, 'maybe_change_substep_text_lines_shipping_methods' ), 20 );

		// Shipping Address
		add_filter( 'fc_substep_shipping_address_attributes', array( $this, 'maybe_change_substep_attributes_shipping_address' ), 10 );

		// Shipping methods
		add_filter( 'fc_shipping_method_has_cost', array( $this, 'maybe_set_shipping_method_has_cost' ), 10, 2 );
		add_filter( 'fc_shipping_method_option_price', array( $this, 'maybe_change_shipping_method_option_costs' ), 10, 2 );

		// Order summary
		add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'maybe_replace_hook_change_shipping_method_label' ), 5, 2 );
	}

	/**
	 * Maybe replace hook for changing the shipping method label.
	 */
	public function maybe_replace_hook_change_shipping_method_label( $label, $method ) {
		// Bail if selected shipping method is not a Hungarian Pickup Points shipping method
		if ( ! $this->is_shipping_method_vp_pont_selected() ) { return $label; }

		// Bail if pickup point location is not yet selected
		if ( ! $this->is_vp_pont_location_selected() ) { return $label; }

		// Replace filter
		remove_filter( 'woocommerce_cart_shipping_method_full_label', array( VP_Woo_Pont::instance(), 'change_shipping_method_label' ), 10, 2 );
		add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'maybe_change_shipping_method_full_label' ), 10, 2 );

		return $label;
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Add validation script
		wp_register_script( 'fc-checkout-validation-hungarian-shipping-methods', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/hungarian-pickup-points-for-woocommerce/checkout-validation-hungarian-shipping-methods' ), array( 'jquery', 'fc-utils', 'fc-checkout-validation' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-checkout-validation-hungarian-shipping-methods', 'window.addEventListener("load",function(){CheckoutValidationHungarianShippingMethods.init(fcSettings.checkoutValidationHungarianShippingMethods);})' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-checkout-validation-hungarian-shipping-methods' );
	}

	/**
	 * Maybe enqueue assets.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not at checkout
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return; }

		$this->enqueue_assets();
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {

		// Add validation settings
		$settings[ 'checkoutValidationHungarianShippingMethods' ] = array(
			'validationMessages'  => array(
				'pickup_point_not_selected' => __( 'Selecting a pickup point is required before proceeding.', 'fluid-checkout' ),
			),
		);

		return $settings;
	}

	

	/**
	 * Add settings to the plugin settings JS object for the checkout validation.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function change_js_settings_checkout_validation( $settings ) {
		// Get current values
		$current_validate_field_selector = array_key_exists( 'validateFieldsSelector', $settings ) ? $settings[ 'validateFieldsSelector' ] : '';
		$current_reference_node_selector = array_key_exists( 'referenceNodeSelector', $settings ) ? $settings[ 'referenceNodeSelector' ] : '';
		$current_always_validate_selector = array_key_exists( 'alwaysValidateFieldsSelector', $settings ) ? $settings[ 'alwaysValidateFieldsSelector' ] : '';

		// Prepend new values to existing settings
		$settings[ 'validateFieldsSelector' ] = 'input[name="vp_pont_id"]' . ( ! empty( $current_validate_field_selector ) ? ', ' : '' ) . $current_validate_field_selector;
		$settings[ 'referenceNodeSelector' ] = 'input[name="vp_pont_id"]' . ( ! empty( $current_reference_node_selector ) ? ', ' : '' ) . $current_reference_node_selector;
		$settings[ 'alwaysValidateFieldsSelector' ] = 'input[name="vp_pont_id"]' . ( ! empty( $current_always_validate_selector ) ? ', ' : '' ) . $current_always_validate_selector;

		return $settings;
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

		// Get selected pont ID
		$selected_vp_pont_id = $selected_vp_pont ? $selected_vp_pont[ 'id' ] : '';

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
		$html = '<div class="vp-woo-pont-pickup-location form-row woocommerce-input-wrapper">';

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
			$html .= '<a href="#" id="vp-woo-pont-show-map" data-shipping-costs="' . $shipping_costs_attr . '">' . esc_html( __( 'Modify', 'vp-woo-pont' ) ) . '</a>';

			$html .= '</div>';
		}

		// Hidden fields
		$html .= '<input type="hidden" id="vp_pont_id" name="vp_pont_id" value="'. esc_attr( $selected_vp_pont_id ) .'" class="validate-hungarian-shipping-method">';
		
		$html .= '</div>';

		// Add substep text lines
		echo $html; // WPCS: XSS ok.
	}

	/**
	 * Maybe change the shipping method substep text to display the shipping method name without price when a Hungarian Pickup Points shipping method is selected.
	 */
	public function maybe_change_shipping_method_substep_text_chosen_method_label( $chosen_method_label, $method ) {
		// Bail if selected shipping method is not a Hungarian Pickup Points shipping method
		if ( ! $this->is_shipping_method_vp_pont_selected() ) { return $chosen_method_label; }

		// Use only the shipping method name
		$chosen_method_label = $method->get_label();
		
		return $chosen_method_label;
	}

	/**
	 * Maybe change the shipping methods substep text to display the selected Hungarian Pickup Points information.
	 */
	public function maybe_change_substep_text_lines_shipping_methods( $text_lines ) {
		// Bail if selected shipping method is not a Hungarian Pickup Points shipping method
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
			$text_lines[] = '<em>' . __( 'Pickup point not selected yet.', 'fluid-checkout' ) . '</em>';
		}


		return $text_lines;
	}



	/**
	 * Set the substep as incomplete when shipping method is Hungarian Pickup Points and no pickup point is selected.
	 *
	 * @param   bool  $is_substep_complete  Whether the substep is complete or not.
	 */
	public function maybe_set_substep_incomplete_pickup_point( $is_substep_complete ) {
		// Bail if substep is already incomplete
		if ( ! $is_substep_complete ) { return $is_substep_complete; }
		
		// Bail if Hungarian Pickup Points shipping method is not selected
		if ( ! $this->is_shipping_method_vp_pont_selected() ) { return $is_substep_complete; }

		// Maybe set substep as incomplete if a Hungarian Pickup Points location is not yet selected
		if ( ! $this->is_vp_pont_location_selected() ) {
			$is_substep_complete = false;
		}

		return $is_substep_complete;
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
		// Bail if Hungarian Pickup Points shipping method is not selected
		if ( ! $this->is_shipping_method_vp_pont_selected() ) { return $substep_attributes; }

		$substep_attributes = array_merge( $substep_attributes, array(
			'data-substep-visible' => $this->is_shipping_method_vp_pont_selected() ? 'no' : 'yes',
		) );

		return $substep_attributes;
	}



	/**
	 * Maybe change the shipping method label to display the selected Hungarian Pickup Points price.
	 */
	public function maybe_change_shipping_method_full_label( $label, $method ) {
		// Bail if not Hungarian Pickup Points shipping method
		if ( ! $this->is_shipping_method_vp_pont( $method->method_id ) ) { return $label; }

		// Bail if pickup point location is not yet selected
		if ( ! $this->is_vp_pont_location_selected() ) { return $label; }

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

		// Costs
		$formatted_costs = '';
		if ( WC()->cart->display_prices_including_tax() ) {
			$formatted_costs = $shipping_cost[ 'formatted_gross' ];
		}
		else {
			$formatted_costs = $shipping_cost[ 'formatted_net' ];
		}

		// Change shipping method label
		$label = $method->get_label() . ': ' . $formatted_costs;

		return $label;
	}



	/**
	 * Maybe set shipping method option as having costs for Hungarian Pickup Points.
	 */
	public function maybe_set_shipping_method_has_cost( $has_cost, $method ) {
		// Bail if not Hungarian Pickup Points shipping method
		if ( ! $this->is_shipping_method_vp_pont( $method->method_id ) ) { return $has_cost; }

		return true;
	}

	/**
	 * Maybe change the shipping method option costs to display the minimum price for Hungarian Pickup Points.
	 */
	public function maybe_change_shipping_method_option_costs( $method_costs, $method ) {
		// Bail if not Hungarian Pickup Points shipping method
		if ( ! $this->is_shipping_method_vp_pont( $method->method_id ) ) { return $method_costs; }

		// Get shippign cost
		$shipping_cost = VP_Woo_Pont_Helpers::calculate_shipping_costs();

		// Find the smallest cost
		$minimum_cost = false;
		$minimum_cost_count = array();
		$has_free_shipping = false;
		$min_cost_formatted = '';
		$min_cost_label = '';
		foreach ( $shipping_cost as $provider => $array ) {
			if ( $array[ 'net' ] == 0 ) {
				$has_free_shipping = true;
			} else {
				$minimum_cost_count[] = $array[ 'net' ];
				if ( ! $minimum_cost ) {
					$minimum_cost = $array;
				}
				elseif ( $array[ 'net' ] < $minimum_cost[ 'net' ] ) {
					$minimum_cost = $array;
				}
			}
		}

		// Check how many different prices we have
		$minimum_cost_count = array_unique( $minimum_cost_count );
		$minimum_cost_count = count( $minimum_cost_count );

		// Minimum cost label
		if ( $minimum_cost ) {
			if ( WC()->cart->display_prices_including_tax() ) {
				$min_cost_formatted = $minimum_cost[ 'formatted_gross' ];
			} else {
				$min_cost_formatted = $minimum_cost[ 'formatted_net' ];
			}
		}

		// Minimum cost label, only free shipping
		if( $has_free_shipping && $minimum_cost_count == 0 ) {
			$min_cost_label = esc_html_x( 'free', 'shipping cost summary on cart & checkout', 'vp-woo-pont' );
		}

		// Minimum cost label, only 1 paid shipping
		if ( ! $has_free_shipping && $minimum_cost_count == 1 ) {
			$min_cost_label = sprintf( esc_html_x( '%s', 'shipping cost summary on cart & checkout(one shipping cost only)', 'vp-woo-pont' ), '<em class="vp-woo-pont-shipping-method-label-cost">' . $min_cost_formatted . '</em>' );
		}

		// Minimum cost label, multiple paid shipping
		if ( ! $has_free_shipping && $minimum_cost_count > 1 ) {
			$min_cost_label = sprintf( esc_html_x( 'from %s', 'shipping cost summary on cart & checkout(multiple shipping costs)', 'vp-woo-pont' ) . ' ', '<em class="vp-woo-pont-shipping-method-label-cost">' . $min_cost_formatted . '</em>' );
		}

		// Minimum cost label, free shipping + paid shipping
		if ( $has_free_shipping && $minimum_cost_count == 1 ) {
			$min_cost_label = sprintf( esc_html_x( 'free or %s', 'shipping cost summary on cart & checkout(free & 1 shipping cost)', 'vp-woo-pont' ) . ' ', '<em class="vp-woo-pont-shipping-method-label-cost">' . $min_cost_formatted . '</em>' );
		}

		// Minimum cost label, free shipping + paid shipping
		if ( $has_free_shipping && $minimum_cost_count > 1 ) {
			$min_cost_label = sprintf( esc_html_x( 'free & from %s', 'shipping cost summary on cart & checkout(free & 1+ shipping cost)', 'vp-woo-pont' ) . ' ', '<em class="vp-woo-pont-shipping-method-label-cost">' . $min_cost_formatted . '</em>' );
		}

		// Create new labels with price and optional icons
		$method_costs = '<span class="vp-woo-pont-shipping-method-label-price">' . $min_cost_label . '</span>';

		return $method_costs;
	}

}

FluidCheckout_HungarianPickupPointsForWooCommerce::instance();
