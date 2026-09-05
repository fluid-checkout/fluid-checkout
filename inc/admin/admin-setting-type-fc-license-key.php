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

		// Scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ), 10 );
	}



	/**
	 * Register the setting type scripts.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function register_scripts( $hook ) {
		// Bail if not on WooCommerce Settings
		if ( 'woocommerce_page_wc-settings' !== $hook ) { return; }

		wp_register_script( 'fc-admin-license-key', FluidCheckout_Enqueue::instance()->get_script_url( 'js/admin/admin-license-key' ), array(), null, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		// Enqueue assets
		wp_enqueue_script( 'fc-admin-license-key' );

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
		$has_saved_value = ! empty( $option_value );

		// Get status transients.
		$license_status_transient_id = $value[ 'id' ] . '_status';
		$license_status = get_transient( $license_status_transient_id );

		$plugin_config = array();
		if ( ! empty( $value['plugin_slug'] ) && class_exists( 'FC_Licenses_Client' ) ) {
			$plugin_config = FC_Licenses_Client::get_plugin_config( $value['plugin_slug'] );
		}

		$license_key_hash = '';
		if ( ! empty( $plugin_config['license_key_hash_option'] ) ) {
			$license_key_hash = get_option( $plugin_config['license_key_hash_option'], '' );
		}

		// No license key saved: drop leftover license data so the field shows its empty state.
		if ( empty( $option_value ) && ! empty( $license_key_hash ) && method_exists( 'FC_Licenses_Client', 'clear_license_key_storage' ) ) {
			FC_Licenses_Client::clear_license_key_storage( $plugin_config );
			$license_key_hash = '';
		}

		// Prefer hash as status identity; fall back to raw option value (never hash masked display values).
		$status_cache_token = '';
		if ( ! empty( $license_key_hash ) ) {
			$status_cache_token = (string) $license_key_hash;
		} elseif ( ! empty( $option_value ) && class_exists( 'FC_Licenses_Client' ) && method_exists( 'FC_Licenses_Client', 'looks_like_masked_license_key' ) && ! FC_Licenses_Client::looks_like_masked_license_key( $option_value ) && ! FC_Licenses_Client::looks_like_license_key_hash( $option_value ) ) {
			$status_cache_token = (string) $option_value;
		}

		// Whether cached status still applies to the current field value.
		$status_matches = false;
		if ( is_array( $license_status ) && ! empty( $status_cache_token ) ) {
			$cached_token   = isset( $license_status['license_key'] ) ? (string) $license_status['license_key'] : '';
			$status_matches = ( $status_cache_token === $cached_token );

			if ( ! $status_matches && class_exists( 'FC_Licenses_Client' ) && method_exists( 'FC_Licenses_Client', 'hash_license_key' ) && ! FC_Licenses_Client::looks_like_license_key_hash( $status_cache_token ) ) {
				$status_matches = ( FC_Licenses_Client::hash_license_key( $status_cache_token ) === $cached_token );
			}

			// Do not keep a cached error when a hash is available — refetch so a successful
			// server activation is not stuck behind a prior "key not found" lookup.
			if ( $status_matches && ! empty( $license_key_hash ) && isset( $license_status['status'] ) && 'error' === $license_status['status'] ) {
				$status_matches = false;
			}

			if ( ! $status_matches ) {
				$license_status = false;
				delete_transient( $license_status_transient_id );
			}
		}

		// Maybe call license API and update license status transient.
		if (
			! empty( $status_cache_token )
			&& ! $status_matches
			&& ! empty( $value['plugin_slug'] )
			&& class_exists( 'FC_Licenses_Client' )
			&& method_exists( 'FC_Licenses_Client', 'get_license_key_details' )
			&& ! empty( $plugin_config )
		) {
			$config = $plugin_config;

			if ( ! empty( $option_value ) && method_exists( 'FC_Licenses_Client', 'looks_like_masked_license_key' ) && ! FC_Licenses_Client::looks_like_masked_license_key( $option_value ) ) {
				$config['license_key'] = $option_value;
			}

			$api_result = FC_Licenses_Client::get_license_key_details(
				$value['plugin_slug'],
				$config['plugin_file'],
				$config
			);

			// Stored license key is no longer on the license server: clear it so a new key can be entered.
			// Only a lookup made with the stored hash can invalidate it.
			if (
				method_exists( 'FC_Licenses_Client', 'is_license_not_found_response' )
				&& FC_Licenses_Client::is_license_not_found_response( $api_result )
				&& FC_Licenses_Client::is_stored_license_key_hash_in_use( $config )
			) {
				FC_Licenses_Client::clear_license_key_storage( $plugin_config );
				delete_transient( $license_status_transient_id );

				$option_value     = '';
				$has_saved_value  = false;
				$license_key_hash = '';
				$license_status   = array(
					'license_key' => '',
					'status' => 'empty',
				);
			}
			else {
				$license_status = $this->maybe_set_license_status_transient( $status_cache_token, $api_result, $license_status_transient_id );
			}
		}
		elseif ( empty( $option_value ) && empty( $license_key_hash ) ) {
			// Keep a status stored for the empty field, such as a rejected license key, so the message is not lost.
			$has_empty_field_status = is_array( $license_status ) && isset( $license_status[ 'license_key' ] ) && '' === (string) $license_status[ 'license_key' ];

			if ( ! $has_empty_field_status ) {
				$license_status = array(
					'license_key' => '',
					'status' => 'empty',
				);
				// Delete transient when license key is empty.
				delete_transient( $license_status_transient_id );
			}
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
					if ( ! empty( $license_status[ 'expiration' ] ) ) {
						$active_until_date = date( 'Y-m-d', $license_status[ 'expiration' ] - ( 60 * 60 * 24 ) ); // Expiration date - 1 day.
						// translators: %s: License key expiration date.
						$license_key_status_text = sprintf( __( 'Valid until %s.', 'fluid-checkout' ), $active_until_date );
					} else {
						$license_key_status_text = __( 'License key active.', 'fluid-checkout' );
					}
					$license_key_status_class = 'fc-license-key__status-label--active';
					$license_action_html = '';
					break;
				case 'expired':
					$license_key_expiration_date = ! empty( $license_status[ 'expiration' ] ) ? date( 'Y-m-d', $license_status[ 'expiration' ] ) : '';
					// translators: %s: License key expiration date.
					$license_key_status_text = $license_key_expiration_date
						? sprintf( __( 'Expired on %s.', 'fluid-checkout' ), $license_key_expiration_date )
						: __( 'License key expired.', 'fluid-checkout' );
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
				<span class="fc-license-key__field-wrap">
					<?php if ( $has_saved_value ) : ?>
						<input type="hidden" class="fc-license-key__preserved-value" name="<?php echo esc_attr( $value['field_name'] ); ?>" value="<?php echo esc_attr( $option_value ); ?>" />
					<?php endif; ?>
					<input
						name="<?php echo esc_attr( $value['field_name'] ); ?>"
						id="<?php echo esc_attr( $value['id'] ); ?>"
						type="text"
						style="<?php echo esc_attr( $value['css'] ); ?>"
						value="<?php echo esc_attr( $option_value ); ?>"
						class="<?php echo esc_attr( trim( ( isset( $value['class'] ) ? $value['class'] : '' ) . ' fc-license-key__input' ) ); ?>"
						placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
						<?php disabled( $has_saved_value ); ?>
						<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
						/>
					<?php if ( $has_saved_value ) : ?>
						<button type="button" class="button-link fc-license-key__clear"><?php echo esc_html( __( 'Clear', 'fluid-checkout' ) ); ?></button>
					<?php endif; ?>
				</span><?php echo esc_html( $value['suffix'] ); ?> <?php echo $description; // WPCS: XSS ok. ?>

					<p class="fc-license-key__status"><strong class="<?php echo esc_attr( $license_key_status_class ); ?>"><?php echo wp_kses_post( $license_key_status_text ); ?></strong> <?php echo wp_kses_post( $license_action_html ); ?></p>
			</td>
		</tr>
		<?php
	}



	/**
	 * Store license field status after an activation attempt on settings save.
	 *
	 * @param string $option_id         Setting field option ID.
	 * @param string $license_key       License key submitted for activation.
	 * @param mixed  $activation_result Result from FC_Licenses_Client::activate_license_key, or an error-like object.
	 */
	public function set_status_from_activation_result( $option_id, $license_key, $activation_result ) {
		$transient_id = $option_id . '_status';

		// Prefer hash as cache identity so it matches the field after raw keys are replaced with masked values.
		$cache_token = $license_key;
		if ( class_exists( 'FC_Licenses_Client' ) && method_exists( 'FC_Licenses_Client', 'hash_license_key' ) && ! FC_Licenses_Client::looks_like_masked_license_key( $license_key ) && ! FC_Licenses_Client::looks_like_license_key_hash( $license_key ) && ! empty( $license_key ) ) {
			$cache_token = FC_Licenses_Client::hash_license_key( $license_key );
		}

		// Successful activation — store field status from the activation response (shows "Valid until").
		if ( class_exists( 'FC_Licenses_Client' ) && FC_Licenses_Client::is_own_license_data_response( $activation_result ) ) {
			$this->maybe_set_license_status_transient( $cache_token, $activation_result, $transient_id );
			return;
		}

		$message = __( 'Error while activating the license key. Plugin updates might not be available until the license key is validated.', 'fluid-checkout' );

		if ( is_object( $activation_result ) && ! empty( $activation_result->message ) ) {
			$message = $activation_result->message;
		}

		$error_response          = new stdClass();
		$error_response->code    = is_object( $activation_result ) && ! empty( $activation_result->code ) ? $activation_result->code : 'fc_license_activation_error';
		$error_response->message = $message;

		$this->maybe_set_license_status_transient( $cache_token, $error_response, $transient_id );
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

		// Determine initial transient expiration. A status not tied to a stored license key,
		// such as a rejected key, is short-lived feedback for the settings page.
		$transient_expiration = '' === (string) $option_value ? 60 * 15 : 60 * 60 * 24; // 15 minutes or 24 hours.

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
