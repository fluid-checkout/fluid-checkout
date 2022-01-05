<?php
defined( 'ABSPATH' ) || exit;

/**
 * Feature for adding packing slips options.
 */
class FluidCheckout_PackingSlips extends FluidCheckout {

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
		// Admin Settings
		add_filter( 'fc_checkout_general_settings', array( $this, 'add_setting' ), 10 );
	}



	/**
	 * Add a packing slip information message option to the Fluid Checkout general settings page.
	 *
	 * @param   array  $settings  Admin settings array.
	 */
	public function add_setting( $settings ) {
		// Define positions for new settings
		$index = count( $settings ) - 1;

		// Define setting to insert
		$insert_settings = array(
			array(
				'title'             => __( 'Packing Slips', 'fluid-checkout' ),
				'desc'              => __( 'Information message printed on the packing slips. May be replaced with the gift message for order with a gift message added.', 'fluid-checkout' ),
				'id'                => 'fc_packing_slips_message_box_body_text',
				'type'              => 'textarea',
				'autoload'          => false,
			),
		);

		// Get token position
		$position_index = count( $settings ) - 1;
		for ( $index = 0; $index < count( $settings ) - 1; $index++ ) {
			$args = $settings[ $index ];

			if ( array_key_exists( 'id', $args ) && $args[ 'id' ] == 'fc_display_gift_message_in_order_details' ) {
				$position_index = $index + 1;
			}
		}

		// Insert at token position
		$new_settings  = array_slice( $settings, 0, $position_index );
		$new_settings = array_merge( $new_settings, $insert_settings );
		$new_settings = array_merge( $new_settings, array_slice( $settings, $position_index, count( $settings ) ) );

		return $new_settings;
	}



	/**
	 * Get the message box markup for packing slip documents.
	 *
	 * @param   int  $order_id   ID of the order.
	 */
	public function get_message_box_html( $order_id ) {
		// Get message values
		$args = apply_filters( 'fc_packing_slips_message_box_args', array(
			'message_body' => get_option( 'fc_packing_slips_message_box_body_text', '' ),
			'message_footer' => '',
			'info_text' => '',
			'message_icon_url' => self::$directory_url . 'images/icon-info.png',
			'message_body_extra_classes' => '',
		), $order_id );

		// Replace `[siteurl]` with the actual website url
		$args['message_body'] = str_replace( '[siteurl]', esc_url( get_option( 'siteurl' ) ), $args['message_body'] );
		$args['info_text'] = str_replace( '[siteurl]', esc_url( get_option( 'siteurl' ) ), $args['info_text'] );

		// Bail if there is no message body
		if ( empty( $args['message_body'] ) ) { return ''; };

		$allowed_html = array(
			'a' => array(
				'href' => array(),
				'title' => array()
			),
			'em' => array(),
			'strong' => array(),
		);

		// Start buffer
		ob_start();
		?>
		<div class="packing-list__message-wrapper">
			<div class="packing-list__message-box">
				<div class="packing-list__message-icon"><img src="<?php echo esc_url( $args['message_icon_url'] ); ?>"/></div>

				<div class="packing-list__message-body <?php echo esc_attr( $args['message_body_extra_classes'] ); ?>"><?php echo wp_kses_post( $args['message_body'] ); ?></div>

				<?php if ( ! empty( $args['message_footer'] ) ) : ?>
					<div class="packing-list__message-footer"><?php echo wp_kses( $args['message_footer'], $allowed_html ); ?></div>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $args['info_text'] ) ) : ?>
				<div class="packing-list__info-text"><?php echo wp_kses( $args['info_text'], $allowed_html ); ?></div>
			<?php endif; ?>
		</div>
		<?php

		// Return html
		return ob_get_clean();
	}



	/**
	 * Get message box styles for packing list.
	 */
	public function get_message_box_styles() {
		$css = '
			.packing-list__message-wrapper {
				margin: 20px 0 35px;
				position: relative;
			}

			.packing-list__message-box {
				position: relative;
				margin-left: 12.5%;
				width: 75%;
				height: 190px;
				border: dashed 1px #ccc;
				border-radius: 5px;
				text-align: center;
			}

			.packing-list__message-icon {
				display: block;
				margin-top: -15px;
				margin-left: 50%;
				transform: translateX( -50% );
				width: 100px;
				background-color: #fff;
			}
			.packing-list__message-icon img {
				width: 30px;
				height: 30px;
			}

			.packing-list__message-title {
				font-family: "Open Sans", Helvetica, Helvetica Neue, Verdana, serif;
				font-size: 16px;
				font-weight: bold;
			}

			.packing-list__message-body {
				padding: 45px 30px 0;
				width: 100%;
				height: 115px;
				font-family: "Open Sans", Helvetica, Helvetica Neue, Verdana, serif;
				font-size: 18px;
				font-weight: normal;
			}
			.packing-list__message-body.packing-list__message-body--gift-message {
				font-style: italic;
				height: 115px;
				padding-top: 15px;
				padding-bottom: 20px;
			}

			.packing-list__message-footer {
				width: 100%;
				font-family: "Open Sans", Helvetica, Helvetica Neue, Verdana, serif;
				font-size: 12px;
				font-weight: bold;
			}

			.packing-list__info-text {
				font-size: 10px;
				text-align: center;
			}
		';

		return $css;
	}

}

FluidCheckout_PackingSlips::instance();
