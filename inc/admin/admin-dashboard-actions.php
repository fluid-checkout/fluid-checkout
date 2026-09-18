<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin dashboard actions for add-ons and site key (AJAX).
 */
class FluidCheckout_AdminDashboardActions extends FluidCheckout {

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
		add_action( 'wp_ajax_fc_validate_site_key', array( $this, 'ajax_validate_site_key' ), 10 );
		add_action( 'wp_ajax_fc_clear_site_key', array( $this, 'ajax_clear_site_key' ), 10 );
		add_action( 'wp_ajax_fc_activate_plugin', array( $this, 'ajax_activate_plugin' ), 10 );
		add_action( 'wp_ajax_fc_install_plugin', array( $this, 'ajax_install_plugin' ), 10 );
	}



	/**
	 * Whether the current user can run dashboard add-on actions.
	 */
	private function current_user_can_manage_addons() {
		return current_user_can( 'manage_woocommerce' ) || current_user_can( 'install_plugins' );
	}



	/**
	 * Verify an AJAX dashboard action request.
	 *
	 * @param string $nonce_action Nonce action name.
	 */
	private function verify_ajax_request( $nonce_action ) {
		if ( ! $this->current_user_can_manage_addons() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'fluid-checkout' ) ), 403 );
		}

		check_ajax_referer( $nonce_action, 'nonce' );
	}



	/**
	 * Resolve the plugin folder slug from a plugin basename.
	 *
	 * @param string $plugin_file Plugin basename.
	 */
	private function get_plugin_slug_from_file( $plugin_file ) {
		$plugin_file = (string) $plugin_file;
		$parts       = explode( '/', $plugin_file );

		return sanitize_key( $parts[0] ?? '' );
	}



	/**
	 * AJAX: validate a pasted site key and persist hash + last chunk on success.
	 */
	public function ajax_validate_site_key() {
		$this->verify_ajax_request( 'fc-validate-site-key' );

		// Bail if licenses client is not available
		if ( ! class_exists( 'FC_Licenses_Client' ) ) {
			wp_send_json_error( array( 'message' => __( 'Licenses client is not available.', 'fluid-checkout' ) ) );
		}

		$site_key = sanitize_text_field( wp_unslash( $_POST['fc_site_key'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$result   = FC_Licenses_Client::validate_site_key( $site_key, self::FC_LICENSES_API_URL, true, self::FC_LICENSES_WEBSITE_ACCOUNT_URL );

		if ( empty( $result['success'] ) || empty( $result['site_key_hash'] ) || empty( $result['site_key_last_chunk'] ) ) {
			$message = ! empty( $result['error'] ) ? $result['error'] : FC_Licenses_Client::get_site_key_invalid_message( self::FC_LICENSES_WEBSITE_ACCOUNT_URL );
			wp_send_json_error( array( 'message' => $message ) );
		}

		FC_Licenses_Client::save_site_key_storage( $result['site_key_hash'], $result['site_key_last_chunk'] );

		wp_send_json_success(
			array(
				'message'          => __( 'Site key validated successfully.', 'fluid-checkout' ),
				'site_key_display' => FC_Licenses_Client::get_site_key_display_value(),
				'has_site_key'     => true,
				'addons_html'      => $this->get_addons_list_html(),
			)
		);
	}



	/**
	 * AJAX: clear the stored site key.
	 */
	public function ajax_clear_site_key() {
		$this->verify_ajax_request( 'fc-clear-site-key' );

		// Bail if licenses client is not available
		if ( ! class_exists( 'FC_Licenses_Client' ) ) {
			wp_send_json_error( array( 'message' => __( 'Licenses client is not available.', 'fluid-checkout' ) ) );
		}

		FC_Licenses_Client::clear_site_key();

		wp_send_json_success(
			array(
				'message'          => __( 'Site key removed.', 'fluid-checkout' ),
				'site_key_display' => '',
				'has_site_key'     => false,
				'addons_html'      => $this->get_addons_list_html(),
			)
		);
	}



	/**
	 * Render the Dashboard add-ons list items HTML for AJAX responses.
	 *
	 * @return string
	 */
	private function get_addons_list_html() {
		// Bail if add-ons settings class is not available
		if ( ! class_exists( 'FluidCheckout_Admin_SettingType_Addons' ) ) {
			return '';
		}

		return FluidCheckout_Admin_SettingType_Addons::instance()->get_addons_list_html();
	}



	/**
	 * AJAX: activate a plugin and maybe call site-key activate-product.
	 */
	public function ajax_activate_plugin() {
		$this->verify_ajax_request( 'fc-activate-plugin' );

		if ( ! current_user_can( 'install_plugins' ) && ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to activate plugins.', 'fluid-checkout' ) ), 403 );
		}

		$plugin           = sanitize_text_field( wp_unslash( $_POST['plugin'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$license_key_hash = strtolower( sanitize_text_field( wp_unslash( $_POST['license_key_hash'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Bail if plugin file is missing
		if ( empty( $plugin ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing plugin.', 'fluid-checkout' ) ) );
		}

		$result = activate_plugin( $plugin );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$this->maybe_activate_product_with_site_key( $plugin, $license_key_hash );

		if ( class_exists( 'FC_Licenses_Client' ) ) {
			FC_Licenses_Client::clear_site_key_entitlements_cache();
		}

		wp_send_json_success(
			array(
				'message' => __( 'Plugin activated.', 'fluid-checkout' ),
				'reload'  => true,
			)
		);
	}



	/**
	 * AJAX: install a plugin from the site-key entitlements package URL.
	 */
	public function ajax_install_plugin() {
		$this->verify_ajax_request( 'fc-install-plugin' );

		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to install plugins.', 'fluid-checkout' ) ), 403 );
		}

		// Bail if licenses client is not available
		if ( ! class_exists( 'FC_Licenses_Client' ) ) {
			wp_send_json_error( array( 'message' => __( 'Licenses client is not available.', 'fluid-checkout' ) ) );
		}

		$plugin           = sanitize_text_field( wp_unslash( $_POST['plugin'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$license_key_hash = strtolower( sanitize_text_field( wp_unslash( $_POST['license_key_hash'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$plugin_slug      = $this->get_plugin_slug_from_file( $plugin );

		// Bail if plugin file is missing
		if ( empty( $plugin ) || empty( $plugin_slug ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing plugin.', 'fluid-checkout' ) ) );
		}

		$entitlement = FC_Licenses_Client::get_site_key_entitlement_for_plugin( $plugin_slug, self::FC_LICENSES_API_URL );

		// Bail if product is not entitled or package URL is missing
		if ( empty( $entitlement['package'] ) ) {
			wp_send_json_error( array( 'message' => __( 'This product is not available for install with the current site key.', 'fluid-checkout' ) ) );
		}

		$package_url = $this->resolve_package_url_for_license_key( $entitlement, $license_key_hash );

		// Bail if the selected license key is not valid for this entitlement
		if ( empty( $package_url ) ) {
			wp_send_json_error( array( 'message' => __( 'This product is not available for install with the current site key.', 'fluid-checkout' ) ) );
		}

		$install_result = FC_Licenses_Client::install_plugin_from_package_url( $package_url, $plugin_slug );

		if ( is_wp_error( $install_result ) ) {
			wp_send_json_error( array( 'message' => $install_result->get_error_message() ) );
		}

		FC_Licenses_Client::clear_site_key_entitlements_cache();

		wp_send_json_success(
			array(
				'message'        => __( 'Plugin installed.', 'fluid-checkout' ),
				'show_activate'  => true,
				'reload'         => false,
			)
		);
	}



	/**
	 * Resolve a package download URL for a specific entitlement license key hash.
	 *
	 * @param array  $entitlement      Entitlement row.
	 * @param string $license_key_hash Optional license key hash from the UI.
	 * @return string
	 */
	private function resolve_package_url_for_license_key( $entitlement, $license_key_hash = '' ) {
		$package          = isset( $entitlement['package'] ) ? (string) $entitlement['package'] : '';
		$license_key_hash = strtolower( trim( (string) $license_key_hash ) );
		$license_keys     = isset( $entitlement['license_keys'] ) && is_array( $entitlement['license_keys'] ) ? $entitlement['license_keys'] : array();

		// Prefer the first entitlement hash when the UI did not send one
		if ( '' === $license_key_hash && ! empty( $license_keys[0]['license_key_hash'] ) ) {
			$license_key_hash = strtolower( trim( (string) $license_keys[0]['license_key_hash'] ) );
		}

		if ( '' === $license_key_hash || ! FC_Licenses_Client::looks_like_license_key_hash( $license_key_hash ) ) {
			return $package;
		}

		// When entitlement lists keys, require the selected hash to be among them
		if ( ! empty( $license_keys ) ) {
			$found = false;

			foreach ( $license_keys as $license_key_row ) {
				$hash = isset( $license_key_row['license_key_hash'] ) ? strtolower( trim( (string) $license_key_row['license_key_hash'] ) ) : '';

				if ( $hash === $license_key_hash ) {
					$found = true;
					break;
				}
			}

			if ( ! $found ) {
				return '';
			}
		}

		if ( preg_match( '#/download/[a-f0-9]{64}#i', $package ) ) {
			return preg_replace( '#/download/[a-f0-9]{64}#i', '/download/' . $license_key_hash, $package, 1 );
		}

		return $package;
	}



	/**
	 * Call activate-product when a site key entitles the plugin.
	 *
	 * @param string $plugin_file      Plugin basename.
	 * @param string $license_key_hash Optional license key hash from the UI.
	 */
	private function maybe_activate_product_with_site_key( $plugin_file, $license_key_hash = '' ) {
		// Bail if licenses client is not available
		if ( ! class_exists( 'FC_Licenses_Client' ) ) { return; }

		// Bail if no site key is stored
		if ( ! FC_Licenses_Client::has_site_key() ) { return; }

		$plugin_slug = $this->get_plugin_slug_from_file( $plugin_file );

		// Bail if plugin slug is empty
		if ( empty( $plugin_slug ) ) { return; }

		// Bail if product is not in site-key entitlements
		if ( ! FC_Licenses_Client::is_plugin_entitled_with_site_key( $plugin_slug, self::FC_LICENSES_API_URL ) ) { return; }

		FC_Licenses_Client::activate_product_with_site_key( $plugin_slug, $license_key_hash, self::FC_LICENSES_API_URL, self::FC_LICENSES_WEBSITE_ACCOUNT_URL );
	}

}

FluidCheckout_AdminDashboardActions::instance();
