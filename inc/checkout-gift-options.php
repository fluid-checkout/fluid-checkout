<?php

/**
 * Feature for adding gift options to checkout
 */
class FluidCheckout_CheckoutGiftOptions extends FluidCheckout {

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
		if ( get_option( 'wfc_enable_checkout_gift_options', 'false' ) == 'true' ) {
			add_filter( 'body_class', array( $this, 'add_body_class' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'scripts_styles' ) );

			add_filter( 'woocommerce_after_order_notes' , array( $this, 'output_gift_packaging_fields' ), 10 );
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_order_meta_with_gift_packaging_fields' ), 10, 1 );
			
			add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'display_gift_packaging_fields_order_admin_screen' ), 100, 1 );
			add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_order_gift_details' ) );
		}
	}



	/**
	 * Add page body class for feature detection
	 */
	public function add_body_class( $classes ) {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ){ return $classes; }

		return array_merge( $classes, array( 'has-wfc-gift-options' ) );
	}



	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue() {

		// // Bail if not on checkout page.
		// if( !is_checkout() || is_order_received_page() ){ return; }

		// // TODO: Enable js minification.
		// // $min = '.min';
		// $min = ''; 

		// if ( defined('SCRIPT_DEBUG') && true === SCRIPT_DEBUG ) {
		// $min = '';
		// }

		// // Enqueue script and style
		// wp_enqueue_script( 'fluid-gift-packaging', self::$directory_url . "js/fluid-gift-packaging$min.js", array(), self::VERSION, true );
		// wp_enqueue_style( 'fluid-gift-packaging', self::$directory_url . "css/fluid-gift-packaging$min.css", null, self::VERSION );
	}



	/**
	 * Output gift packaging fields.
	 */
	public function output_gift_packaging_fields( $checkout ) {
		echo '<div id="woocommerce-gift-fields">';

		woocommerce_form_field( '_wfc_has_gift_options', array(
			'type'          => 'checkbox',
			'class'         => array( 'form-row-wide '),
			'label'         => __( 'Do you want to add a gift message?', 'woocommerce-fluid-checkout' ),
		), $checkout->get_value( '_wfc_has_gift_options' ) );

		// Wrapper for initially hidden fields
		echo '<div id="woocommerce-gift-options__field-wrapper">';

		woocommerce_form_field( '_gift_packaging_message', array(
			'type'          => 'textarea',
			'class'         => array( 'form-row-wide '),
			'label'         => __( 'Gift message', 'woocommerce-fluid-checkout' ),
			'placeholder'   => __( 'Write a special message...', 'woocommerce-fluid-checkout' ),
		), $checkout->get_value( '_gift_packaging_message' ) );

		woocommerce_form_field( '_gift_packaging_from', array(
			'type'          => 'text',
			'class'         => array( 'form-row-wide '),
			'label'         => __( 'From', 'woocommerce-fluid-checkout' ),
			'placeholder'   => __( 'Who is sending this gift?', 'woocommerce-fluid-checkout' ),
		), $checkout->get_value( 'billing_first_name' ) );

		echo '</div>';

		echo '</div>';
	}



	/**
	 * Update the order meta with gift fields value.
	 **/
	public function update_order_meta_with_gift_packaging_fields( $order_id ) {
		$is_gift_packaging = isset( $_POST['_wfc_has_gift_options'] ) && boolval( $_POST['_wfc_has_gift_options'] );
		$gift_packaging_message = isset( $_POST['_gift_packaging_message'] ) ? $_POST['_gift_packaging_message'] : '';
		$gift_packaging_from = isset( $_POST['_gift_packaging_from'] ) ? $_POST['_gift_packaging_from'] : '';

		// Update order meta
		update_post_meta( $order_id, '_wfc_has_gift_options', $is_gift_packaging ? 'Yes' : 'No' );
		update_post_meta( $order_id, '_gift_packaging_message', $is_gift_packaging ? $gift_packaging_message : '' );
		update_post_meta( $order_id, '_gift_packaging_from', $is_gift_packaging ? $gift_packaging_from : '' );
	}



	/**
	 * Display gift packaging fields on order admin screen.
	 **/
	public function display_gift_packaging_fields_order_admin_screen( $order ) {
		$order_id = $order->id;
		$gift_packaging_message = get_post_meta( $order_id, '_gift_packaging_message', true );
		$gift_packaging_from = get_post_meta( $order_id, '_gift_packaging_from', true );
		

		if ( $gift_packaging_message || $gift_packaging_from ) : ?>
		
		<br class="clear" />
		<h4>Gift Order <a href="#" class="edit_address">Edit</a></h4>

		<div class="address">
			<?php
			if ( $gift_packaging_message ) {
				echo '<p><strong>'. __( 'Gift Message:', 'woocommerce-fluid-checkout' ) . '</strong>' . $gift_packaging_message . '</p>';
			}

			if ( $gift_packaging_from ) {
				echo '<p><strong>'. __( 'Gift From:', 'woocommerce-fluid-checkout' ) . '</strong>' . $gift_packaging_from . '</p>';
			}
			?>
		</div>
		<div class="edit_address">
			<?php
			if ( $gift_packaging_message ) {
				woocommerce_wp_textarea_input( array(
				'id' => '_gift_packaging_message',
				'label' => __( 'Gift Message:', 'woocommerce-fluid-checkout' ),
				'value' => $gift_packaging_message,
				'wrapper_class' => 'form-field-wide'
				) );
			}

			if ( $gift_packaging_from ) {
				woocommerce_wp_text_input( array(
				'id' => '_gift_packaging_from',
				'label' => __( 'Gift From:', 'woocommerce-fluid-checkout' ),
				'value' => $gift_packaging_from,
				'wrapper_class' => 'form-field-wide'
				) );
			}
			?>
		</div>

		<?php
		endif;
	}


	
	/**
	 * Save order meta data for gift message
	 */
	public function save_order_gift_details( $order_id ){
		update_post_meta( $order_id, '_gift_packaging_message', wc_clean( $_POST[ '_gift_packaging_message' ] ) );
		update_post_meta( $order_id, '_gift_packaging_from', wc_sanitize_textarea( $_POST[ '_gift_packaging_from' ] ) );
	}

}

FluidCheckout_CheckoutGiftOptions::instance();
