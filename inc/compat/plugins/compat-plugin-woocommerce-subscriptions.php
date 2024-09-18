<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce Subscriptions (by WooCommerce).
 */
class FluidCheckout_WooCommerceSubscriptions extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct( ) {
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Template file loader
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 100, 3 );

		// Shipping methods available for subscription plans
		remove_action( 'woocommerce_subscriptions_recurring_totals_shipping', array( 'WCS_Template_Loader', 'get_recurring_cart_shipping' ), 10 );
		add_action( 'fc_shipping_methods_after_packages_inside', array( 'WCS_Template_Loader', 'get_recurring_cart_shipping' ), 10 );

		// Shipping methods
		add_filter( 'fc_cart_has_multiple_packages', array( $this, 'maybe_set_cart_with_multiple_packages' ), 10 );
		add_action( 'fc_shipping_method_display_package_destination_substep_text_lines', array( $this, 'maybe_enable_show_shipping_method_package_destination' ), 10 );
		add_action( 'fc_shipping_method_display_package_name', array( $this, 'maybe_enable_show_shipping_method_package_name' ), 10 );
		add_filter( 'fc_subscription_shipping_package_name', array( $this, 'maybe_change_subscription_shipping_package_name' ), 10, 4 );
	}



	/**
	 * Maybe set cart with multiple packages.
	 * 
	 * @param  bool  $has_multiple_packages   Whether the cart has multiple packages.
	 */
	public function maybe_set_cart_with_multiple_packages( $has_multiple_packages ) {
		// Get packages count
		$packages_count = $this->get_all_packages_count();

		// Maybe set cart with multiple packages when there are at least 2 shipping packages
		if ( 1 < $packages_count ) {
			$has_multiple_packages = true;
		}

		return $has_multiple_packages;
	}

	/**
	 * Maybe disable show shipping package destination.
	 * 
	 * @param  bool  $show_package_destination   Whether to display the shipping package destination.
	 */
	public function maybe_enable_show_shipping_method_package_destination( $show_package_destination ) {
		// Get packages count
		$packages_count = $this->get_all_packages_count();

		// Maybe enable show shipping method package name when there are at least 2 shipping packages
		if ( 1 < $packages_count ) {
			$show_package_destination = false;
		}

		return $show_package_destination;
	}

	/**
	 * Maybe enable show shipping package name.
	 * 
	 * @param  bool  $show_package_name   Whether to display the shipping package name.
	 */
	public function maybe_enable_show_shipping_method_package_name( $show_package_name ) {
		// Get packages count
		$packages_count = $this->get_all_packages_count();

		// Maybe enable show shipping package name when there are at least 2 shipping packages
		if ( 1 < $packages_count ) {
			$show_package_name = true;
		}

		return $show_package_name;
	}

	/**
	 * Changes the shipping package name to add more meaningful information about it's content.
	 * COPIED FROM: `woocommerce-subscriptions/vendor/woocommerce/subscriptions-core/includes/class-wc-subscriptions-extend-store-endpoint.php`
	 *
	 * @param array $package All shipping package data.
	 * @param array $cart Recurring cart data.
	 */
	public function get_shipping_package_name( $package, $cart ) {
		$package_name = __( 'Shipping', 'woocommerce-subscriptions' );
		$interval     = wcs_cart_pluck( $cart, 'subscription_period_interval', '' );
		$period       = wcs_cart_pluck( $cart, 'subscription_period', '' );
		switch ( $period ) {
			case 'year':
				// translators: %d subscription interval.
				$package_name = $interval > 1 ? sprintf( _n( 'Shipment every %d year', 'Shipment every %d years', $interval, 'woocommerce-subscriptions' ), $interval ) : __( 'Yearly Shipment', 'woocommerce-subscriptions' );
				break;
			case 'month':
				// translators: %d subscription interval.
				$package_name = $interval > 1 ? sprintf( _n( 'Shipment every %d month', 'Shipment every %d months', $interval, 'woocommerce-subscriptions' ), $interval ) : __( 'Monthly Shipment', 'woocommerce-subscriptions' );
				break;
			case 'week':
				// translators: %d subscription interval.
				$package_name = $interval > 1 ? sprintf( _n( 'Shipment every %d week', 'Shipment every %d weeks', $interval, 'woocommerce-subscriptions' ), $interval ) : __( 'Weekly Shipment', 'woocommerce-subscriptions' );
				break;
			case 'day':
				// translators: %d subscription interval.
				$package_name = $interval > 1 ? sprintf( _n( 'Shipment every %d day', 'Shipment every %d days', $interval, 'woocommerce-subscriptions' ), $interval ) : __( 'Daily Shipment', 'woocommerce-subscriptions' );
				break;
		}
		return $package_name;
	}

	/**
	 * Maybe change the shipping package name.
	 * 
	 * @param  string  $package_name     The shipping package name.
	 * @param  int     $package_index    The shipping package index.
	 * @param  array   $package          The shipping package.
	 * @param  object  $recurring_cart   The recurring cart object.
	 */
	public function maybe_change_subscription_shipping_package_name( $package_name, $package_index, $package, $recurring_cart ) {
		// Bail if not a recurring package
		if ( ! array_key_exists( 'recurring_cart_key', $package ) ) { return $package_name; }

		// Get shipping package name
		$package_name = $this->get_shipping_package_name( $package, $recurring_cart );

		return $package_name;
	}



	/**
	 * Locate template files from this plugin.
	 */
	public function locate_template( $template, $template_name, $template_path ) {
		$_template = null;

		// Set template path to default value when not provided
		if ( ! $template_path ) { $template_path = 'woocommerce/'; };

		// Get plugin path
		$plugin_path = self::$directory_path . 'templates/compat/plugins/woocommerce-subscriptions/';

		// Get the template from this plugin, if it exists
		if ( file_exists( $plugin_path . $template_name ) ) {
			$_template = $plugin_path . $template_name;

			// Look for template file in the theme
			if ( apply_filters( 'fc_override_template_with_theme_file', false, $template, $template_name, $template_path ) || apply_filters( 'fc_pro_override_template_with_theme_file', false, $template, $template_name, $template_path ) ) {
				$_template_override = locate_template( array(
					trailingslashit( $template_path ) . $template_name,
					$template_name,
				) );

				// Check if files exist before changing template
				if ( file_exists( $_template_override ) ) {
					$_template = $_template_override;
				}
			}
		}

		// Use default template
		if ( ! $_template ) {
			$_template = $template;
		}

		// Return what we found
		return $_template;
	}



	/**
	 * Get shipping method label for a subscription plan.
	 *
	 * @param  string         $cart   The recurring cart object.
	 * @param  object|string  $method Either the name of the method's class, or an instance of the method's class.
	 */
	public function get_recurring_shipping_methods_label( $recurring_cart, $method ) {
		// Bail if function is not available
		if ( ! function_exists( 'wcs_cart_price_string' ) ) { return; }

		// Initialize label variable
		$label = '';

		// Get method label
		$label .= sprintf( apply_filters( 'fc_shipping_method_option_label_markup', '<span class="shipping-method__option-text">%s</span>', $method ), $method->get_label() );

		// Maybe add shipping method logo image to label
		$method_image_html = apply_filters( 'fc_shipping_method_option_image_html', '', $method );
		if ( ! empty( $method_image_html ) ) {
			$label .= sprintf( apply_filters( 'fc_shipping_method_option_image_markup', '<span class="shipping-method__option-image">%s</span>', $method, $method_image_html ), $method_image_html );
		}

		// Get shipping method costs settings
		$has_cost  = apply_filters( 'fc_shipping_method_has_cost', 0 < $method->cost, $method );
		$hide_cost = ! $has_cost && in_array( $method->get_method_id(), array( 'free_shipping', 'local_pickup' ), true );

		// Maybe add shipping method costs to label
		if ( $has_cost && ! $hide_cost ) {
			$method_costs = '';

			// Maybe get shipping method costs including tax
			if ( WC()->cart->display_prices_including_tax() ) {
				$method_costs = wcs_cart_price_string( $method->cost + $method->get_shipping_tax(), $recurring_cart );
				if ( $method->get_shipping_tax() > 0 && ! wc_prices_include_tax() ) {
					$method_costs .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
				}
			}
			// Otherwise get shipping method costs excluding tax
			else {
				$method_costs = wcs_cart_price_string( $method->cost, $recurring_cart );
				if ( $method->get_shipping_tax() > 0 && wc_prices_include_tax() ) {
					$method_costs .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
				}
			}

			// Allow developers to change the shipping method costs
			$method_costs = apply_filters( 'fc_shipping_method_option_price', $method_costs, $method );

			// Add shipping method costs to label
			$label .= sprintf( apply_filters( 'fc_shipping_method_option_price_markup', ' <span class="shipping-method__option-price">%s</span>', $method, $method_costs ), $method_costs );
		}

		return $label;
	}



	/**
	 * Get the number of recurring and initial shipping packages in the cart.
	 */
	public function get_all_packages_count() {
		// Get initial shipping packages
		$packages = WC()->shipping->get_packages();

		// Add packages count
		$packages_count = count( $packages );

		// Iterate through recurring carts
		foreach ( WC()->cart->recurring_carts as $recurring_cart_key => $recurring_cart ) {
			// Allow third parties to filter whether the recurring cart has a shipment.
			$cart_has_next_shipment = apply_filters( 'woocommerce_subscriptions_cart_has_next_shipment', 0 !== $recurring_cart->next_payment_date, $recurring_cart );

			// Increment packages count if the recurring cart contains subscriptions needing shipping.
			if ( $cart_has_next_shipment && WC_Subscriptions_Cart::cart_contains_subscriptions_needing_shipping( $recurring_cart ) ) {
				$packages_count++;
			}
		}

		return $packages_count;
	}



	/**
	 * Get the shipping package details.
	 * 
	 * @param  array  $package  The shipping package.
	 */
	public function get_package_details( $package ) {
		// Bail if package contents are not available
		if ( ! isset( $package['contents'] ) ) { return; }

		// Get product names from the package
		foreach ( $package['contents'] as $item_id => $values ) {
			$product_names[] = $values['data']->get_title() . ' &times;' . $values['quantity'];
		}

		// Combine product names into package details
		$package_details = implode( ', ', $product_names );

		return $package_details;
	}

}

FluidCheckout_WooCommerceSubscriptions::instance();
