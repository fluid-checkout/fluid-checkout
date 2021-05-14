<?php

/**
 * Feature for adding coupon codes or gift certificate codes to checkout
 */
class FluidCheckout_CouponCodes extends FluidCheckout {

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
		// Body Class
		add_filter( 'body_class', array( $this, 'add_body_class' ) );

		// Checkout Coupon Notice
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

		// Coupon Code Substep
		add_action( 'wfc_output_step_payment', array( $this, 'output_substep_coupon_codes' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_coupon_codes_text_fragment' ), 10 );

		// Apply coupon code
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'maybe_apply_coupon_code' ), 10 );
		
		// Apply coupon code on processing order
		// This action has high priority as other plugins might need the updated cart values after this point.
		add_action( 'woocommerce_checkout_process', array( $this, 'maybe_apply_coupon_code_on_process_checkout' ), 0 );
	}



	/**
	 * Return Checkout Steps class instance.
	 */
	public function checkout_steps() {
		return FluidCheckout_Steps::instance();
	}



	/**
	 * Add page body class for feature detection.
	 *
	 * @param   array  $classes  Body classes array.
	 */
	public function add_body_class( $classes ) {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() ){ return $classes; }

		return array_merge( $classes, array( 'has-wfc-coupon-code-fields' ) );
	}



	/**
	 * Output coupon codes substep.
	 *
	 * @param   string  $step_id     Id of the step in which the substep will be rendered.
	 */
	public function output_substep_coupon_codes( $step_id ) {
		$substep_id = 'coupon_codes';
		$substep_title = apply_filters( 'wfc_substep_coupon_codes_title_display', false ) === true ? apply_filters( 'wfc_substep_coupon_codes_title', __( 'Coupon code', 'woocommerce-fluid-checkout' ) ) : null;
		$this->checkout_steps()->output_substep_start_tag( $step_id, $substep_id, $substep_title );

		$this->output_substep_text_coupon_codes();

		$this->checkout_steps()->output_substep_fields_start_tag( $step_id, $substep_id, false );
		$this->output_substep_coupon_codes_fields();
		$this->checkout_steps()->output_substep_fields_end_tag();		

		$this->checkout_steps()->output_substep_end_tag( $step_id, $substep_id, false );
	}



	/**
	 * Output coupon codes fields.
	 */
	public function output_substep_coupon_codes_fields() {
		$coupon_code_field_label = apply_filters( 'wfc_coupon_code_field_label', __( 'Coupon code', 'woocommerce-fluid-checkout' ) );
		$coupon_code_field_placeholder = apply_filters( 'wfc_coupon_code_field_placeholder', __( 'Enter your code here', 'woocommerce-fluid-checkout' ) );
		$coupon_code_button_label = apply_filters( 'wfc_coupon_code_button_label', _x( 'Apply code', 'Button label for applying coupon codes', 'woocommerce-fluid-checkout' ) );

		$this->checkout_steps()->output_expansible_form_section_start_tag( 'coupon_code', apply_filters( 'wfc_coupon_code_expansible_section_toggle_label', __( 'Add coupon code', 'woocommerce-fluid-checkout' ) ) );
		?>
			<input type="text" class="input-text" name="coupon_code" id="coupon_code" aria-label="<?php echo esc_attr( $coupon_code_field_label ); ?>" placeholder="<?php echo esc_attr( $coupon_code_field_placeholder ); ?>" data-autofocus>
			<button type="button" class="wfc-step__substep-coupon-codes-save" data-step-save><?php echo $coupon_code_button_label; ?></button>
		<?php
		$this->checkout_steps()->output_expansible_form_section_end_tag();
	}

	/**
	 * Get coupon codes fields.
	 */
	public function get_substep_coupon_codes_fields() {
		ob_start();
		$this->output_substep_coupon_codes_fields();
		return ob_get_clean();
	}



	/**
	 * Get coupon codes substep added coupon codes.
	 */
	public function get_substep_text_coupon_codes() {
		$html = '<div class="wfc-step__substep-text-content wfc-step__substep-text-content--coupon-codes">';
		ob_start();

		foreach ( WC()->cart->get_coupons() as $code => $coupon ) :
			// Get coupon label with changed "remove" link
			ob_start();
			wc_cart_totals_coupon_html( $coupon );
			$coupon_html = str_replace( __( '[Remove]', 'woocommerce' ), __( 'Remove', 'woocommerce-fluid-checkout' ), ob_get_clean() );
			?>
			<div class="wfc-coupon-codes__coupon coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
				<span class="wfc-coupon-codes__coupon-code"><?php wc_cart_totals_coupon_label( $coupon ); ?></span>
				<span class="wfc-coupon-codes__coupon-amount"><?php echo $coupon_html; ?></span>
			</div>
			<?php
		endforeach;

		$html .= ob_get_clean();
		$html .= '</div>';

		return apply_filters( 'wfc_substep_coupon_codes_text', $html );
	}

	/**
	 * Add coupon codes text format as checkout fragment.
	 * 
	 * @param array $fragments Checkout fragments.
	 */
	public function add_coupon_codes_text_fragment( $fragments ) {
		$html = $this->get_substep_text_coupon_codes();
		$fragments['.wfc-step__substep-text-content--coupon-codes'] = $html;
		return $fragments;
	}

	/**
	 * Output coupon codes substep in text format for when the step is completed.
	 */
	public function output_substep_text_coupon_codes() {
		echo $this->get_substep_text_coupon_codes();
	}



	/**
	 * Apply a coupon code when updating the checkout page, if a value is provided.
	 * 
	 * @param array $posted_data Post data for all checkout fields.
	 */
	public function maybe_apply_coupon_code( $posted_data ) {
		// Get parsed posted data
		$parsed_posted_data = $this->get_parsed_posted_data();
		
		// If a coupon code was entered, try to apply it
		if ( array_key_exists( 'coupon_code', $parsed_posted_data ) && ! empty( $parsed_posted_data[ 'coupon_code' ] ) ) {
			$coupon_code = wc_format_coupon_code( wp_unslash( $parsed_posted_data[ 'coupon_code' ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			WC()->cart->add_discount( $coupon_code );
		}
		
		return $posted_data;
	}

	/**
	 * Apply a coupon code when processing checkout to place an order, if a value is provided.
	 * 
	 * @param array $posted_data Post data for all checkout fields.
	 */
	public function maybe_apply_coupon_code_on_process_checkout() {
		// If a coupon code was entered, try to apply it
		if ( array_key_exists( 'coupon_code', $_POST ) && ! empty( $_POST[ 'coupon_code' ] ) ) {
			$coupon_code = wc_format_coupon_code( wp_unslash( $_POST[ 'coupon_code' ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			
			// Apply coupon code to the Cart and Order
			WC()->cart->add_discount( $coupon_code );
			
			// Maybe remove notice "coupon added successfuly" as it is not needed after the order is completed
			$coupon_class = new WC_Coupon(); // A new instance of `WC_Coupon` is needed to use the function `get_coupon_message`.
			$coupon_added_message = $coupon_class->get_coupon_message( WC_Coupon::WC_COUPON_SUCCESS );
			if ( wc_has_notice( $coupon_added_message, 'success' ) ) {
				// Get all success notices
				$all_notices = wc_get_notices();
				$success_notices = $all_notices[ 'success' ];

				// Search for the message and get the array key
				$notice_key = array_search( $coupon_added_message, wp_list_pluck( $success_notices, 'notice' ), true );

				// Maybe remove the notice from the list
				if ( $notice_key !== false ) {
					unset( $success_notices[ $notice_key ] );
					
					// Update the notices
					$all_notices[ 'success' ] = $success_notices;
					wc_set_notices( $all_notices );
				}
			}
		}
	}

}

FluidCheckout_CouponCodes::instance();
