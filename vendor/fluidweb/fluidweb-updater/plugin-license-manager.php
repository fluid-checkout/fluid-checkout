<?php
/**
 * Plugin Updater.
 *
 * Allow activation and auto updates for plugins hosted with License Manager for WooCommerce.
 */
if ( ! class_exists( 'Fluidweb_PluginLicenseManager' ) ) {
	class Fluidweb_PluginLicenseManager {
		private $slug;
		private $plugin_data;
		private $api_update_called = false;

		private $plugin_file;
		private $product_id;
		private $api_url;
		private $customer_key;
		private $customer_secret;
		private $license_key;
		private $activate_option;

		/**
		 * Cron hook name for weekly site environment reports.
		 */
		const SITE_REPORT_CRON_HOOK = 'fc_site_report_weekly';

		/**
		 * Option key for the last successful site report fingerprint.
		 */
		const SITE_REPORT_FINGERPRINT_OPTION = 'fc_site_report_last_fingerprint';

		/**
		 * Option key for the last successful site report timestamp.
		 */
		const SITE_REPORT_LAST_SENT_OPTION = 'fc_site_report_last_sent';

		/**
		 * Transient key used while a site report request is in progress.
		 */
		const SITE_REPORT_SEND_LOCK_TRANSIENT = 'fc_site_report_send_lock';

		/**
		 * Minimum interval between sends when the environment fingerprint has changed.
		 */
		const SITE_REPORT_CHANGED_INTERVAL = WEEK_IN_SECONDS;

		/**
		 * Heartbeat interval between sends when the environment fingerprint is unchanged.
		 */
		const SITE_REPORT_UNCHANGED_INTERVAL = 4 * WEEK_IN_SECONDS;



		/**
		 * Construct a new instance of plugin updater
		 *
		 * @param		string		$plugin_file			Relative path to plugin file.
		 * @param		string		$product_id				ID of the product on the license manager website.
		 * @param		string		$customer_key			License Manager API consumer key.
		 * @param		string		$customer_secret		License Manager API consumer secret.
		 * @param		string		$activate_option		License Manager API consumer secret.
		 * @param		string		$license_key		License key.
		 */
		function __construct( $plugin_file, $product_id, $api_url, $customer_key, $customer_secret, $activate_option, $license_key ) {	
			// Set variables
			$this->plugin_file = $plugin_file;
			$this->product_id = $product_id;
			$this->api_url = $api_url;
			$this->customer_key = $customer_key;
			$this->customer_secret = $customer_secret;
			$this->activate_option = $activate_option;
			$this->license_key = $license_key;
		}



		/**
		 * Initialize hooks.
		 */
		public function init_plugin_update_hooks() {
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'set_transient' ) );
			add_filter( 'plugins_api', array( $this, 'set_plugin_info' ), 10, 3 );
			add_filter( 'upgrader_post_install', array( $this, 'post_install' ), 10, 3 );
		}



		/**
		 * Get information regarding the current plugin version
		 */
		private function init_plugin_data() {
			$this->slug = plugin_basename( $this->plugin_file );
			$this->plugin_data = get_plugin_data( $this->plugin_file );
		}



		/**
		 * Call API for data
		 */
		private function call_api( $url ) {
			$process = curl_init( $url );
			curl_setopt( $process, CURLOPT_USERPWD, sprintf( '%s:%s', $this->customer_key, $this->customer_secret ) );
			curl_setopt( $process, CURLOPT_RETURNTRANSFER, TRUE );
			$response = curl_exec( $process );
			curl_close( $process );
			return $response;
		}



		/**
		 * Get information regarding plugin releases from repository
		 */
		public function get_release_info( $force_check = false ) {	
			$transient_name = $this->slug . '_plugin_info';

			// Check transient but allow for $force_check to override
			if( ! $force_check ) {
				$transient = get_transient( $transient_name );
				if( $transient !== false ) {
					return $transient;
				}
			}

			$url = untrailingslashit( $this->api_url ) . '/wp-json/lmfwc/v2/products/update/' . $this->product_id;

			$response = $this->call_api( $url );
			$response = json_decode( $response );

			// Set flag for API called
			$this->api_update_called = true;

			// Update transient
			set_transient( $transient_name, $response, DAY_IN_SECONDS );

			return $response;
		}



		/**
		 * Get the plugin license information from the license manager server.
		 */
		public function get_info( $license_key = null ) {
			// Defaults to the instance license key.
			if ( ! $license_key ) {
				$license_key = $this->license_key;
			}
	
			$url = untrailingslashit( $this->api_url ) . '/wp-json/lmfwc/v2/licenses/' . $license_key;

			$response = $this->call_api( $url );

			if ( $response ) {
				$data = json_decode( $response );
				return $data;
			}

			$error_response = new \stdClass();
			$error_response->code = 'fwplm_rest_connection_error';
			$error_response->message = sprintf( 'Couldn\'t connect to the license server (%s). Try again later.', $this->api_url );
			return $error_response;
		}



		/**
		 * Validate the plugin license key against the license manager server.
		 */
		public function validate( $license_key = null ) {
			// Defaults to the instance license key.
			if ( ! $license_key ) {
				$license_key = $this->license_key;
			}
	
			$url = untrailingslashit( $this->api_url ) . '/wp-json/lmfwc/v2/licenses/validate/' . $license_key;

			$response = $this->call_api( $url );

			if ( $response ) {
				$data = json_decode( $response );
				return $data;
			}

			$error_response = new \stdClass();
			$error_response->code = 'fwplm_rest_connection_error'; 
			$error_response->message = sprintf( 'Couldn\'t connect to the license server (%s). Try again later.', $this->api_url );
			return $error_response;
		}



		/**
		 * Activate the plugin license, also validate against the license manager server.
		 */
		public function activate( $license_key = null ) {
			// Defaults to the instance license key.
			if ( ! $license_key ) {
				$license_key = $this->license_key;
			}

			if ( ! $license_key ) {
				$error_response = new \stdClass();
				$error_response->code = 'fwplm_missing_license_key'; 
				$error_response->message = 'Missing the license key. Please provide a valid license key and try again.';
				return $error_response;
			}
	
			$url = untrailingslashit( $this->api_url ) . '/wp-json/lmfwc/v2/licenses/activate/' . $license_key;
	
			$response = $this->call_api( $url );
	
			if ( $response ) {
				$data = json_decode( $response );

				if ( $data && isset( $data->success ) && $data->success ) {
					update_option( $this->activate_option, 'yes' );
					return $data;
				}
				else if ( $data && isset( $data->message ) && $data->message ) {
					$error_response = new \stdClass();
					$error_response->code = isset( $data->code ) ? $data->code : 'fwplm_generic_error';
					$error_response->message = $data->message;
					return $error_response;
				}
			}

			$error_response = new \stdClass();
			$error_response->code = 'fwplm_rest_connection_error'; 
			$error_response->message = sprintf( 'Couldn\'t connect to the license server (%s). Try again later.', $this->api_url );
			return $error_response;
		}



		/**
		 * Push in plugin version information to get the update notification
		 */
		public function set_transient( $transient ) {
			// Bail if no response (error)
			if( ! isset( $transient->response ) ) {
				return $transient;
			}

			// Get flag for `force-check` (force check only once)
			$force_check = ( ! $this->api_update_called ) ? ! empty( $_GET['force-check'] ) : false;
			
			// Get plugin & latest release information
			$this->init_plugin_data();
			$release_info = $this->get_release_info( $force_check );

			// Nothing found.
			if ( ! $release_info || ! property_exists( $release_info, 'success' ) || ! $release_info->success || ! isset( $release_info->data ) ) { return $transient; }

			// Check the versions if we need to do an update ( $repo_version > current version )
			$doUpdate = version_compare( $release_info->data->version, $this->plugin_data["Version"] );
	
			// Update the transient to include our updated plugin data
			if ( $doUpdate == 1 ) {
				$obj = new \stdClass();
				$obj->slug = $this->slug;
				$obj->new_version = $release_info->data->version;
				$obj->tested = $release_info->data->tested;
				$obj->url = $this->plugin_data["PluginURI"];
				$obj->package = str_replace( '{license_key}', $this->license_key, $release_info->data->package );

				// Copy icons from api_result
				if ( $release_info->data->icons ) {
					$obj->icons = array();
					foreach ( $release_info->data->icons as $key => $value ) {
						$obj->icons[ $key ] = $value;
					}
				}
				
				$transient->response[ $this->slug ] = $obj;
			}

			return $transient;
		}



		/**
		 * Push in plugin version information to display in the details lightbox
		 */
		public function set_plugin_info( $res, $action, $args ) {

			// Only for 'plugin_information' action
			if( 'plugin_information' !== $action ) { return $res; }

			// Get plugin data
			$this->init_plugin_data();

			// Only for 'plugin_information' action
			if( ! $this->plugin_data || ! is_array( $this->plugin_data ) ) { return $res; }

			// Get latest release information
			$release_info = $this->get_release_info();

			// Bail if new plugin info is not available
			if ( ! $release_info || ! property_exists( $release_info, 'success' ) || ! $release_info->success || ! isset( $release_info->data ) ) { return $res; }

			if ( $args->slug == $this->slug ) {
				$res = new \stdClass();

				$res->slug = $this->slug;
				$res->name = $this->plugin_data['Name'];
				$res->author = $this->plugin_data['Author'];
				$res->homepage = $this->plugin_data['PluginURI'];

				// Copy values from release info
				foreach ( $release_info->data as $key => $value ) {
					// Skip sections
					if ( 'sections' == $key ) { continue; }

					$res->$key = $value;
				}

				// Copy icons from release info
				if ( $release_info->data->icons ) {
					$res->icons = array();
					foreach ( $release_info->data->icons as $key => $value ) {
						$res->icons[ $key ] = $value;
					}
				}

				// Copy banners from release info
				if ( $release_info->data->banners ) {
					$res->banners = array();
					foreach ( $release_info->data->banners as $key => $value ) {
						$res->banners[ $key ] = $value;
					}
				}

				// Copy sections from release info
				if ( $release_info->data->sections ) {
					$res->sections = array();
					foreach ( $release_info->data->sections as $key => $value ) {
						$res->sections[ $key ] = $value;
					}
				}
			}

			return $res;
		}



		/**
		 * Perform additional actions to successfully install our plugin
		 */
		public function post_install( $true, $hook_extra, $result ) {
			// Get plugin information
			$this->init_plugin_data();

			// Remember if our plugin was previously activated
			$wasActivated = is_plugin_active( $this->slug );

			global $wp_filesystem;
			$pluginFolder = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . dirname( $this->slug );
			$wp_filesystem->move( $result['destination'], $pluginFolder );
			$result['destination'] = $pluginFolder;

			// Re-activate plugin if needed
			if ( $wasActivated ) { $activate = activate_plugin( $this->slug ); }

			return $result;
		}



		/**
		 * Schedule the weekly site report cron event.
		 */
		public static function schedule_site_report_cron() {
			// Bail if already scheduled
			if ( wp_next_scheduled( self::SITE_REPORT_CRON_HOOK ) ) { return; }

			wp_schedule_event( time() + WEEK_IN_SECONDS, 'weekly', self::SITE_REPORT_CRON_HOOK );
		}



		/**
		 * Maybe send a consolidated site environment report to Fluid Checkout.
		 */
		public static function maybe_send_site_report() {
			// Bail if a site report request is already in progress
			if ( get_transient( self::SITE_REPORT_SEND_LOCK_TRANSIENT ) ) { return; }

			set_transient( self::SITE_REPORT_SEND_LOCK_TRANSIENT, 1, MINUTE_IN_SECONDS );

			$payload     = self::build_site_report_payload();
			$fingerprint = self::get_site_report_fingerprint( $payload );

			// Bail if the site report should not be sent
			if ( ! self::should_send_site_report( $fingerprint ) ) {
				delete_transient( self::SITE_REPORT_SEND_LOCK_TRANSIENT );
				return;
			}

			$response = self::send_site_report( $payload );
			$code     = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );

			if ( 200 === $code ) {
				update_option( self::SITE_REPORT_FINGERPRINT_OPTION, $fingerprint );
				update_option( self::SITE_REPORT_LAST_SENT_OPTION, time() );
			}

			delete_transient( self::SITE_REPORT_SEND_LOCK_TRANSIENT );
		}



		/**
		 * Determine whether a site report should be sent for the current fingerprint.
		 *
		 * Sends on first run, after 7 days when changed, or after 4 weeks when unchanged.
		 *
		 * @param string $fingerprint Payload fingerprint hash.
		 */
		private static function should_send_site_report( $fingerprint ) {
			$last_fingerprint = get_option( self::SITE_REPORT_FINGERPRINT_OPTION, '' );
			$last_sent        = (int) get_option( self::SITE_REPORT_LAST_SENT_OPTION, 0 );

			// Bail if the site report has never been sent
			if ( empty( $last_sent ) ) { return true; }

			$elapsed = time() - $last_sent;

			// Bail if the site report should not be sent
			if ( $fingerprint !== $last_fingerprint ) { return $elapsed >= self::SITE_REPORT_CHANGED_INTERVAL; }

			return $elapsed >= self::SITE_REPORT_UNCHANGED_INTERVAL;
		}



		/**
		 * Build the site environment report payload.
		 */
		public static function build_site_report_payload() {
			// Bail if `get_plugins` function is not available
			if ( ! function_exists( 'get_plugins' ) ) { return array(); }

			$theme      = wp_get_theme();
			$site_url   = untrailingslashit( esc_url_raw( home_url() ) );
			$wc_version = defined( 'WC_VERSION' ) ? WC_VERSION : null;

			$plugins = array();

			foreach ( get_plugins() as $plugin_file => $plugin_data ) {
				$plugin_slug = self::get_plugin_slug_from_file( $plugin_file );
				$plugin_row  = array(
					'plugin_slug' => $plugin_slug,
					'plugin_file' => $plugin_file,
					'name'        => $plugin_data['Name'],
					'version'     => $plugin_data['Version'],
					'active'      => is_plugin_active( $plugin_file ),
				);

				self::maybe_add_license_status_plugin_row( $plugin_row, $plugin_slug );
				$plugins[] = $plugin_row;
			}

			return array(
				'schema_version'   => 1,
				'site_url'         => $site_url,
				'wp_version'       => get_bloginfo( 'version' ),
				'php_version'      => PHP_VERSION,
				'wc_version'       => $wc_version,
				'locale'           => get_locale(),
				'is_multisite'     => is_multisite(),
				'is_ssl'           => is_ssl(),
				'theme_template'   => $theme->get_template(),
				'theme_stylesheet' => $theme->get_stylesheet(),
				'theme_version'    => $theme->get( 'Version' ),
				'plugins'          => $plugins,
			);
		}



		/**
		 * Get a fingerprint hash for change detection.
		 *
		 * @param array $payload Site report payload.
		 */
		public static function get_site_report_fingerprint( $payload ) {
			$minimal = array(
				'site_url'         => $payload['site_url'],
				'wp_version'       => $payload['wp_version'],
				'php_version'      => $payload['php_version'],
				'wc_version'       => $payload['wc_version'],
				'locale'           => $payload['locale'],
				'is_multisite'     => $payload['is_multisite'],
				'is_ssl'           => $payload['is_ssl'],
				'theme_template'   => $payload['theme_template'],
				'theme_stylesheet' => $payload['theme_stylesheet'],
				'theme_version'    => $payload['theme_version'],
				'plugins'          => array(),
			);

			foreach ( $payload['plugins'] as $plugin ) {
				$plugin_minimal = array(
					'plugin_slug' => $plugin['plugin_slug'],
					'version'     => $plugin['version'],
					'active'      => $plugin['active'],
				);

				if ( array_key_exists( 'license_status', $plugin ) ) {
					$plugin_minimal['license_status'] = $plugin['license_status'];
				}

				if ( array_key_exists( 'license_key_hash', $plugin ) && ! empty( $plugin['license_key_hash'] ) ) {
					$plugin_minimal['license_key_hash'] = $plugin['license_key_hash'];
				}

				$minimal['plugins'][] = $plugin_minimal;
			}

			usort(
				$minimal['plugins'],
				function( $a, $b ) {
					return strcmp( $a['plugin_slug'], $b['plugin_slug'] );
				}
			);

			return hash( 'sha256', wp_json_encode( $minimal ) );
		}



		/**
		 * POST the site report payload to the Fluid Checkout licenses API.
		 *
		 * @param array $payload Site report payload.
		 */
		public static function send_site_report( $payload ) {
			$api_url = untrailingslashit(
				apply_filters( 'fc_site_report_api_url', 'https://fluidcheckout.com' )
			);

			return wp_remote_post(
				$api_url . '/wp-json/fc-licenses/v1/site-report',
				array(
					'headers' => array(
						'Content-Type' => 'application/json',
						'Referer'      => home_url(),
						'User-Agent'   => 'Fluid Checkout Site Report/' . self::get_site_report_user_agent_version(),
					),
					'body'    => wp_json_encode( $payload ),
					'timeout' => 15,
				)
			);
		}



		/**
		 * Get the Fluid Checkout plugin version for the site report User-Agent header.
		 */
		private static function get_site_report_user_agent_version() {
			// Bail if `get_plugins` function is not available
			if ( ! function_exists( 'get_plugins' ) ) { return 'unknown'; }

			$plugins = get_plugins();

			foreach ( $plugins as $plugin_file => $plugin_data ) {
				$plugin_slug = self::get_plugin_slug_from_file( $plugin_file );

				// Skip if not a premium Fluid Checkout plugin or the lite plugin
				$premium_fc_plugins = array_keys( self::get_premium_fc_license_option_map() );
				if ( 'fluid-checkout' !== $plugin_slug && ! in_array( $plugin_slug, $premium_fc_plugins, true ) ) { continue; }

				if ( ! is_plugin_active( $plugin_file ) ) { continue; }

				if ( ! empty( $plugin_data['Version'] ) ) {
					return $plugin_data['Version'];
				}
			}

			return 'unknown';
		}



		/**
		 * Get premium FC plugin license option map keyed by plugin folder slug.
		 */
		private static function get_premium_fc_license_option_map() {
			return array(
				'fluid-checkout-pro'             => array(
					'license_key_option'       => 'fc_pro_license_key',
					'license_activated_option' => 'fc_pro_license_key_activated',
				),
				'fc-address-book'                => array(
					'license_key_option'       => 'fc_adb_license_key',
					'license_activated_option' => 'fc_adb_license_key_activated',
				),
				'fc-vat-assistant'               => array(
					'license_key_option'       => 'fc_vat_license_key',
					'license_activated_option' => 'fc_vat_license_key_activated',
				),
				'fc-google-address-autocomplete' => array(
					'license_key_option'       => 'fc_gaa_license_key',
					'license_activated_option' => 'fc_gaa_license_key_activated',
				),
				'fc-paddle-payments'             => array(
					'license_key_option'       => 'fc_paddle_license_key',
					'license_activated_option' => 'fc_paddle_license_key_activated',
				),
				'fc-conversion-kit'              => array(
					'license_key_option'       => 'fc_kit_license_key',
					'license_activated_option' => 'fc_kit_license_key_activated',
				),
			);
		}



		/**
		 * Attach license fields to a premium FC plugin row when applicable.
		 *
		 * @param array  $plugin_row Plugin payload row.
		 * @param string $plugin_slug Plugin folder slug.
		 */
		private static function maybe_add_license_status_plugin_row( &$plugin_row, $plugin_slug ) {
			$license_map = self::get_premium_fc_license_option_map();

			if ( ! array_key_exists( $plugin_slug, $license_map ) ) {
				return;
			}

			$license_options = $license_map[ $plugin_slug ];
			$license_key     = get_option( $license_options['license_key_option'], '' );
			$license_status  = self::get_premium_fc_license_status(
				$license_key,
				get_option( $license_options['license_activated_option'], '' )
			);

			$plugin_row['license_status'] = $license_status;

			if ( ! empty( $license_key ) ) {
				$plugin_row['license_key_hash'] = hash( 'sha256', $license_key );
			}
		}



		/**
		 * Resolve license status for a premium FC plugin.
		 *
		 * @param string $license_key License key option value.
		 * @param string $activated_option_value Activation option value.
		 */
		private static function get_premium_fc_license_status( $license_key, $activated_option_value ) {
			if ( empty( $license_key ) ) {
				return 'missing';
			}

			if ( 'yes' === $activated_option_value ) {
				return 'valid';
			}

			if ( in_array( $activated_option_value, array( 'no', '', null ), true ) ) {
				return 'invalid';
			}

			return 'unknown';
		}



		/**
		 * Get plugin folder slug from a plugin file path.
		 *
		 * @param string $plugin_file Plugin file relative to plugins directory.
		 */
		private static function get_plugin_slug_from_file( $plugin_file ) {
			$plugin_file = str_replace( '\\', '/', $plugin_file );
			$parts       = explode( '/', $plugin_file );

			return sanitize_key( $parts[0] );
		}
	}
}
