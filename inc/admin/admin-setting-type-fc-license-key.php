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

		// AJAX
		add_action( 'wp_ajax_fc_lcs_activate_license', array( $this, 'ajax_activate_license' ), 10 );
		add_action( 'wp_ajax_fc_lcs_refresh_license_statuses', array( $this, 'ajax_refresh_license_statuses' ), 10 );

		// Hide WooCommerce Save changes on the License keys section
		add_action( 'admin_head', array( $this, 'maybe_hide_save_button' ), 10 );
	}



	/**
	 * Whether the current request is the Fluid Checkout License keys settings section.
	 */
	private function is_license_keys_settings_section() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';

		return 'fc_checkout' === $tab && 'license_keys' === $section;
	}



	/**
	 * Hide the WooCommerce settings Save changes button on the License keys section.
	 */
	public function maybe_hide_save_button() {
		// Bail if not on License keys settings
		if ( ! $this->is_license_keys_settings_section() ) { return; }

		echo '<style type="text/css">.woocommerce p.submit{display:none!important;}</style>';
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
	 * Localize AJAX settings for the license key admin script.
	 */
	private function maybe_localize_script() {
		static $localized = false;

		// Bail if already localized
		if ( $localized ) { return; }

		$localized = true;

		wp_localize_script(
			'fc-admin-license-key',
			'fcAdminLicenseKeySettings',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'fc_lcs_license_key_admin' ),
				'i18n'    => array(
					'activate'        => __( 'Activate', 'fluid-checkout' ),
					'activating'      => __( 'Activating…', 'fluid-checkout' ),
					'genericError'    => __( 'Could not activate the license key. Try again.', 'fluid-checkout' ),
					'refreshError'    => __( 'Could not refresh license statuses. Try again.', 'fluid-checkout' ),
				),
			)
		);
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		// Enqueue assets
		wp_enqueue_script( 'fc-admin-license-key' );
		$this->maybe_localize_script();

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

		$plugin_slug = ! empty( $value['plugin_slug'] ) ? sanitize_key( (string) $value['plugin_slug'] ) : '';
		$option_id   = ! empty( $value['id'] ) ? (string) $value['id'] : '';

		$plugin_config = array();
		if ( ! empty( $plugin_slug ) && class_exists( 'FC_Licenses_Client' ) ) {
			$plugin_config = FC_Licenses_Client::get_plugin_config( $plugin_slug );
		}

		// Prefer stored masked option; never show plaintext from memory.
		$option_value = '';
		if ( ! empty( $plugin_config['license_key_option'] ) ) {
			$stored = get_option( $plugin_config['license_key_option'], '' );
			$option_value = is_string( $stored ) ? $stored : '';
		} elseif ( isset( $value['value'] ) ) {
			$option_value = (string) $value['value'];
		}

		$license_key_hash = '';
		if ( ! empty( $plugin_config ) && method_exists( 'FC_Licenses_Client', 'get_stored_license_key_hash' ) ) {
			$license_key_hash = FC_Licenses_Client::get_stored_license_key_hash( $plugin_config );
		}

		$license_status_transient_id = $option_id . '_status';
		$license_status              = get_transient( $license_status_transient_id );

		// Temporary errors (not found, connection, etc.) are not durable — drop them on page load.
		if ( is_array( $license_status ) && isset( $license_status['status'] ) && 'error' === $license_status['status'] ) {
			$license_status = false;
			delete_transient( $license_status_transient_id );
		}

		// Drop stale field status when it does not match the stored hash.
		if ( is_array( $license_status ) && ! empty( $license_key_hash ) ) {
			$cached_token = isset( $license_status['license_key'] ) ? (string) $license_status['license_key'] : '';

			if ( $cached_token !== $license_key_hash ) {
				$license_status = false;
				delete_transient( $license_status_transient_id );
			}
		}

		// Empty storage: no durable status to show.
		if ( empty( $option_value ) && empty( $license_key_hash ) ) {
			$license_status = array(
				'license_key' => '',
				'status'      => 'empty',
			);
			delete_transient( $license_status_transient_id );
		}

		$status_ui = $this->build_status_ui_from_transient( $license_status, $value );

		?><tr valign="top" class="fc-license-key__row" data-plugin-slug="<?php echo esc_attr( $plugin_slug ); ?>" data-option-id="<?php echo esc_attr( $option_id ); ?>">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
				<span class="fc-license-key__field-wrap">
					<input
						id="<?php echo esc_attr( $value['id'] ); ?>"
						type="text"
						style="<?php echo esc_attr( $value['css'] ); ?>"
						value="<?php echo esc_attr( $option_value ); ?>"
						class="<?php echo esc_attr( trim( ( isset( $value['class'] ) ? $value['class'] : '' ) . ' fc-license-key__input' ) ); ?>"
						placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
						autocomplete="off"
						data-plugin-slug="<?php echo esc_attr( $plugin_slug ); ?>"
						data-option-id="<?php echo esc_attr( $option_id ); ?>"
						data-product-url="<?php echo esc_url( isset( $value['product_url'] ) ? $value['product_url'] : '' ); ?>"
						<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
						/>
					<button type="button" class="button button-secondary fc-license-key__activate"><?php echo esc_html( __( 'Activate', 'fluid-checkout' ) ); ?></button>
				</span><?php echo esc_html( $value['suffix'] ); ?> <?php echo $description; // WPCS: XSS ok. ?>

					<p class="fc-license-key__status"><strong class="<?php echo esc_attr( $status_ui['status_class'] ); ?>"><?php echo wp_kses_post( $status_ui['status_text'] ); ?></strong> <?php echo wp_kses_post( $status_ui['action_html'] ); ?></p>
			</td>
		</tr>
		<?php
	}



	/**
	 * AJAX: activate or clear one license key field.
	 */
	public function ajax_activate_license() {
		$this->verify_ajax_request();

		$plugin_slug  = isset( $_POST['plugin_slug'] ) ? sanitize_key( wp_unslash( $_POST['plugin_slug'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$option_id    = isset( $_POST['option_id'] ) ? sanitize_text_field( wp_unslash( $_POST['option_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$license_key  = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$product_url  = isset( $_POST['product_url'] ) ? esc_url_raw( wp_unslash( $_POST['product_url'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $plugin_slug ) || empty( $option_id ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Missing license field data.', 'fluid-checkout' ),
				)
			);
		}

		if ( ! class_exists( 'FC_Licenses_Client' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'License manager class not available.', 'fluid-checkout' ),
				)
			);
		}

		$config = FC_Licenses_Client::get_plugin_config( $plugin_slug );

		if ( empty( $config ) || empty( $config['plugin_file'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'License manager class not available.', 'fluid-checkout' ),
				)
			);
		}

		$transient_id = $option_id . '_status';

		// Empty key: clear local storage only (no remote call).
		if ( '' === trim( $license_key ) ) {
			FC_Licenses_Client::clear_license_key_storage( $config );
			delete_transient( $transient_id );

			$empty_status = array(
				'license_key' => '',
				'status'      => 'empty',
			);

			wp_send_json_success(
				array(
					'option_id'    => $option_id,
					'plugin_slug'  => $plugin_slug,
					'masked_value' => '',
					'status'       => $this->build_status_ui_from_transient(
						$empty_status,
						array(
							'product_url' => $product_url,
						)
					),
				)
			);
		}

		// Masked value means the user did not enter a new raw key — reject activate.
		if ( method_exists( 'FC_Licenses_Client', 'looks_like_masked_license_key' ) && FC_Licenses_Client::looks_like_masked_license_key( $license_key ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Enter the full license key before activating.', 'fluid-checkout' ),
				)
			);
		}

		$stored_hash    = FC_Licenses_Client::get_stored_license_key_hash( $config );
		$submitted_hash = FC_Licenses_Client::hash_license_key( $license_key );

		// Different key: drop previous identity before activate.
		if ( ! empty( $stored_hash ) && $stored_hash !== $submitted_hash ) {
			delete_transient( $transient_id );
			FC_Licenses_Client::clear_stored_license_key_hash( $config );
		}

		$config['license_key'] = $license_key;
		$activation_result     = FC_Licenses_Client::activate_license_key( $plugin_slug, $config['plugin_file'], $config );

		if ( method_exists( 'FC_Licenses_Client', 'is_license_not_found_response' ) && FC_Licenses_Client::is_license_not_found_response( $activation_result ) ) {
			FC_Licenses_Client::clear_license_key_storage( $config );
			$license_status = $this->set_status_from_activation_result( $option_id, '', $activation_result );

			wp_send_json_success(
				array(
					'option_id'    => $option_id,
					'plugin_slug'  => $plugin_slug,
					'masked_value' => '',
					'status'       => $this->build_status_ui_from_transient(
						$license_status,
						array(
							'product_url' => $product_url,
						)
					),
				)
			);
		}

		$license_status = $this->set_status_from_activation_result( $option_id, $license_key, $activation_result );

		$masked_value = '';
		if ( ! empty( $config['license_key_option'] ) ) {
			$masked = get_option( $config['license_key_option'], '' );
			$masked_value = is_string( $masked ) ? $masked : '';
		}

		wp_send_json_success(
			array(
				'option_id'    => $option_id,
				'plugin_slug'  => $plugin_slug,
				'masked_value' => $masked_value,
				'status'       => $this->build_status_ui_from_transient(
					$license_status,
					array(
						'product_url' => $product_url,
					)
				),
			)
		);
	}



	/**
	 * AJAX: refresh license statuses for stored hashes with missing/stale detailed cache.
	 */
	public function ajax_refresh_license_statuses() {
		$this->verify_ajax_request();

		$force = ! empty( $_POST['force'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( ! class_exists( 'FC_Licenses_Client' ) || ! method_exists( 'FC_Licenses_Client', 'get_license_keys_details' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'License manager class not available.', 'fluid-checkout' ),
				)
			);
		}

		$statuses     = array();
		$hash_to_meta = array();
		$hashes       = array();
		$api_url      = '';

		$plugin_slugs = method_exists( 'FC_Licenses_Client', 'get_registered_plugin_slugs' )
			? FC_Licenses_Client::get_registered_plugin_slugs()
			: array();

		foreach ( $plugin_slugs as $plugin_slug ) {
			$config = FC_Licenses_Client::get_plugin_config( $plugin_slug );

			if ( empty( $config['license_key_option'] ) || empty( $config['plugin_file'] ) ) {
				continue;
			}

			$option_id = (string) $config['license_key_option'];
			$hash      = FC_Licenses_Client::get_stored_license_key_hash( $config );

			if ( empty( $hash ) ) {
				continue;
			}

			$product_url = '';
			$hash_to_meta[ $hash ] = array(
				'plugin_slug' => $plugin_slug,
				'option_id'   => $option_id,
				'config'      => $config,
				'product_url' => $product_url,
			);

			$cached_details = FC_Licenses_Client::get_license_status_details_cache( $hash );
			$field_status   = get_transient( $option_id . '_status' );

			if ( ! $force && false !== $cached_details && is_array( $field_status ) ) {
				$statuses[ $option_id ] = $this->build_status_ui_from_transient(
					$field_status,
					array(
						'product_url' => $product_url,
					)
				);
				continue;
			}

			$hashes[] = $hash;

			if ( empty( $api_url ) && ! empty( $config['api_url'] ) ) {
				$api_url = $config['api_url'];
			}
		}

		if ( ! empty( $hashes ) ) {
			$bulk = FC_Licenses_Client::get_license_keys_details( $hashes, $api_url );

			if ( is_object( $bulk ) && isset( $bulk->data ) ) {
				foreach ( $hash_to_meta as $hash => $meta ) {
					if ( ! in_array( $hash, $hashes, true ) ) {
						continue;
					}

					$entry = null;

					if ( is_object( $bulk->data ) && isset( $bulk->data->{$hash} ) ) {
						$entry = $bulk->data->{$hash};
					} elseif ( is_array( $bulk->data ) && isset( $bulk->data[ $hash ] ) ) {
						$entry = $bulk->data[ $hash ];
					}

					if ( null === $entry ) {
						continue;
					}

					$response = FC_Licenses_Client::wrap_license_map_entry_as_response( $entry );

					if ( FC_Licenses_Client::is_own_license_data_response( $response ) ) {
						FC_Licenses_Client::set_license_status_details_cache( $hash, $entry );
						FC_Licenses_Client::mark_license_activated( $meta['config'], $hash );
					} elseif ( FC_Licenses_Client::is_license_not_found_response( $response ) ) {
						FC_Licenses_Client::clear_license_key_storage( $meta['config'] );
						delete_transient( $meta['option_id'] . '_status' );

						$statuses[ $meta['option_id'] ] = $this->build_status_ui_from_transient(
							array(
								'license_key' => '',
								'status'      => 'empty',
							),
							array(
								'product_url' => $meta['product_url'],
							)
						);
						continue;
					} else {
						FC_Licenses_Client::mark_license_deactivated( $meta['config'], $hash );
					}

					$license_status = $this->set_status_from_activation_result( $meta['option_id'], $hash, $response );

					$statuses[ $meta['option_id'] ] = $this->build_status_ui_from_transient(
						$license_status,
						array(
							'product_url' => $meta['product_url'],
						)
					);
				}
			}
		}

		wp_send_json_success(
			array(
				'statuses' => $statuses,
			)
		);
	}



	/**
	 * Verify AJAX capability and nonce.
	 */
	private function verify_ajax_request() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You are not allowed to manage license keys.', 'fluid-checkout' ),
				),
				403
			);
		}

		check_ajax_referer( 'fc_lcs_license_key_admin', 'nonce' );
	}



	/**
	 * Store license field status after an activation attempt.
	 *
	 * Successful statuses are cached for one day. Temporary errors are returned for
	 * immediate UI feedback only and are not persisted across page loads.
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
			return $this->maybe_set_license_status_transient( $cache_token, $activation_result, $transient_id );
		}

		$message = __( 'Error while activating the license key. Plugin updates might not be available until the license key is validated.', 'fluid-checkout' );

		if ( is_object( $activation_result ) && ! empty( $activation_result->message ) ) {
			$message = $activation_result->message;
		}

		$error_response          = new stdClass();
		$error_response->code    = is_object( $activation_result ) && ! empty( $activation_result->code ) ? $activation_result->code : 'fc_license_activation_error';
		$error_response->message = $message;

		return $this->maybe_set_license_status_transient( $cache_token, $error_response, $transient_id );
	}



	/**
	 * Build status label/action HTML fragments from a field status transient.
	 *
	 * @param mixed $license_status Transient value.
	 * @param array $value          Field args (needs product_url).
	 */
	public function build_status_ui_from_transient( $license_status, $value = array() ) {
		$product_url = ! empty( $value['product_url'] ) ? $value['product_url'] : '';

		$license_key_status       = 'empty';
		$license_key_status_class = 'fc-license-key__status-label--empty';
		$license_key_status_text  = '';
		// translators: %s: Product URL.
		$license_action_html = sprintf( __( '<a href="https://fluidcheckout.com/account/" target="_blank">Log in to your account</a> to manage and get your license key, or <a href="%s" target="_blank">purchase a new license key</a>.', 'fluid-checkout' ), esc_url( $product_url ) );

		if ( is_array( $license_status ) && isset( $license_status['status'] ) ) {
			$license_key_status = $license_status['status'];

			switch ( $license_status['status'] ) {
				case 'active':
					if ( ! empty( $license_status['expiration'] ) ) {
						$active_until_date = date( 'Y-m-d', $license_status['expiration'] - ( 60 * 60 * 24 ) ); // Expiration date - 1 day.
						// translators: %s: License key expiration date.
						$license_key_status_text = sprintf( __( 'Valid until %s.', 'fluid-checkout' ), $active_until_date );
					} else {
						$license_key_status_text = __( 'License key active.', 'fluid-checkout' );
					}
					$license_key_status_class = 'fc-license-key__status-label--active';
					$license_action_html      = '';
					break;
				case 'expired':
					$license_key_expiration_date = ! empty( $license_status['expiration'] ) ? date( 'Y-m-d', $license_status['expiration'] ) : '';
					// translators: %s: License key expiration date.
					$license_key_status_text = $license_key_expiration_date
						? sprintf( __( 'Expired on %s.', 'fluid-checkout' ), $license_key_expiration_date )
						: __( 'License key expired.', 'fluid-checkout' );
					$license_key_status_class = 'fc-license-key__status-label--expired';
					$license_action_html      = __( '<a href="https://fluidcheckout.com/account/" target="_blank">Log in to your account</a> to renew your license key and continue to receive updates and support.', 'fluid-checkout' );
					break;
				case 'cancelled':
					$license_key_status_text  = __( 'License key cancelled.', 'fluid-checkout' );
					$license_key_status_class = 'fc-license-key__status-label--cancelled';
					// translators: %s: Product URL.
					$license_action_html = sprintf( __( '<a href="%s" target="_blank">Purchase a new license key</a> and continue to receive updates and support.', 'fluid-checkout' ), esc_url( $product_url ) );
					break;
				case 'error':
					$license_key_status_text  = __( 'Error: ', 'fluid-checkout' ) . esc_html( isset( $license_status['data'] ) ? (string) $license_status['data'] : '' ) . '<br/>';
					$license_key_status_class = 'fc-license-key__status-label--error';
					break;
				case 'empty':
					$license_key_status_text  = '';
					$license_key_status_class = 'fc-license-key__status-label--empty';
					break;
			}
		}

		return array(
			'state'        => $license_key_status,
			'status_class' => $license_key_status_class,
			'status_text'  => $license_key_status_text,
			'action_html'  => $license_action_html,
		);
	}



	/**
	 * Maybe set license status transient from API results.
	 *
	 * Durable statuses (active / expired / cancelled) are cached for one day.
	 * Temporary errors are returned for the current request only and not stored.
	 *
	 * @param   string  $option_value                 License key option value.
	 * @param   object  $api_result                   License API result.
	 * @param   string  $license_status_transient_id  License status transient ID.
	 */
	private function maybe_set_license_status_transient( $option_value, $api_result, $license_status_transient_id ) {
		$license_key_status               = 'error';
		$license_key_expiration_timestamp = null;

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

			set_transient( $license_status_transient_id, $license_status, DAY_IN_SECONDS );

			return $license_status;
		}

		// Temporary errors: show once, do not cache across page loads.
		delete_transient( $license_status_transient_id );

		return array(
			'license_key' => $option_value,
			'status'      => 'error',
			'data'        => is_object( $api_result ) && isset( $api_result->message ) ? $api_result->message : '',
		);
	}

}

FluidCheckout_Admin_SettingType_LicenseKey::instance();
