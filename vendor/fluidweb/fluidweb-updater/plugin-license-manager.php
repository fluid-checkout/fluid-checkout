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
		 * Option key for site report opt-in.
		 */
		const SITE_REPORT_ENABLE_OPTION = 'fc_enable_site_report';

		/**
		 * Option key for selected site report data groups.
		 */
		const SITE_REPORT_DATA_GROUPS_OPTION = 'fc_site_report_data_groups';

		/**
		 * Option key for the site report API base URL.
		 */
		const SITE_REPORT_API_URL_OPTION = 'fc_site_report_api_url';

		/**
		 * Option key indicating sales metrics history backfill was sent.
		 */
		const SITE_REPORT_SALES_BACKFILL_SENT_OPTION = 'fc_site_report_sales_backfill_sent';

		/**
		 * Option key for the last closed sales month included in a successful site report.
		 */
		const SITE_REPORT_LAST_SALES_METRICS_MONTH_OPTION = 'fc_site_report_last_sales_metrics_month';

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
			// Bail if site reporting is disabled
			if ( ! self::is_site_report_enabled() ) { return; }

			self::send_site_report_now( null, false, false );
		}



		/**
		 * Send a site environment report immediately.
		 *
		 * @param array|null $groups             Optional data groups to include.
		 * @param bool       $enable_if_disabled Whether to enable reporting before sending.
		 * @param bool       $respect_send_rules Whether to apply fingerprint send intervals.
		 */
		public static function send_site_report_now( $groups = null, $enable_if_disabled = false, $respect_send_rules = true ) {
			if ( get_transient( self::SITE_REPORT_SEND_LOCK_TRANSIENT ) ) {
				return array(
					'success'    => false,
					'error_code' => 'in_progress',
				);
			}

			if ( $enable_if_disabled && ! self::is_site_report_enabled() ) {
				update_option( self::SITE_REPORT_ENABLE_OPTION, 'yes' );
				self::schedule_site_report_cron();
			}

			if ( null !== $groups ) {
				$groups = self::normalize_site_report_data_groups( $groups );
				update_option( self::SITE_REPORT_DATA_GROUPS_OPTION, $groups );
			}

			if ( ! self::is_site_report_enabled() ) {
				return array(
					'success'    => false,
					'error_code' => 'disabled',
				);
			}

			set_transient( self::SITE_REPORT_SEND_LOCK_TRANSIENT, 1, MINUTE_IN_SECONDS );

			$payload = self::build_site_report_payload( $groups );

			if ( empty( $payload ) || empty( $payload['site_url'] ) ) {
				delete_transient( self::SITE_REPORT_SEND_LOCK_TRANSIENT );

				return array(
					'success'    => false,
					'error_code' => 'empty_payload',
				);
			}

			$fingerprint = self::get_site_report_fingerprint( $payload );

			if ( $respect_send_rules && ! self::should_send_site_report( $fingerprint ) ) {
				delete_transient( self::SITE_REPORT_SEND_LOCK_TRANSIENT );

				return array(
					'success'    => false,
					'error_code' => 'rate_limited',
				);
			}

			$response      = self::send_site_report( $payload );
			$response_code = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );

			if ( 200 === $response_code ) {
				update_option( self::SITE_REPORT_FINGERPRINT_OPTION, $fingerprint );
				update_option( self::SITE_REPORT_LAST_SENT_OPTION, time() );

				if ( in_array( 'woocommerce_sales_metrics', $payload['report_groups'] ?? array(), true ) ) {
					if ( ! self::has_site_report_sales_backfill_sent() ) {
						update_option( self::SITE_REPORT_SALES_BACKFILL_SENT_OPTION, 'yes' );
					}

					self::update_site_report_last_sales_metrics_month( $payload );
				}
			}

			delete_transient( self::SITE_REPORT_SEND_LOCK_TRANSIENT );

			if ( 200 !== $response_code ) {
				$error_code = 429 === $response_code ? 'rate_limited' : 'request_failed';

				return array(
					'success'       => false,
					'error_code'    => $error_code,
					'response_code' => $response_code,
				);
			}

			return array(
				'success'       => true,
				'response_code' => $response_code,
				'is_enabled'    => self::is_site_report_enabled(),
			);
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
		 * Whether site environment reporting is enabled for this site.
		 */
		public static function is_site_report_enabled() {
			return 'yes' === get_option( self::SITE_REPORT_ENABLE_OPTION, 'no' );
		}



		/**
		 * Get normalized site report data groups selected by the merchant.
		 */
		public static function get_site_report_data_groups() {
			$groups = get_option( self::SITE_REPORT_DATA_GROUPS_OPTION, array( 'basic_environment' ) );

			return self::normalize_site_report_data_groups( $groups );
		}



		/**
		 * Normalize selected site report data groups.
		 *
		 * @param mixed $groups Raw or sanitized group values.
		 */
		public static function normalize_site_report_data_groups( $groups ) {
			$allowed = array( 'basic_environment', 'woocommerce_sales_metrics', 'plugin_settings' );

			if ( ! is_array( $groups ) ) {
				$groups = array();
			}

			$groups = array_values(
				array_intersect(
					array_map( 'sanitize_key', $groups ),
					$allowed
				)
			);

			if ( empty( $groups ) ) {
				return array( 'basic_environment' );
			}

			$dependent_groups = array( 'woocommerce_sales_metrics', 'plugin_settings' );

			if ( array_intersect( $groups, $dependent_groups ) && ! in_array( 'basic_environment', $groups, true ) ) {
				$groups[] = 'basic_environment';
			}

			return array_values( array_unique( $groups ) );
		}



		/**
		 * Build the site environment report payload.
		 *
		 * @param array|null $groups Optional data groups to include. When omitted, uses saved settings and requires opt-in.
		 */
		public static function build_site_report_payload( $groups = null ) {
			if ( null === $groups ) {
				// Bail if site reporting is disabled
				if ( ! self::is_site_report_enabled() ) { return array(); }

				$groups = self::get_site_report_data_groups();
			}
			else {
				$groups = self::normalize_site_report_data_groups( $groups );
			}

			// Bail if no data groups are selected
			if ( empty( $groups ) ) { return array(); }

			$site_url = untrailingslashit( esc_url_raw( home_url() ) );
			$payload  = array(
				'schema_version' => 2,
				'site_url'       => $site_url,
				'report_groups'  => $groups,
			);

			if ( in_array( 'basic_environment', $groups, true ) ) {
				$payload = array_merge( $payload, self::build_basic_environment_payload() );
				$payload['plugin_activations'] = self::build_plugin_activations();
			}

			if ( in_array( 'woocommerce_sales_metrics', $groups, true ) ) {
				$sales_metrics = self::build_sales_metrics();

				if ( ! empty( $sales_metrics ) ) {
					$payload['sales_metrics'] = $sales_metrics;
				}

				if ( ! self::has_site_report_sales_backfill_sent() ) {
					$sales_metrics_history = self::build_sales_metrics_history();

					if ( ! empty( $sales_metrics_history ) ) {
						$payload['sales_metrics_history'] = $sales_metrics_history;
					}
				}
				else {
					$sales_metrics_catchup = self::build_sales_metrics_catchup();

					if ( ! empty( $sales_metrics_catchup ) ) {
						$payload['sales_metrics_history'] = $sales_metrics_catchup;
					}
				}
			}

			return $payload;
		}



		/**
		 * Build the basic environment section of the site report payload.
		 */
		private static function build_basic_environment_payload() {
			$theme      = wp_get_theme();
			$wc_version = defined( 'WC_VERSION' ) ? WC_VERSION : null;
			$plugins    = array();

			if ( function_exists( 'get_plugins' ) ) {
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
			}

			return array(
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
		 * Whether sales metrics history backfill has already been sent.
		 */
		private static function has_site_report_sales_backfill_sent() {
			return 'yes' === get_option( self::SITE_REPORT_SALES_BACKFILL_SENT_OPTION, 'no' );
		}



		/**
		 * Get the site timezone used for monthly sales metrics.
		 */
		private static function get_site_report_timezone() {
			return wp_timezone();
		}



		/**
		 * Get the last closed calendar month key (YYYY-MM) in the site timezone.
		 */
		private static function get_last_closed_calendar_month_key() {
			$date = new DateTimeImmutable( 'first day of last month', self::get_site_report_timezone() );

			return $date->format( 'Y-m' );
		}



		/**
		 * Get the start of a calendar month in the site timezone.
		 *
		 * @param string $month_key Month key in YYYY-MM format.
		 */
		private static function get_month_start_datetime( $month_key ) {
			$parts = explode( '-', $month_key );

			return new DateTimeImmutable(
				sprintf( '%04d-%02d-01 00:00:00', (int) $parts[0], (int) $parts[1] ),
				self::get_site_report_timezone()
			);
		}



		/**
		 * Get the end of a calendar month in the site timezone.
		 *
		 * @param string $month_key Month key in YYYY-MM format.
		 */
		private static function get_month_end_datetime( $month_key ) {
			return self::get_month_start_datetime( $month_key )->modify( 'last day of this month 23:59:59' );
		}



		/**
		 * Add calendar months to a month key.
		 *
		 * @param string $month_key Month key in YYYY-MM format.
		 * @param int    $months    Number of months to add (negative to subtract).
		 */
		private static function add_calendar_months_to_key( $month_key, $months ) {
			$modifier = $months >= 0 ? '+' . $months . ' months' : $months . ' months';

			return self::get_month_start_datetime( $month_key )->modify( $modifier )->format( 'Y-m' );
		}



		/**
		 * List month keys from start through end, inclusive.
		 *
		 * @param string $start_month_key Start month key in YYYY-MM format.
		 * @param string $end_month_key   End month key in YYYY-MM format.
		 */
		private static function list_month_keys_inclusive( $start_month_key, $end_month_key ) {
			$months  = array();
			$current = $start_month_key;

			while ( strcmp( $current, $end_month_key ) <= 0 ) {
				$months[] = $current;
				$current  = self::add_calendar_months_to_key( $current, 1 );

				// Bail if the month range is unexpectedly large
				if ( count( $months ) > 240 ) { break; }
			}

			return $months;
		}



		/**
		 * Aggregate WooCommerce sales metrics for one or more closed calendar months.
		 *
		 * @param array $month_keys Month keys in YYYY-MM format.
		 */
		private static function aggregate_sales_metrics_for_months( $month_keys ) {
			if ( empty( $month_keys ) ) {
				return array();
			}

			if ( ! function_exists( 'wc_get_orders' ) || ! function_exists( 'get_woocommerce_currency' ) ) {
				return array();
			}

			$month_keys = array_values( array_unique( $month_keys ) );
			sort( $month_keys );

			$start      = self::get_month_start_datetime( $month_keys[0] );
			$end        = self::get_month_end_datetime( $month_keys[ count( $month_keys ) - 1 ] );
			$timezone   = self::get_site_report_timezone();
			$aggregates = array();

			foreach ( $month_keys as $month_key ) {
				$aggregates[ $month_key ] = array(
					'orders_count' => 0,
					'gross_sales'  => 0.0,
				);
			}

			$orders = wc_get_orders(
				array(
					'limit'        => -1,
					'return'       => 'objects',
					'status'       => array( 'wc-completed', 'wc-processing' ),
					'date_created' => $start->format( 'Y-m-d H:i:s' ) . '...' . $end->format( 'Y-m-d H:i:s' ),
				)
			);

			if ( ! is_array( $orders ) ) {
				$orders = array();
			}

			foreach ( $orders as $order ) {
				if ( ! $order ) { continue; }

				$created = $order->get_date_created();

				if ( ! $created ) { continue; }

				$created->setTimezone( $timezone );
				$month_key = $created->format( 'Y-m' );

				if ( ! isset( $aggregates[ $month_key ] ) ) { continue; }

				$aggregates[ $month_key ]['orders_count']++;
				$aggregates[ $month_key ]['gross_sales'] += (float) $order->get_total();
			}

			$currency = get_woocommerce_currency();
			$results  = array();

			foreach ( $month_keys as $month_key ) {
				$results[] = array(
					'month'        => $month_key,
					'orders_count' => $aggregates[ $month_key ]['orders_count'],
					'gross_sales'  => wc_format_decimal( $aggregates[ $month_key ]['gross_sales'], 2 ),
					'currency'     => $currency,
				);
			}

			return $results;
		}



		/**
		 * Build WooCommerce sales metrics for the last closed calendar month.
		 */
		private static function build_sales_metrics() {
			$results = self::aggregate_sales_metrics_for_months(
				array( self::get_last_closed_calendar_month_key() )
			);

			return ! empty( $results ) ? $results[0] : null;
		}



		/**
		 * Build closed-month sales metrics catch-up entries since the last successful report.
		 */
		private static function build_sales_metrics_catchup() {
			if ( ! function_exists( 'wc_get_orders' ) ) {
				return array();
			}

			$last_sent_month = get_option( self::SITE_REPORT_LAST_SALES_METRICS_MONTH_OPTION, '' );

			if ( empty( $last_sent_month ) ) {
				return array();
			}

			$last_closed_month = self::get_last_closed_calendar_month_key();

			if ( strcmp( $last_sent_month, $last_closed_month ) >= 0 ) {
				return array();
			}

			$start_month = self::add_calendar_months_to_key( $last_sent_month, 1 );

			if ( strcmp( $start_month, $last_closed_month ) > 0 ) {
				return array();
			}

			return self::aggregate_sales_metrics_for_months(
				self::list_month_keys_inclusive( $start_month, $last_closed_month )
			);
		}



		/**
		 * Remember the last closed sales month sent in a successful site report.
		 *
		 * @param array $payload Site report payload.
		 */
		private static function update_site_report_last_sales_metrics_month( $payload ) {
			if ( empty( $payload['sales_metrics']['month'] ) ) {
				return;
			}

			update_option(
				self::SITE_REPORT_LAST_SALES_METRICS_MONTH_OPTION,
				sanitize_text_field( (string) $payload['sales_metrics']['month'] )
			);
		}



		/**
		 * Build monthly sales metrics history for the first site report.
		 */
		private static function build_sales_metrics_history() {
			if ( ! function_exists( 'wc_get_orders' ) ) {
				return array();
			}

			$lite_activation = (int) get_option( 'fc_plugin_activation_time', 0 );

			if ( $lite_activation <= 0 ) {
				return array();
			}

			$timezone          = self::get_site_report_timezone();
			$install_month     = ( new DateTimeImmutable( '@' . $lite_activation ) )->setTimezone( $timezone )->format( 'Y-m' );
			$last_closed_month = self::get_last_closed_calendar_month_key();
			$pre_install_end   = self::add_calendar_months_to_key( $install_month, -1 );
			$pre_install_start = self::add_calendar_months_to_key( $pre_install_end, -11 );
			$month_keys        = self::list_month_keys_inclusive( $pre_install_start, $pre_install_end );
			$post_install      = self::list_month_keys_inclusive( $install_month, $last_closed_month );

			$month_keys = array_values( array_unique( array_merge( $month_keys, $post_install ) ) );
			sort( $month_keys );

			return self::aggregate_sales_metrics_for_months( $month_keys );
		}



		/**
		 * Build first-activation timestamps for Fluid Checkout catalog plugins.
		 */
		private static function build_plugin_activations() {
			$option_map = array(
				'fluid-checkout'                 => 'fc_plugin_activation_time',
				'fluid-checkout-pro'             => 'fc_pro_plugin_activation_time',
				'fc-address-book'                => 'fc_adb_plugin_activation_time',
				'fc-google-address-autocomplete' => 'fc_gaa_plugin_activation_time',
				'fc-paddle-payments'             => 'fc_paddle_plugin_activation_time',
				'fc-vat-assistant'               => 'fc_vat_plugin_activation_time',
				'fc-conversion-kit'              => 'fc_kit_plugin_activation_time',
			);

			$activations = array();

			foreach ( $option_map as $plugin_slug => $option_name ) {
				$timestamp = get_option( $option_name, null );

				if ( null === $timestamp || '' === $timestamp ) {
					$activations[ $plugin_slug ] = null;
					continue;
				}

				$activations[ $plugin_slug ] = absint( $timestamp );
			}

			return $activations;
		}



		/**
		 * Get a fingerprint hash for change detection.
		 *
		 * @param array $payload Site report payload.
		 */
		public static function get_site_report_fingerprint( $payload ) {
			$minimal = array(
				'site_url'      => $payload['site_url'] ?? '',
				'report_groups' => $payload['report_groups'] ?? array(),
			);

			if ( ! empty( $payload['report_groups'] ) && in_array( 'basic_environment', $payload['report_groups'], true ) ) {
				$minimal['wp_version']       = $payload['wp_version'] ?? '';
				$minimal['php_version']      = $payload['php_version'] ?? '';
				$minimal['wc_version']       = $payload['wc_version'] ?? null;
				$minimal['locale']           = $payload['locale'] ?? '';
				$minimal['is_multisite']     = $payload['is_multisite'] ?? false;
				$minimal['is_ssl']           = $payload['is_ssl'] ?? false;
				$minimal['theme_template']   = $payload['theme_template'] ?? '';
				$minimal['theme_stylesheet'] = $payload['theme_stylesheet'] ?? '';
				$minimal['theme_version']    = $payload['theme_version'] ?? '';
				$minimal['plugins']          = array();

				if ( ! empty( $payload['plugins'] ) && is_array( $payload['plugins'] ) ) {
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
				}

				if ( ! empty( $payload['plugin_activations'] ) && is_array( $payload['plugin_activations'] ) ) {
					$minimal['plugin_activations'] = $payload['plugin_activations'];
					ksort( $minimal['plugin_activations'] );
				}
			}

			if ( ! empty( $payload['sales_metrics'] ) && is_array( $payload['sales_metrics'] ) ) {
				$minimal['sales_metrics'] = array(
					'month'        => $payload['sales_metrics']['month'] ?? null,
					'orders_count' => $payload['sales_metrics']['orders_count'] ?? null,
					'gross_sales'  => $payload['sales_metrics']['gross_sales'] ?? null,
					'currency'     => $payload['sales_metrics']['currency'] ?? null,
				);
			}

			return hash( 'sha256', wp_json_encode( $minimal ) );
		}



		/**
		 * POST the site report payload to the Fluid Checkout licenses API.
		 *
		 * @param array $payload Site report payload.
		 */
		public static function send_site_report( $payload ) {
			$api_url = untrailingslashit( self::get_site_report_api_url() );

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
		 * Get the site report API base URL.
		 */
		public static function get_site_report_api_url() {
			return apply_filters(
				'fc_site_report_api_url',
				get_option( self::SITE_REPORT_API_URL_OPTION, 'https://fluidcheckout.com' )
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
				$plugin_row['license_key_hash'] = hash( 'sha256', strtoupper( $license_key ) );
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
