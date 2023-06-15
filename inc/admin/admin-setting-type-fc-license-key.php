<?php
defined( 'ABSPATH' ) || exit;

/**
 * Checkout admin options.
 */
class FluidCheckout_Admin_SettingType_LicenseKey extends FluidCheckout {

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
		// Field types
		add_action( 'woocommerce_admin_field_fc_license_key', array( $this, 'output_field' ), 10 );
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		// Custom attribute handling.
		$custom_attributes = array();
		if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		// Description handling.
		$field_description = WC_Admin_Settings::get_field_description( $value );
		$description       = $field_description['description'];
		$tooltip_html      = $field_description['tooltip_html'];

		// Get option value.
		$option_value = $value['value'];

		// Get status transients.
		$license_status_transient_id = $value[ 'id' ] . '_status';
		$license_status = get_transient( $license_status_transient_id );

		// Get license key value from the database
		$license_key_saved = FluidCheckout_Settings::instance()->get_option( $value[ 'id' ] );

		// Maybe call license API and update license status transient.
		if (
			! empty( $option_value )
			// && ( false === $license_status || ! is_array( $license_status ) )
			&& ( ! is_array( $license_status ) || $option_value !== $license_status[ 'license_key' ] )
			&& array_key_exists( 'license_manager', $value )
			&& 'object' === gettype( $value[ 'license_manager' ] )
			&& method_exists( $value[ 'license_manager' ], 'get_info' )
		) {
			// Call license manager API.
			$license_manager = $value[ 'license_manager' ];
			$api_result = $license_manager->get_info( $option_value );

			// Determine initial transient expiration.
			$transient_expiration = 60 * 60 * 24; // 24 hours.

			// Maybe set transient based on API results.
			if ( property_exists( $api_result, 'success' ) && true === $api_result->success ) {
				// Update transient in memory
				$license_info_transient = $api_result->data;

				// Maybe set status based on API results saved in transient.
				if ( ! empty( $option_value ) && null !== $api_result->data ) {
					// Determine if license key is expired.
					$license_key_expiration_timestamp = null === $api_result->data->expiresAt ? strtotime( $api_result->data->createdAt ) + ( $api_result->data->validFor * 60 * 60 * 24 ) : strtotime( $api_result->data->expiresAt );
					$license_key_expiration_date = date( 'Y-m-d', $license_key_expiration_timestamp );
					$license_key_is_expired = time() > $license_key_expiration_timestamp;

					// Determine if license key is active.
					$license_key_active_statuses = array(
						2, // Delivered
						3, // Active
						5, // Disabled (but still not expired)
					);
					$license_key_is_active = ! $license_key_is_expired && in_array( $api_result->data->status, $license_key_active_statuses );

					// Define license key status text
					if ( 6 === $api_result->data->status ) { // 6 = Cancelled
						$license_key_status = 'cancelled';
					}
					elseif ( $license_key_is_active ) {
						$license_key_status = 'active';
					}
					elseif ( $license_key_is_expired ) {
						$license_key_status = 'expired';
					}
				}

				// Update license status object.
				$license_status = array(
					'license_key' => $option_value,
					'status' => $license_key_status,
					'expiration' => $license_key_expiration_timestamp,
					'data' => $api_result->data,
				);

				// Update transient in database.
				set_transient( $license_status_transient_id, $license_status, $transient_expiration );
			}
			// Maybe set status to error and delete transient.
			else {
				// Update license status object.
				$license_status = array(
					'license_key' => $option_value,
					'status' => 'error',
					'data' => $api_result->message,
				);

				// Update transient in database.
				set_transient( $license_status_transient_id, $license_status, $transient_expiration );
			}
		}
		elseif ( empty( $option_value ) ) {
			$license_status = array(
				'license_key' => '',
				'status' => 'empty',
			);
			// Delete transient when license key is empty.
			delete_transient( $license_status_transient_id );
		}

		// Determine default license key status.
		$license_key_status = 'empty';
		$license_key_status_class = 'fc-license-key__status-label--empty';
		$license_key_status_text = '';
		$license_action_html = sprintf( __( '<a href="https://fluidcheckout.com/account/" target="_blank">Log in to your account</a> to get your license key, or <a href="%s" target="_blank">purchase a new license key</a>.', 'fluid-checkout' ), esc_url( $value[ 'product_url' ] ) );

		if ( is_array( $license_status ) ) {
			// Get status from transient.
			$license_key_status = $license_status[ 'status' ];

			switch ( $license_status[ 'status' ] ) {
				case 'active':
					$active_until_date = date( 'Y-m-d', $license_status[ 'expiration' ] - ( 60 * 60 * 24 ) ); // Expiration date - 1 day.
					// translators: %s: License key expiration date.
					$license_key_status_text = sprintf( __( 'Valid until %s.', 'fluid-checkout' ), $active_until_date );
					$license_key_status_class = 'fc-license-key__status-label--active';
					$license_action_html = '';
					break;
				case 'expired':
					// translators: %s: License key expiration date.
					$license_key_status_text = sprintf( __( 'Expired on %s.', 'fluid-checkout' ), $license_key_expiration_date );
					$license_key_status_class = 'fc-license-key__status-label--expired';
					$license_action_html = __( '<a href="https://fluidcheckout.com/account/" target="_blank">Log in to your account</a> to renew your license key and continue to receive updates and support.', 'fluid-checkout' );
					break;
				case 'cancelled':
					$license_key_status_text = __( 'License key cancelled.', 'fluid-checkout' );
					$license_key_status_class = 'fc-license-key__status-label--cancelled';
					$license_action_html = sprintf( __( '<a href="%s" target="_blank">Purchase a new license key</a> and continue to receive updates and support.', 'fluid-checkout' ), esc_url( $value[ 'product_url' ] ) );
					break;
				case 'error':
					$license_key_status_text = __( 'Error: ', 'fluid-checkout' ) . $license_status[ 'data' ];
					$license_key_status_class = 'fc-license-key__status-label--error';
			}
		}

		?><tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
				<input
					name="<?php echo esc_attr( $value['field_name'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					type="text"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					value="<?php echo esc_attr( $option_value ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
					<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
					/><?php echo esc_html( $value['suffix'] ); ?> <?php echo $description; // WPCS: XSS ok. ?>

					<p class="fc-license-key__status"><strong class="<?php echo esc_attr( $license_key_status_class ); ?>"><?php echo esc_html( $license_key_status_text ); ?></strong> <?php echo wp_kses_post( $license_action_html ); ?></p>
			</td>
		</tr>
		<?php
	}

}

FluidCheckout_Admin_SettingType_LicenseKey::instance();
