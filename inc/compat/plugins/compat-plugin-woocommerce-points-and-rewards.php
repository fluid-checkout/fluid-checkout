<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce Points and Rewards (by WooCommerce).
 */
class FluidCheckout_WooCommercePointsAndRewards extends FluidCheckout {

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
		// Bail if use of coupons not enabled
		if ( ! wc_coupons_enabled() ) { return; }

		// Bail if coupon code customizations not enabled
		if ( get_option( 'fc_enable_checkout_coupon_codes', 'yes' ) !== 'yes' ) { return; }

		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// Enqueue
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// JS settings
		add_filter( 'fc_js_settings', array( $this, 'add_points_rewards_js_settings' ), 10 );
	}



	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		if ( class_exists( 'WC_Points_Rewards' ) ) {
			// Remove messages and inline JS
			remove_action( 'woocommerce_before_checkout_form', array( WC_Points_Rewards::instance()->cart, 'render_earn_points_message' ), 5 );
			remove_action( 'woocommerce_before_checkout_form', array( WC_Points_Rewards::instance()->cart, 'render_redeem_points_message' ), 6 );
			remove_action( 'woocommerce_before_checkout_form', array( WC_Points_Rewards::instance()->cart, 'render_discount_javascript' ), 10 );
			remove_action( 'woocommerce_applied_coupon', array( WC_Points_Rewards::instance()->cart, 'discount_updated' ), 40 );
			remove_action( 'woocommerce_removed_coupon', array( WC_Points_Rewards::instance()->cart, 'discount_updated' ), 40 );

			// Change coupon label
			remove_filter( 'woocommerce_cart_totals_coupon_label', array( WC_Points_Rewards::instance()->cart, 'coupon_label' ), 10 );
			add_filter( 'woocommerce_cart_totals_coupon_label', array( $this, 'change_points_redemption_coupon_label' ), 10 );

			// Earn points
			add_action( 'fc_checkout_before_steps', array( $this, 'output_earn_points_section' ), 5 );
			add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_earn_points_section_fragment' ), 10 );

			// Redeem points
			add_action( 'fc_substep_coupon_codes_text_after', array( $this, 'output_redeem_points_section' ), 10 );
		}
	}



	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts() {
		// CHECKOUT
		if ( is_checkout() && ! ( is_order_received_page() || is_checkout_pay_page() ) ) {
			wp_enqueue_script( 'fc-plugin-compat-woocommerce-points-and-rewards--redeem-points', self::$directory_url . 'js/compat/plugins/woocommerce-points-and-rewards/redeem-points'. self::$asset_version . '.js', array(), null );
			wp_add_inline_script( 'fc-plugin-compat-woocommerce-points-and-rewards--redeem-points', 'window.addEventListener("load",function(){ if(window.PointsRewardsRedeemPoints){PointsRewardsRedeemPoints.init()}})' );
		}
	}



	/**
	 * Add points and rewards settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_points_rewards_js_settings( $settings ) {
		$labels = get_option( 'wc_points_rewards_points_label' );
		$labels = is_string( $labels ) ? explode( ':', $labels ) : array();
		$label_plural = array_key_exists( 1, $labels ) ? $labels[1] : _x( 'Points', 'Points label', 'fluid-checkout' );

		$min_points = max( (float) get_option( 'wc_points_rewards_cart_min_discount', '' ), 1 );
		$max_points = WC_Points_Rewards::instance()->cart->calculate_cart_max_points();

		$settings[ 'woocommercePointsRewards' ] = apply_filters( 'fc_points_rewards_script_settings', array(
			/* translators: %1$s is points label, %2$s is min points to redeem, %3$s is max points to redeem. */
			'pointsToRedeemMessage' => sprintf( __( 'How many %1$s would you like to apply? Choose from %2$s up to %3$s %1$s to apply for a discount.', 'fluid-checkout' ), $label_plural, $min_points, $max_points ),
			/* translators: %1$s is points label, %2$s is min points to redeem, %3$s is max points to redeem. */
			'lessThanMinPointsMessage' => sprintf( __( 'Minimum of %2$s %1$s to apply for a discount not reached.', 'fluid-checkout' ), $label_plural, $min_points, $max_points ),
			'partialRedemptionEnabled' => 'yes' === get_option( 'wc_points_rewards_partial_redemption_enabled' ),
			'minPointsToRedeem' => $min_points,
			'maxPointsToRedeem' => $max_points,
		) );

		return $settings;
	}



	/**
	 * Recalculate cart totals when a discount is applied or removed.
	 *
	 * @param string $coupon_code Coupon code which is removed.
	 * 
	 * @see WC_Points_Rewards_Cart_Checkout::discount_updated();
	 */
	public function recalculate_totals_discount_updated( $coupon_code ) {
		// Do not display messages on ajax requests from the checkout or cart page.
		if ( wp_is_json_request() || is_checkout() || is_cart() || wp_get_referer() === wc_get_cart_url() ) {
			return;
		}

		WC()->cart->calculate_totals();
	}



	/**
	 * Renders a message above the cart displaying how many points the customer will receive for completing their purchase
	 *
	 * @since 1.0
	 */
	public function output_earn_points_message() {
		$points_earned = $this->get_points_earned_for_purchase();
		$message = WC_Points_Rewards::instance()->cart->generate_earn_points_message();

		// If message was null then return here, we don't need to continue.
		if( null === $message ) {
			return;
		}

		// Prepare for rendering by wrapping in div.
		$message = '<div class="woocommerce-info wc_points_rewards_earn_points">' . $message . '</div>';

		echo apply_filters( 'wc_points_rewards_earn_points_message', $message, $points_earned );
	}




	/**
	 * Returns the amount of points earned for the purchase, calculated by getting the points earned for each individual
	 * product purchase multiplied by the quantity being ordered.
	 * 
	 * Copied code from the original plugin because the function is `private`.
	 * 
	 * @see WC_Points_Rewards_Discount::get_points_earned_for_purchase();
	 */
	public function get_points_earned_for_purchase() {
		$points_earned = 0;

		foreach ( WC()->cart->get_cart() as $item_key => $item ) {
			$points_earned += apply_filters( 'woocommerce_points_earned_for_cart_item', WC_Points_Rewards_Product::get_points_earned_for_product_purchase( $item['data'] ), $item_key, $item ) * $item['quantity'];
		}

		/*
		 * Reduce by any discounts.  One minor drawback: if the discount includes a discount on tax and/or shipping
		 * it will cost the customer points, but this is a better solution than granting full points for discounted orders.
		 */
		if ( version_compare( WC_VERSION, '2.3', '<' ) ) {
			$discount = WC()->cart->discount_cart + WC()->cart->discount_total;
		} else {
			$discount = ( wc_prices_include_tax() ) ? WC()->cart->discount_cart + WC()->cart->discount_cart_tax : WC()->cart->discount_cart;
		}

		$discount_amount = min( WC_Points_Rewards_Manager::calculate_points( $discount ), $points_earned );

		// Apply a filter that will allow users to manipulate the way discounts affect points earned.
		$points_earned = apply_filters( 'wc_points_rewards_discount_points_modifier', $points_earned - $discount_amount, $points_earned, $discount_amount, $discount );

		// Check if applied coupons have a points modifier and use it to adjust the points earned.
		$coupons = WC()->cart->get_applied_coupons();

		$points_earned = WC_Points_Rewards_Manager::calculate_points_modification_from_coupons( $points_earned, $coupons );

		$points_earned = WC_Points_Rewards_Manager::round_the_points( $points_earned );
		return apply_filters( 'wc_points_rewards_points_earned_for_purchase', $points_earned, WC()->cart );
	}



	/**
	 * Output the redeem points section.
	 * 
	 * @see WC_Points_Rewards_Discount::render_redeem_points_message()
	 */
	public function output_redeem_points_section() {
		// COPIED FROM ORIGINAL PLUGIN FUNCTION
		$existing_discount = WC_Points_Rewards_Discount::get_discount_code();

		/*
		 * Don't display a points message to the user if:
		 * The cart total is fully discounted OR
		 * Coupons are disabled OR
		 * Points have already been applied for a discount.
		 */
		if ( WC_Points_Rewards::instance()->cart->is_fully_discounted() || ! wc_coupons_enabled() || ( ! empty( $existing_discount ) && WC()->cart->has_discount( $existing_discount ) ) ) {
			return;
		}

		// get the total discount available for redeeming points
		$discount_available = WC_Points_Rewards::instance()->cart->get_discount_for_redeeming_points( false, null, true );

		$message = WC_Points_Rewards::instance()->cart->generate_redeem_points_message();

		if ( null === $message ) {
			return;
		}
		// END - COPIED FROM ORIGINAL PLUGIN FUNCTION

		$html = '<div class="fc-coupon-codes__points-rewards wc_points_redeem_earn_points">';
		$html .= '<span class="fc-points-rewards__message">' . wp_kses_post( $message ) . '</span>';
		$html .= '<span class="fc-points-rewards__apply-discount">';
		$html .= '<input type="hidden" name="wc_points_rewards_apply_discount_amount" class="wc_points_rewards_apply_discount_amount" />';
		$html .= '<button type="button" class="wc_points_rewards_apply_discount button alt">' . esc_html( __( 'Apply Discount', 'woocommerce-points-and-rewards' ) ) . '</button>';
		$html .= '</span>';
		$html .= '</div>';
		
		echo apply_filters( 'fc_points_and_rewards_redemption_message', $html, $message );
	}



	/**
	 * Output the earn points section.
	 */
	public function get_earn_points_section_html() {
		$points_earned = $this->get_points_earned_for_purchase();
		$message = WC_Points_Rewards::instance()->cart->generate_earn_points_message();		

		$labels = get_option( 'wc_points_rewards_points_label' );
		$labels = is_string( $labels ) ? explode( ':', $labels ) : array();
		$section_label = array_key_exists( 1, $labels ) ? $labels[1] : _x( 'Points', 'Points and rewards section title', 'fluid-checkout' );
		$section_label = apply_filters( 'fc_points_rewards_section_title', $section_label );

		ob_start();
		?>
		<section class="fc-points-rewards-earn-points" aria-label="<?php echo esc_attr( $section_label ); ?>">
			<?php if( null !== $message ) : ?>
				<div class="fc-points-rewards-earn-points__inner">
					<div class="fc-points-rewards-earn-points__message">
						<?php if ( get_option( 'fc_display_points_rewards_section_title', 'no' ) === 'yes' ) : ?>
							<h2 class="fc-points-rewards-earn-points__title"><?php echo esc_html( $section_label ); ?></h2>
						<?php endif; ?>
						
						<?php echo wp_kses_post( $message ); ?>
					</div>
				</div>
			<?php endif; ?>
		</section>
		<?php
		return ob_get_clean();
	}

	/**
	 * Output the earn points section.
	 */
	public function output_earn_points_section() {
		echo $this->get_earn_points_section_html();
	}

	/**
	 * Add earn points section as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_earn_points_section_fragment( $fragments ) {
		$html = $this->get_earn_points_section_html();
		$fragments['.fc-points-rewards-earn-points'] = $html;
		return $fragments;
	}

	



	/**
	 * Change the coupon code label for points redemption to include the points label from the settings.
	 *
	 * @param   string  $label  Coupon code label.
	 */
	public function change_points_redemption_coupon_label( $label ) {
		if ( strstr( strtoupper( $label ), 'WC_POINTS_REDEMPTION' ) ) {
			// Get label from settings
			$labels = get_option( 'wc_points_rewards_points_label' );
			$labels = is_string( $labels ) ? explode( ':', $labels ) : array();
			$label_singular = array_key_exists( 0, $labels ) ? $labels[0] : _x( 'Point', 'Points label', 'fluid-checkout' );
			$label_plural = array_key_exists( 1, $labels ) ? $labels[1] : _x( 'Points', 'Points label', 'fluid-checkout' );

			/* translators: %1$s points label in singular form, %2$s points label in plural form */
			$label = esc_html( sprintf( __( '%2$s redemption', 'woocommerce-points-and-rewards' ), $label_singular, $label_plural ) );
		}

		return $label;
	}

}

FluidCheckout_WooCommercePointsAndRewards::instance();
