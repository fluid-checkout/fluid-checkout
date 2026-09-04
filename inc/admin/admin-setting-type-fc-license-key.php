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

		$plugin_config = array();
		if ( ! empty( $value['plugin_slug'] ) && class_exists( 'FC_Licenses_Client' ) ) {
			$plugin_config = FC_Licenses_Client::get_plugin_config( $value['plugin_slug'] );
		}

		$license_key_hash = '';
		if ( ! empty( $plugin_config['license_key_hash_option'] ) ) {
			$license_key_hash = get_option( $plugin_config['license_key_hash_option'], '' );
		}

		// Cache identity: prefer entered key, else stored hash
		$status_cache_token = ! empty( $option_value ) ? $option_value : (string) $license_key_hash;

		// Maybe call license API and update license status transient.
		if (
			! empty( $status_cache_token )
			&& ( ! is_array( $license_status ) || $status_cache_token !== $license_status[ 'license_key' ] )
			&& ! empty( $value['plugin_slug'] )
			&& class_exists( 'FC_Licenses_Client' )
			&& method_exists( 'FC_Licenses_Client', 'get_license_key_details' )
			&& ! empty( $plugin_config )
		) {
			$config = $plugin_config;

			if ( ! empty( $option_value ) ) {
				$config['license_key'] = $option_value;
			}

			$api_result = FC_Licenses_Client::get_license_key_details(
				$value['plugin_slug'],
				$config['plugin_file'],
				$config
			);

			$license_status = $this->maybe_set_license_status_transient( $status_cache_token, $api_result, $license_status_transient_id );
		}
		elseif ( empty( $option_value ) && empty( $license_key_hash ) ) {
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
		// translators: %s: Product URL.
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
					$license_key_expiration_date = date( 'Y-m-d', $license_status[ 'expiration' ] );
					// translators: %s: License key expiration date.
					$license_key_status_text = sprintf( __( 'Expired on %s.', 'fluid-checkout' ), $license_key_expiration_date );
					$license_key_status_class = 'fc-license-key__status-label--expired';
					$license_action_html = __( '<a href="https://fluidcheckout.com/account/" target="_blank">Log in to your account</a> to renew your license key and continue to receive updates and support.', 'fluid-checkout' );
					break;
				case 'cancelled':
					$license_key_status_text = __( 'License key cancelled.', 'fluid-checkout' );
					$license_key_status_class = 'fc-license-key__status-label--cancelled';
					// translators: %s: Product URL.
					$license_action_html = sprintf( __( '<a href="%s" target="_blank">Purchase a new license key</a> and continue to receive updates and support.', 'fluid-checkout' ), esc_url( $value[ 'product_url' ] ) );
					break;
				case 'error':
					$license_key_status_text = __( 'Error: ', 'fluid-checkout' ) . $license_status[ 'data' ] . '<br/>';
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

					<p class="fc-license-key__status"><strong class="<?php echo esc_attr( $license_key_status_class ); ?>"><?php echo wp_kses_post( $license_key_status_text ); ?></strong> <?php echo wp_kses_post( $license_action_html ); ?></p>
			</td>
		</tr>
		<?php
	}



	/**
	 * Maybe set license status transient from API results.
	 *
	 * @param   string  $option_value                 License key option value.
	 * @param   object  $api_result                   License API result.
	 * @param   string  $license_status_transient_id  License status transient ID.
	 */
	private function maybe_set_license_status_transient( $option_value, $api_result, $license_status_transient_id ) {
		$license_key_status = 'error';
		$license_key_expiration_timestamp = null;

		// Determine initial transient expiration.
		$transient_expiration = 60 * 60 * 24; // 24 hours.

		// Process license key status from API response.
		if (
			is_object( $api_result )
			&& isset( $api_result->data )
			&& is_object( $api_result->data )
			&& isset( $api_result->data->id )
			&& ! isset( $api_result->code )
		) {
			$status_slug = isset( $api_result->data->status ) ? sanitize_key( (string) $api_result->data->status ) : '';
			$expires_at  = isset( $api_result->data->expires_at ) ? $api_result->data->expires_at : null;

			$license_key_expiration_timestamp = null !== $expires_at && '' !== $expires_at
				? strtotime( (string) $expires_at )
				: null;

			$license_key_is_expired = null !== $license_key_expiration_timestamp && time() > $license_key_expiration_timestamp;

			if ( 'cancelled' === $status_slug ) {
				$license_key_status = 'cancelled';
			} elseif ( 'expired' === $status_slug || $license_key_is_expired ) {
				$license_key_status = 'expired';
			} elseif ( 'active' === $status_slug || 'available' === $status_slug || 'delivered' === $status_slug ) {
				$license_key_status = 'active';
			}

			$license_status = array(
				'license_key' => $option_value,
				'status'      => $license_key_status,
				'expiration'  => $license_key_expiration_timestamp,
				'data'        => $api_result->data,
			);

			set_transient( $license_status_transient_id, $license_status, $transient_expiration );

			return $license_status;
		}

		$license_status = array(
			'license_key' => $option_value,
			'status' => 'error',
			'data' => is_object( $api_result ) && isset( $api_result->message ) ? $api_result->message : '',
		);

		set_transient( $license_status_transient_id, $license_status, $transient_expiration );

		return $license_status;
	}

}

FluidCheckout_Admin_SettingType_LicenseKey::instance();
