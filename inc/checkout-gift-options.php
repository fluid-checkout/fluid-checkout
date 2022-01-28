<?php
defined( 'ABSPATH' ) || exit;

/**
 * Feature for adding gift options to checkout
 */
class FluidCheckout_GiftOptions extends FluidCheckout {

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

		// Checkout
		add_action( 'fc_output_step_shipping', array( $this, 'output_substep_gift_options' ), 90 );
		add_filter( 'fc_substep_gift_options_text_lines', array( $this, 'add_substep_text_lines_gift_options' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_gift_options_text_fragment' ), 10 );

		// Order Admin Screen
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'display_gift_options_fields_order_admin_screen' ), 100, 1 );

		// Clear session after placing order
		add_filter( 'fc_customer_persisted_data_clear_fields_order_processed', array( $this, 'change_customer_persisted_data_clear_fields_order_processed' ), 10 );
		add_filter( 'fc_customer_persisted_data_clear_all_fields_skip_list', array( $this, 'change_customer_persisted_data_clear_fields_skip_list' ), 10 );

		// Save gift fields to order
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_order_meta_with_gift_options_fields' ), 10, 1 );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_order_gift_details' ) );

		// Order Details
		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'maybe_add_gift_message_order_received_details_table' ), 30, 3 );
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'output_gift_message_order_details' ), 10 );

		// Prevent hiding optional gift option fields behind a link button
		add_filter( 'fc_hide_optional_fields_skip_list', array( $this, 'prevent_hide_optional_fields_gift_options' ), 10 );

		// Emails
		add_action( 'woocommerce_email_after_order_table', array( $this, 'output_gift_message_order_details_email' ), 10, 4 );
		add_filter( 'woocommerce_email_styles', array( $this, 'add_email_styles' ), 10, 2 );

		// Packing slips
		add_filter( 'fc_packing_slips_message_box_args', array( $this, 'change_packing_slip_message_box_args' ), 10, 2 );
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
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ){ return $classes; }

		return array_merge( $classes, array( 'has-fc-gift-options' ) );
	}



	/**
	 * Output gift options substep.
	 *
	 * @param   string  $step_id  Id of the step in which the substep will be rendered.
	 */
	public function output_substep_gift_options( $step_id ) {
		$substep_id = 'gift_options';
		$substep_title = __( 'Gift options', 'fluid-checkout' );
		$this->checkout_steps()->output_substep_start_tag( $step_id, $substep_id, $substep_title );

		$this->checkout_steps()->output_substep_fields_start_tag( $step_id, $substep_id );
		$this->output_gift_options_fields();
		$this->checkout_steps()->output_substep_fields_end_tag();

		// Only output substep text format for multi-step checkout layout
		if ( $this->checkout_steps()->is_checkout_layout_multistep() ) {
			$this->checkout_steps()->output_substep_text_start_tag( $step_id, $substep_id );
			$this->output_substep_text_gift_options();
			$this->checkout_steps()->output_substep_text_end_tag();
		}

		$this->checkout_steps()->output_substep_end_tag( $step_id, $substep_id, $substep_title );
	}



	/**
	 * Add the gift options substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_gift_options( $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }
		
		// Get gift options fields
		$gift_options_fields = $this->get_gift_options_fields();

		// Only display gift options text when a message is added
		if ( array_key_exists( '_fc_gift_message', $gift_options_fields ) && ! empty( WC()->checkout()->get_value( '_fc_gift_message' ) ) ) {
			foreach ( $gift_options_fields as $field_key => $field_args ) {
				$review_text_lines[] = WC()->checkout()->get_value( $field_key );
			}
		}
		else {
			$review_text_lines[] = apply_filters( 'fc_no_gift_options_order_review_notice', _x( 'None.', 'Notice for no gift options provided', 'fluid-checkout' ) );
		}

		return $review_text_lines;
	}

	/**
	 * Get gift options substep review text.
	 */
	public function get_substep_text_gift_options() {
		return FluidCheckout_Steps::instance()->get_substep_review_text( 'gift_options' );
	}

	/**
	 * Add gift options substep review text as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_gift_options_text_fragment( $fragments ) {
		$html = $this->get_substep_text_gift_options();
		$fragments['.fc-step__substep-text-content--gift_options'] = $html;
		return $fragments;
	}

	/**
	 * Output gift options substep review text.
	 */
	public function output_substep_text_gift_options() {
		echo $this->get_substep_text_gift_options();
	}



	/**
	 * Get the gift options fields settings.
	 *
	 * @return  array  Gift options fields array in the format expected by WooCommerce.
	 */
	public function get_gift_options_fields() {
		// Define gift options fields
		$message_maxlength = apply_filters( 'fc_gift_options_message_length', false );
		$gift_option_fields = array(
			'_fc_gift_message' => array(
				'type'          => 'textarea',
				'class'         => array( 'form-row-wide '),
				'label'         => __( 'Gift message', 'fluid-checkout' ),
				'placeholder'   => __( 'Write a gift message...', 'fluid-checkout' ),
				'description'   => $message_maxlength ? sprintf( __( 'Brief message with up to %d characters, printed on the packing slip.', 'fluid-checkout' ), $message_maxlength ) : __( 'Brief message, printed on the packing slip.', 'fluid-checkout' ),
				'default'		=> is_checkout() ? WC()->checkout()->get_value( '_fc_gift_message' ) : null,
				'maxlength'		=> $message_maxlength,
				'custom_attributes' => array(
					'data-autofocus' => true,
				),
			),

			'_fc_gift_from' => array(
				'type'          => 'text',
				'class'         => array( 'form-row-wide '),
				'label'         => _x( 'From', 'Field label for person sending the gift', 'fluid-checkout' ),
				'placeholder'   => __( 'Your name', 'fluid-checkout' ),
				'description'   => __( 'Name of who is sending the gift, printed on the packing slip.', 'fluid-checkout' ),
				'default'		=> is_checkout() ? WC()->customer->get_display_name() : null,
				'maxlength'		=> apply_filters( 'fc_gift_options_from_length', false ),
			),
		);

		return apply_filters( 'fc_gift_options_fields_args', $gift_option_fields );
	}

	/**
	 * Get the list of gift message field IDs.
	 *
	 * @return  array  List of gift message field IDs.
	 */
	public function get_gift_message_field_ids() {
		return array( '_fc_gift_message', '_fc_gift_from' );
	}



	/**
	 * Prevent hiding optional gift option fields behind a link button.
	 *
	 * @param   array  $skip_list  List of optional fields to skip hidding.
	 */
	public function prevent_hide_optional_fields_gift_options( $skip_list ) {
		$skip_list = array_merge( $skip_list, array( '_fc_gift_message', '_fc_gift_from' ) );
		return $skip_list;
	}



	/**
	 * Output gift options fields.
	 *
	 * @param   WC_Checkout   $checkout   The Checkout object.
	 */
	public function output_gift_options_fields() {
		// Output gift options form template
		wc_get_template(
			'fc/checkout/form-gift-options.php',
			array(
				'checkout'                 => WC()->checkout(),
				'gift_options'             => $this->get_gift_options_session(),
				'gift_options_fields'      => $this->get_gift_options_fields(),
			)
		);
	}



	/**
	 * Get gift options values from session.
	 *
	 * @return  array  The gift options fields values saved to session.
	 */
	public function get_gift_options_session() {
		$gift_options = array();

		// Get gift options fields
		$gift_options_fields = $this->get_gift_options_fields();

		// Get gift option field values from session
		foreach ( $gift_options_fields as $field_key => $field_args ) {
			$gift_options[ $field_key ] = $this->checkout_steps()->get_checkout_field_value_from_session( $field_key );
		}

		return $gift_options;
	}

	/**
	 * Change the list of fields to clear from session after placing an order, adding the gift option fields to be cleared.
	 *
	 * @param   array  $clear_field_keys  Checkout field keys to clear from the session after placing an order.
	 */
	public function change_customer_persisted_data_clear_fields_order_processed( $clear_field_keys ) {
		return array_merge( $clear_field_keys, array_keys( $this->get_gift_options_fields() ) );
	}
	
	/**
	 * Change the list of fields to clear from session after placing an order, adding the gift option fields to be cleared.
	 *
	 * @param   array  $clear_field_keys  Checkout field keys to clear from the session after placing an order.
	 */
	public function change_customer_persisted_data_clear_fields_skip_list( $clear_field_keys ) {
		return array_merge( $clear_field_keys, array_keys( $this->get_gift_options_fields() ) );
	}



	/**
	 * Update the order meta with gift fields value.
	 *
	 * @param   int  $order_id  Order ID.
	 */
	public function update_order_meta_with_gift_options_fields( $order_id ) {
		// Get gift options fields
		$gift_options_fields = $this->get_gift_options_fields();

		// Get gift message value
		$gift_message = wc_clean( wp_unslash( $_POST[ '_fc_gift_message' ] ) );

		// Save values for each field to the order meta
		foreach ( $gift_options_fields as $key => $field ) {
			$field_value = isset( $_POST[ $key ] ) ? wc_clean( wp_unslash( $_POST[ $key ] ) ) : null;

			// Maybe unset gift message `from` field
			if ( $key === '_fc_gift_from' && empty( $gift_message ) ) {
				$field_value = null;
			}

			// Update order meta data for the current field
			if ( ! empty( $field_value ) ) {
				update_post_meta( $order_id, $key, $field_value );
			}
			else {
				delete_post_meta( $order_id, $key );
			}
		}
	}



	/**
	 * Get gift options values from saved the order meta.
	 *
	 * @param   int  $order_id  Order ID.
	 */
	public function get_gift_options_from_order( $order_id ) {
		// Get gift options values
		$gift_options = array();

		// Get values for each field
		$gift_options_fields = $this->get_gift_options_fields();
		foreach ( $gift_options_fields as $key => $field ) {
			$gift_options[ $key ] = get_post_meta( $order_id, $key, true );
		}

		return $gift_options;
	}



	/**
	 * Display gift options fields on order admin screen.
	 *
	 * @param   WC_Order   $order   The Order object.
	 */
	public function display_gift_options_fields_order_admin_screen( $order ) {
		$order_id = $order->get_id();

		// Map field types from the frontend to the field types available in the admin area
		$admin_field_types = array(
			'default' => 'woocommerce_wp_text_input',
			'textarea' => 'woocommerce_wp_textarea_input',
			'select' => 'woocommerce_wp_select',
			'checkbox' => 'woocommerce_wp_checkbox',
			'hidden' => 'woocommerce_wp_hidden_input',
		);

		// Get gift options
		$gift_options = $this->get_gift_options_from_order( $order_id );

		// Get gift options fields
		$gift_options_fields = $this->get_gift_options_fields();
		?>
			<br class="clear" />

			<?php // TODO: Move the gift options edit section to its own metabox and template file ?>
			<div class="order_data_column" style="width: 100%">

				<h4><?php echo esc_html( __( 'Gift options', 'Title for gift options on admin order details screen', 'fluid-checkout' ) ); ?> <a href="#" class="edit_address"><?php echo esc_html( _x( 'Edit', 'Edit gift options link on admin order details screen', 'fluid-checkout' ) ); ?></a></h4>

				<div class="address">
					<?php
					// Output values in text format
					foreach ( $gift_options_fields as $key => $field ) {
						if ( ! empty( $gift_options[ $key ] ) ) {
							echo '<p><strong>'. esc_html( $field[ 'label' ] ) . '</strong>' . esc_html( $gift_options[ $key ] ) . '</p>';
						}
					}
					?>
				</div>

				<div class="edit_address">
					<?php
					// Output edit fields
					foreach ( $gift_options_fields as $key => $field ) {
						$field_args = array(
							'id' => $key,
							'label' => $field[ 'label' ],
							'value' => $gift_options[ $key ],
							'wrapper_class' => 'form-field-wide'
						);

						// Maybe add options
						if ( array_key_exists( 'options', $field ) ) {
							$field_args[ 'options' ] = $field[ 'options' ];
						}

						// Output edit field
						$callable_field_func = array_key_exists( $field[ 'type' ], $admin_field_types ) ? $admin_field_types[ $field[ 'type' ] ] : $admin_field_types[ 'default' ];
						$callable_field_func( $field_args );
					}
					?>
				</div>

			</div>
		<?php
	}



	/**
	 * Save order meta data for gift message.
	 *
	 * @param   int  $order_id  Order ID.
	 */
	public function save_order_gift_details( $order_id ) {
		
		// TODO: Get list of gift fields and iterate to save or delete each post meta, see `update_order_meta_with_gift_options_fields`

		$gift_message = wc_clean( wp_unslash( $_POST[ '_fc_gift_message' ] ) );
		$gift_from = wc_clean( wp_unslash( $_POST[ '_fc_gift_from' ] ) );

		if ( ! empty( $gift_message ) ) {
			update_post_meta( $order_id, '_fc_gift_message', $gift_message );
			update_post_meta( $order_id, '_fc_gift_from', $gift_from );
		}
		else {
			delete_post_meta( $order_id, '_fc_gift_message' );
			delete_post_meta( $order_id, '_fc_gift_from' );
		}
	}



	/**
	 * Wheter the gift message should be displayed as a separate section in the order details.
	 *
	 * @return  bool  `true` if displaying the gift message as a separate section, `false` if displaying the gift message as part of the order details table.
	 */
	public function is_gift_message_in_order_details() {
		return get_option( 'fc_display_gift_message_in_order_details', 'no' ) == 'yes';
	}



	/**
	 * Maybe add gift message values to the order details table.
	 *
	 * @param array  $total_rows  Total rows.
	 * @param   WC_Order   $order   The Order object.
	 * @param string $tax_display Tax to display.
	 */
	public function maybe_add_gift_message_order_received_details_table( $total_rows, $order, $tax_display ) {
		// Bail if displaying gift message as a separate section
		if ( ! $this->is_gift_message_in_order_details() ) { return $total_rows; }

		// Get token position
		$position_index = array_search( 'shipping_address', array_keys( $total_rows ) ) + 1;

		// Get gift options
		$gift_options = $this->get_gift_options_from_order( $order->get_id() );

		// Get gift message value
		$gift_message = $gift_options[ '_fc_gift_message' ];
		$gift_from = $gift_options[ '_fc_gift_from' ];

		// Insert at token position
		$new_total_rows  = array_slice( $total_rows, 0, $position_index );

		// Gift message
		if ( ! empty( $gift_message ) ) {
			$new_total_rows[ 'gift_message' ] = array(
				'label' => __( 'Gift message:', 'fluid-checkout' ),
				'value' => $gift_message,
			);
		}

		// Gift message from
		if ( ! empty( $gift_from ) ) {
			$new_total_rows[ 'gift_message_from' ] = array(
				'label' => __( 'Gift message from:', 'fluid-checkout' ),
				'value' => $gift_from,
			);
		}

		$new_total_rows = array_merge( $new_total_rows, array_slice( $total_rows, $position_index, count( $total_rows ) ) );

		return $new_total_rows;
	}



	/**
	 * Output the gift message values to the order details as a section below the order details table.
	 *
	 * @param   WC_Order   $order   The Order object.
	 */
	public function output_gift_message_order_details( $order ) {
		// Bail if already displaying message text on the order details table
		if ( $this->is_gift_message_in_order_details() ) { return; }

		// Bail if not on order received page.
		if( ( ! function_exists( 'is_checkout' ) || ! is_checkout() && ! is_order_received_page() ) && ! is_wc_endpoint_url( 'view-order' ) ){ return; }

		// Get gift options
		$gift_options = $this->get_gift_options_from_order( $order->get_id() );

		// Bail if gift message not added to the order
		if ( ! array_key_exists( '_fc_gift_message', $gift_options ) || empty( $gift_options['_fc_gift_message'] ) ) { return; }

		// Output gift options section template
		wc_get_template(
			'fc/order/order-details-gift-options.php',
			array(
				'order'                    => $order,
				'gift_options'             => $gift_options,
			)
		);
	}


	/**
	 * * Maybe output the gift message values to the order details as a section below the order details table.
	 *
	 * @param WC_Order $order         Order instance.
	 * @param bool     $sent_to_admin If should sent to admin.
	 * @param bool     $plain_text    If is plain text email.
	 * @param string   $email         Email address.
	 */
	public function output_gift_message_order_details_email( $order, $sent_to_admin, $plain_text, $email ) {
		// Bail if already displaying message text on the order details table
		if ( $this->is_gift_message_in_order_details() ) { return; }

		// Get gift options
		$gift_options = $this->get_gift_options_from_order( $order->get_id() );

		// Bail if gift message not added to the order
		if ( ! array_key_exists( '_fc_gift_message', $gift_options ) || empty( $gift_options['_fc_gift_message'] ) ) { return; }

		if ( $plain_text ) {
			// Output gift options section template
			wc_get_template(
				'fc/order/order-details-gift-options-email-plain-text.php',
				array(
					'order'                    => $order,
					'gift_options'             => $gift_options,
				)
			);
		}
		else {
			// Output gift options section template
			wc_get_template(
				'fc/order/order-details-gift-options.php',
				array(
					'order'                    => $order,
					'gift_options'             => $gift_options,
				)
			);
		}
	}



	/**
	 * Add the gift options email styles.
	 *
	 * @param   string    $css    CSS code for the email styles.
	 * @param   WC_Email  $email  WooCommerce email object.
	 */
	public function add_email_styles( $css, $email = null ) {
		// Bail if email type does not have gift message, skip email type check and output the styles if the `$email` variable is null
		$allowed_email_ids = array( 'new_order', 'customer_invoice', 'failed_order', 'cancelled_order', 'customer_completed_order', 'customer_note', 'customer_on_hold_order', 'customer_processing_order', 'customer_refunded_order' );
		if ( null !== $email && ! in_array( $email->id, $allowed_email_ids ) ) { return $css; }

		$css_file_path = self::$directory_path . 'css/gift-message-email-styles'. self::$asset_version . '.css';

		// Get styles from file, if it exists
		if ( file_exists( $css_file_path ) ) {
			$file_contents = file_get_contents( $css_file_path );

			if( $file_contents ) {
				$css = $css . PHP_EOL . $file_contents;
			}
		}

		return $css;
	}



	/**
	 * Get the message box markup for packing slip documents.
	 *
	 * @param   array   $args           Packing slip message box arguments.
	 * @param   int     $order_id       ID of the order.
	 */
	public function change_packing_slip_message_box_args( $args, $order_id ) {
		// Bail if order_id invalid
		if ( ! $order_id ) { return $args; }

		// Get gift options
		$gift_options = $this->get_gift_options_from_order( $order_id );

		// Bail if no order does not have a gift message
		if ( empty( $gift_options[ '_fc_gift_message' ] ) ) { return $args; }

		// Get message values for gift
		$args[ 'info_text' ] = $args[ 'message_body' ];
		$args[ 'message_body_extra_classes' ] = $args[ 'message_body_extra_classes' ] . ' packing-list__message-body--gift-message';
		$args[ 'message_body' ] = $gift_options[ '_fc_gift_message' ];
		$args[ 'message_footer' ] = $gift_options[ '_fc_gift_from' ];
		$args[ 'message_icon_url' ] = self::$directory_url . 'images/icon-gift-package.png';

		return $args;
	}

}

FluidCheckout_GiftOptions::instance();
