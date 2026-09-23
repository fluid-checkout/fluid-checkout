<?php
/**
 * Fluid Checkout Telemetry Client.
 * Opt-in site environment telemetry for Fluid Checkout ecosystem plugins.
 */
if ( ! class_exists( 'FC_Telemetry_Client' ) ) {
	class FC_Telemetry_Client {

		/**
		 * Whether the HTTP Origin injection filter has been registered.
		 *
		 * @var bool
		 */
		private static $http_origin_hooks_registered = false;

		/**
		 * API hosts allowed for Origin injection (lowercase).
		 *
		 * @var array
		 */
		private static $allowed_api_hosts = array();

		/**
		 * Telemetry configs keyed by normalized API URL.
		 *
		 * @var array
		 */
		private static $configs = array();

		/**
		 * Whether cron/init hooks have been registered for an API URL.
		 *
		 * @var array
		 */
		private static $cron_hooks_registered = array();



		/**
		 * Parse and normalize a telemetry config for one API URL.
		 *
		 * @param array $config Unfiltered config array.
		 */
		private static function parse_telemetry_config( $config ) {
			return wp_parse_args(
				is_array( $config ) ? $config : array(),
				array(
					'cron_hook'                       => 'fc_telemetry_weekly',
					'enable_option'                   => 'fc_telemetry_enabled',
					'data_groups_option'              => 'fc_telemetry_data_groups',
					'fingerprint_option'              => 'fc_telemetry_last_fingerprint',
					'last_sent_option'                => 'fc_telemetry_last_sent',
					'send_lock_transient'             => 'fc_telemetry_send_lock',
					'sales_backfill_sent_option'      => 'fc_telemetry_sales_backfill_sent',
					'last_sales_metrics_month_option' => 'fc_telemetry_last_sales_metrics_month',
					'changed_interval'                => WEEK_IN_SECONDS,
					'unchanged_interval'              => 4 * WEEK_IN_SECONDS,
				)
			);
		}



		/**
		 * Normalize an API URL for config lookup.
		 *
		 * @param string $api_url Remote API base URL.
		 */
		private static function normalize_api_url( $api_url ) {
			return untrailingslashit( (string) $api_url );
		}



		/**
		 * Get filtered telemetry config for an API URL.
		 *
		 * @param string $api_url Remote API base URL.
		 */
		public static function get_telemetry_config( $api_url ) {
			$api_url = self::normalize_api_url( $api_url );

			if ( isset( self::$configs[ $api_url ] ) ) {
				$config = self::$configs[ $api_url ];
			}
			else {
				$config = self::parse_telemetry_config( array() );
			}

			/**
			 * Filter telemetry client config for an API URL.
			 *
			 * @param array  $config  Telemetry config.
			 * @param string $api_url Remote API base URL.
			 */
			return apply_filters( 'fc_telemetry_client_config', $config, $api_url );
		}



		/**
		 * Get registered telemetry API URLs.
		 */
		public static function get_registered_api_urls() {
			return array_keys( self::$configs );
		}



		/**
		 * Register telemetry configs keyed by API URL and ensure cron hooks once per URL.
		 *
		 * @param array $settings Map of api_url => config.
		 */
		public static function register_telemetry_configs( $settings ) {
			// Bail if settings are missing
			if ( ! is_array( $settings ) || empty( $settings ) ) { return; }

			foreach ( $settings as $api_url => $config ) {
				$api_url = self::normalize_api_url( $api_url );

				// Bail if API URL is empty
				if ( '' === $api_url ) { continue; }

				$parsed = self::parse_telemetry_config( $config );

				if ( isset( self::$configs[ $api_url ] ) ) {
					$existing = self::$configs[ $api_url ];
					$existing['changed_interval']   = min( (int) $existing['changed_interval'], (int) $parsed['changed_interval'] );
					$existing['unchanged_interval'] = min( (int) $existing['unchanged_interval'], (int) $parsed['unchanged_interval'] );
					self::$configs[ $api_url ]      = $existing;
					continue;
				}

				self::$configs[ $api_url ] = $parsed;
				self::ensure_telemetry_hooks_for_api_url( $api_url );
			}
		}



		/**
		 * Register init and cron hooks once for an API URL.
		 *
		 * @param string $api_url Remote API base URL.
		 */
		private static function ensure_telemetry_hooks_for_api_url( $api_url ) {
			$api_url = self::normalize_api_url( $api_url );

			// Bail if hooks already registered for this API URL
			if ( ! empty( self::$cron_hooks_registered[ $api_url ] ) ) { return; }

			$config           = self::get_telemetry_config( $api_url );
			$resolved_api_url = self::get_remote_api_url( $api_url );
			$cron_hook        = apply_filters( 'fc_telemetry_cron_hook', $config['cron_hook'], $api_url );

			// Bail if cron hook is not defined
			if ( empty( $cron_hook ) ) { return; }

			self::remember_api_host( $resolved_api_url );
			self::maybe_register_http_origin_hooks();

			self::$cron_hooks_registered[ $api_url ] = true;

			add_action(
				'init',
				function () use ( $api_url ) {
					self::maybe_schedule_telemetry_cron( $api_url );
				}
			);

			add_action(
				$cron_hook,
				function () use ( $api_url ) {
					self::run_telemetry_cron( $api_url );
				}
			);
		}



		private static function get_api_request_headers( $extra = array() ) {
			$headers = array(
				'Origin' => home_url(),
			);

			if ( ! empty( $extra ) && is_array( $extra ) ) {
				$headers = array_merge( $headers, $extra );
			}

			return $headers;
		}



		/**
		 * Remember an API base URL host for Origin injection allowlisting.
		 *
		 * @param string $api_url Remote API base URL.
		 */
		private static function remember_api_host( $api_url ) {
			$host = wp_parse_url( untrailingslashit( (string) $api_url ), PHP_URL_HOST );

			// Bail if host missing
			if ( empty( $host ) ) {
				return;
			}

			self::$allowed_api_hosts[ strtolower( (string) $host ) ] = true;
		}



		private static function maybe_register_http_origin_hooks() {
			if ( self::$http_origin_hooks_registered ) {
				return;
			}

			add_filter( 'http_request_args', array( __CLASS__, 'filter_http_request_args_inject_origin' ), 10, 2 );
			self::$http_origin_hooks_registered = true;
		}



		/**
		 * Whether a remote URL targets the Fluid Licenses REST API on a known host.
		 *
		 * @param string $url Request URL.
		 */
		private static function is_telemetry_api_request_url( $url ) {
			$url = (string) $url;

			if ( '' === $url ) {
				return false;
			}

			// Own REST API path (telemetry and related licenses routes)
			if ( false === strpos( $url, '/wp-json/fc-licenses/' ) ) {
				return false;
			}

			$request_host = wp_parse_url( $url, PHP_URL_HOST );

			// Bail if request host missing
			if ( empty( $request_host ) ) {
				return false;
			}

			$request_host = strtolower( (string) $request_host );

			return isset( self::$allowed_api_hosts[ $request_host ] );
		}



		public static function filter_http_request_args_inject_origin( $args, $url ) {
			if ( ! self::is_telemetry_api_request_url( $url ) ) {
				return $args;
			}

			if ( ! is_array( $args ) ) {
				$args = array();
			}

			if ( empty( $args['headers'] ) || ! is_array( $args['headers'] ) ) {
				$args['headers'] = array();
			}

			$args['headers']['Origin'] = home_url();

			return $args;
		}



		private static function hash_license_key( $license_key ) {
			return hash( 'sha256', strtoupper( trim( (string) $license_key ) ) );
		}



		private static function looks_like_license_key_hash( $value ) {
			return (bool) preg_match( '/^[a-f0-9]{64}$/i', (string) $value );
		}



		private static function looks_like_masked_license_key( $value ) {
			return (bool) preg_match( '/(^|[^A-Z0-9])XXXX([^A-Z0-9]|$)/i', (string) $value );
		}



		public static function get_remote_api_url( $api_url = null, $plugin_slug = null ) {
			$api_url = apply_filters( 'fc_telemetry_api_url', $api_url, $plugin_slug );

			if ( empty( $api_url ) ) { return ''; }

			return $api_url;
		}



		//
		// TELEMETRY FUNCTIONS
		//


		/**
		 * Whether site environment reporting is enabled for an API URL.
		 *
		 * @param string|null $api_url Telemetry API base URL.
		 */
		public static function is_telemetry_enabled( $api_url = null ) {
			$api_url = self::resolve_api_url( $api_url );
			$config  = self::get_telemetry_config( $api_url );

			return 'yes' === get_option( $config['enable_option'], 'no' );
		}



		/**
		 * Whether site environment reporting is supported by this license client.
		 */
		public static function is_telemetry_supported() {
			return true;
		}



		/**
		 * Resolve an API URL, falling back to the first registered config when omitted.
		 *
		 * @param string|null $api_url Telemetry API base URL.
		 */
		private static function resolve_api_url( $api_url = null ) {
			$api_url = self::normalize_api_url( $api_url );

			if ( '' !== $api_url ) {
				return $api_url;
			}

			$registered = self::get_registered_api_urls();

			return ! empty( $registered[0] ) ? $registered[0] : '';
		}



		/**
		 * Schedule the weekly telemetry cron event for an API URL.
		 *
		 * @param string|null $api_url Telemetry API base URL.
		 */
		public static function schedule_telemetry_cron( $api_url = null ) {
			$api_url = self::resolve_api_url( $api_url );
			$config  = self::get_telemetry_config( $api_url );
			$cron_hook = apply_filters( 'fc_telemetry_cron_hook', $config['cron_hook'], $api_url );

			// Bail if cron hook is not defined
			if ( empty( $cron_hook ) ) { return; }

			// Bail if already scheduled
			if ( wp_next_scheduled( $cron_hook ) ) { return; }

			wp_schedule_event( time() + WEEK_IN_SECONDS, 'weekly', $cron_hook );
		}



		/**
		 * Schedule the weekly telemetry cron when reporting is enabled.
		 *
		 * @param string|null $api_url Telemetry API base URL.
		 */
		public static function maybe_schedule_telemetry_cron( $api_url = null ) {
			// Bail if telemetry is not supported
			if ( ! self::is_telemetry_supported() ) { return; }

			$api_url = self::resolve_api_url( $api_url );

			// Bail if telemetry is disabled
			if ( ! self::is_telemetry_enabled( $api_url ) ) { return; }

			self::schedule_telemetry_cron( $api_url );
		}



		/**
		 * Run the weekly site environment report cron job for an API URL.
		 *
		 * @param string|null $api_url Telemetry API base URL.
		 */
		public static function run_telemetry_cron( $api_url = null ) {
			// Bail if telemetry is not supported
			if ( ! self::is_telemetry_supported() ) { return; }

			self::maybe_send_telemetry( $api_url );
		}



		/**
		 * Maybe send a consolidated site environment report for an API URL.
		 *
		 * @param string|null $api_url Telemetry API base URL.
		 */
		public static function maybe_send_telemetry( $api_url = null ) {
			$api_url = self::resolve_api_url( $api_url );

			// Bail if telemetry is disabled
			if ( ! self::is_telemetry_enabled( $api_url ) ) { return; }

			self::send_telemetry_now( null, false, false, $api_url );
		}



		/**
		 * Send a site environment report immediately for an API URL.
		 *
		 * @param array|null  $groups             Optional data groups to include.
		 * @param bool        $enable_if_disabled Whether to enable scheduled reporting before sending.
		 * @param bool        $respect_send_rules Whether to apply fingerprint send intervals.
		 * @param string|null $api_url            Telemetry API base URL.
		 */
		public static function send_telemetry_now( $groups = null, $enable_if_disabled = false, $respect_send_rules = true, $api_url = null ) {
			$api_url = self::resolve_api_url( $api_url );
			$config  = self::get_telemetry_config( $api_url );

			if ( get_transient( $config['send_lock_transient'] ) ) {
				return array(
					'success'    => false,
					'error_code' => 'in_progress',
				);
			}

			if ( $enable_if_disabled && ! self::is_telemetry_enabled( $api_url ) ) {
				update_option( $config['enable_option'], 'yes' );
				self::schedule_telemetry_cron( $api_url );
			}

			if ( null !== $groups ) {
				$groups = self::normalize_telemetry_data_groups( $groups );
				update_option( $config['data_groups_option'], $groups );
			}

			if ( ! self::is_telemetry_enabled( $api_url ) ) {
				return array(
					'success'    => false,
					'error_code' => 'disabled',
				);
			}

			set_transient( $config['send_lock_transient'], 1, MINUTE_IN_SECONDS );

			$host = self::get_telemetry_host();

			if ( '' === $host || ! self::is_telemetry_domain_eligible( $host ) ) {
				delete_transient( $config['send_lock_transient'] );

				self::log_telemetry_error(
					'Telemetry domain is not eligible for sending.',
					array(
						'api_url'     => $api_url,
						'site_domain' => $host,
					)
				);

				return array(
					'success'    => false,
					'error_code' => '' === $host ? 'empty_payload' : 'ineligible_domain',
				);
			}

			$payload = self::build_telemetry_payload( $groups, null, $api_url );

			if ( empty( $payload ) || empty( $payload['site_domain'] ) ) {
				delete_transient( $config['send_lock_transient'] );

				self::log_telemetry_error(
					'Telemetry payload is empty or missing site_domain.',
					array(
						'api_url' => $api_url,
					)
				);

				return array(
					'success'    => false,
					'error_code' => 'empty_payload',
				);
			}

			$fingerprint = self::get_telemetry_fingerprint( $payload );

			if ( $respect_send_rules && ! self::should_send_telemetry( $fingerprint, $api_url ) ) {
				delete_transient( $config['send_lock_transient'] );

				return array(
					'success'    => false,
					'error_code' => 'rate_limited',
				);
			}

			$response      = self::send_telemetry( $payload, $api_url );
			$request_url   = untrailingslashit( self::get_remote_api_url( $api_url ) ) . '/wp-json/fc-licenses/v1/sites/telemetry';
			$response_code = 0;

			if ( is_wp_error( $response ) ) {
				self::log_telemetry_error(
					$response->get_error_message(),
					array(
						'api_url'     => $api_url,
						'request_url' => $request_url,
						'error_code'  => $response->get_error_code(),
					)
				);
			} else {
				$response_code = (int) wp_remote_retrieve_response_code( $response );

				if ( 200 !== $response_code ) {
					self::log_telemetry_error(
						'Telemetry request failed with HTTP ' . $response_code . '.',
						array(
							'api_url'       => $api_url,
							'request_url'   => $request_url,
							'response_code' => $response_code,
						)
					);
				}
			}

			if ( 200 === $response_code ) {
				update_option( $config['fingerprint_option'], $fingerprint );
				update_option( $config['last_sent_option'], time() );

				if ( in_array( 'woocommerce_sales_metrics', $payload['report_groups'] ?? array(), true ) ) {
					if ( ! self::has_telemetry_sales_backfill_sent( $api_url ) ) {
						update_option( $config['sales_backfill_sent_option'], 'yes' );
					}

					self::update_telemetry_last_sales_metrics_month( $payload, $api_url );
				}
			}

			delete_transient( $config['send_lock_transient'] );

			if ( 200 !== $response_code ) {
				return array(
					'success'       => false,
					'error_code'    => 'request_failed',
					'response_code' => $response_code,
				);
			}

			return array(
				'success'       => true,
				'response_code' => $response_code,
				'is_enabled'    => self::is_telemetry_enabled( $api_url ),
			);
		}



		/**
		 * Log a telemetry error to the WooCommerce logger.
		 *
		 * @param string $message Log message.
		 * @param array  $context Optional context data.
		 */
		private static function log_telemetry_error( $message, $context = array() ) {
			if ( ! function_exists( 'wc_get_logger' ) ) { return; }

			$context['source'] = 'fc-telemetry';

			wc_get_logger()->error( $message, $context );
		}



		/**
		 * Determine whether a telemetry should be sent for the current fingerprint.
		 *
		 * Sends on first run, after the changed interval when changed, or after the unchanged interval when unchanged.
		 *
		 * @param string      $fingerprint Payload fingerprint hash.
		 * @param string|null $api_url     Telemetry API base URL.
		 */
		private static function should_send_telemetry( $fingerprint, $api_url = null ) {
			$api_url = self::resolve_api_url( $api_url );
			$config  = self::get_telemetry_config( $api_url );

			$last_fingerprint = get_option( $config['fingerprint_option'], '' );
			$last_sent        = (int) get_option( $config['last_sent_option'], 0 );

			// Bail if the telemetry has never been sent
			if ( empty( $last_sent ) ) { return true; }

			$elapsed = time() - $last_sent;

			// Bail if the telemetry should not be sent
			if ( $fingerprint !== $last_fingerprint ) { return $elapsed >= (int) $config['changed_interval']; }

			return $elapsed >= (int) $config['unchanged_interval'];
		}



		/**
		 * Get normalized telemetry data groups selected by the merchant.
		 *
		 * @param string|null $api_url Telemetry API base URL.
		 */
		public static function get_telemetry_data_groups( $api_url = null ) {
			$api_url = self::resolve_api_url( $api_url );
			$config  = self::get_telemetry_config( $api_url );
			$groups  = get_option( $config['data_groups_option'], array( 'basic_environment' ) );

			return self::normalize_telemetry_data_groups( $groups );
		}



		/**
		 * Normalize selected telemetry data groups.
		 *
		 * @param mixed $groups Raw or sanitized group values.
		 */
		public static function normalize_telemetry_data_groups( $groups ) {
			$allowed = array( 'basic_environment', 'plugin_settings', 'woocommerce_sales_metrics' );

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

			$dependent_groups = array( 'plugin_settings', 'woocommerce_sales_metrics' );

			if ( array_intersect( $groups, $dependent_groups ) && ! in_array( 'basic_environment', $groups, true ) ) {
				$groups[] = 'basic_environment';
			}

			return array_values( array_unique( $groups ) );
		}



		/**
		 * Get the plain site host from home_url (no eligibility checks).
		 */
		private static function get_telemetry_host() {
			$parsed = wp_parse_url( home_url() );

			if ( empty( $parsed['host'] ) ) {
				return '';
			}

			return strtolower( $parsed['host'] );
		}



		/**
		 * Whether a site domain is eligible for sending telemetrys.
		 *
		 * Blocks IP hosts, single-label hosts, localhost, and common local/dev suffixes.
		 *
		 * @param string $domain Site host.
		 */
		public static function is_telemetry_domain_eligible( $domain ) {
			$domain = strtolower( (string) $domain );

			if ( '' === $domain ) {
				return false;
			}

			$eligible = true;

			if ( filter_var( $domain, FILTER_VALIDATE_IP ) ) {
				$eligible = false;
			}
			elseif ( substr_count( $domain, '.' ) < 1 ) {
				$eligible = false;
			}
			elseif ( 'localhost' === $domain ) {
				$eligible = false;
			}
			else {
				$blocked_suffixes = array(
					'.local',
					'.localhost',
					'.test',
					'.invalid',
					'.example',
					'.internal',
					'.intranet',
					'.lan',
					'.home',
					'.corp',
					'.localdomain',
				);

				foreach ( $blocked_suffixes as $suffix ) {
					if ( $domain === ltrim( $suffix, '.' ) || substr( $domain, -strlen( $suffix ) ) === $suffix ) {
						$eligible = false;
						break;
					}
				}
			}

			/**
			 * Filter whether a site domain may send telemetrys.
			 *
			 * @param bool   $eligible Whether the domain is eligible.
			 * @param string $domain   Site host.
			 */
			return (bool) apply_filters( 'fc_telemetry_is_domain_eligible', $eligible, $domain );
		}



		/**
		 * Get the plain site domain for telemetry payloads.
		 *
		 * @param bool $require_eligible Whether to return empty for ineligible/local domains.
		 */
		private static function get_telemetry_domain( $require_eligible = true ) {
			$domain = self::get_telemetry_host();

			if ( '' === $domain ) {
				return '';
			}

			if ( $require_eligible && ! self::is_telemetry_domain_eligible( $domain ) ) {
				return '';
			}

			return $domain;
		}



		/**
		 * Build the site environment report payload.
		 *
		 * Domain eligibility is enforced when sending, not when building. Admin preview
		 * and local development can inspect the payload for ineligible hosts.
		 *
		 * @param array|null  $groups                  Optional data groups to include. When omitted, uses saved settings and requires opt-in.
		 * @param string|null $plugins_report_scope    Whether the plugins list is a full inventory or partial subset. Defaults to `complete`.
		 * @param string|null $api_url                 Telemetry API base URL from the consuming plugin.
		 * @param bool        $require_eligible_domain Whether to require a production-eligible domain. Defaults to false.
		 */
		public static function build_telemetry_payload( $groups = null, $plugins_report_scope = null, $api_url = null, $require_eligible_domain = false ) {
			$api_url = self::resolve_api_url( $api_url );

			if ( null === $groups ) {
				// Bail if telemetry is disabled
				if ( ! self::is_telemetry_enabled( $api_url ) ) { return array(); }

				$groups = self::get_telemetry_data_groups( $api_url );
			}
			else {
				$groups = self::normalize_telemetry_data_groups( $groups );
			}

			// Bail if no data groups are selected
			if ( empty( $groups ) ) { return array(); }

			$site_domain = self::get_telemetry_domain( $require_eligible_domain );

			if ( '' === $site_domain ) {
				return array();
			}

			$payload  = array(
				'schema_version' => 2,
				'site_domain'    => $site_domain,
				'report_groups'  => $groups,
			);

			if ( in_array( 'basic_environment', $groups, true ) ) {
				$payload = array_merge( $payload, self::build_basic_environment_payload( $api_url ) );
				$payload['plugin_activations']  = self::build_plugin_activations( $api_url );
				$payload['plugins_report_scope'] = null !== $plugins_report_scope ? $plugins_report_scope : 'complete';
			}

			if ( in_array( 'woocommerce_sales_metrics', $groups, true ) ) {
				$sales_metrics = self::build_sales_metrics();

				if ( ! empty( $sales_metrics ) ) {
					$payload['sales_metrics'] = $sales_metrics;
				}

				if ( ! self::has_telemetry_sales_backfill_sent( $api_url ) ) {
					$sales_metrics_history = self::build_sales_metrics_history();

					if ( ! empty( $sales_metrics_history ) ) {
						$payload['sales_metrics_history'] = $sales_metrics_history;
					}
				}
				else {
					$sales_metrics_catchup = self::build_sales_metrics_catchup( $api_url );

					if ( ! empty( $sales_metrics_catchup ) ) {
						$payload['sales_metrics_history'] = $sales_metrics_catchup;
					}
				}
			}

			return $payload;
		}


		/**
		 * Build the basic environment section of the telemetry payload.
		 *
		 * @param string|null $api_url Telemetry API base URL from the consuming plugin.
		 */
		private static function build_basic_environment_payload( $api_url = null ) {
			$theme   = wp_get_theme();
			$plugins = array();

			if ( function_exists( 'get_plugins' ) ) {
				foreach ( get_plugins() as $plugin_file => $plugin_data ) {
					$plugin_slug = self::get_plugin_slug_from_file( $plugin_file );
					$plugin_row  = array(
						'plugin_slug' => $plugin_slug,
						'name'        => $plugin_data['Name'],
						'version'     => $plugin_data['Version'],
						'active'      => is_plugin_active( $plugin_file ),
					);

					self::maybe_add_own_plugin_license_hash_row( $plugin_row, $plugin_slug, $api_url );
					$plugins[] = $plugin_row;
				}
			}

			return array(
				'wp_version'        => get_bloginfo( 'version' ),
				'php_version'       => PHP_VERSION,
				'locale'            => get_locale(),
				'wc_store_country'  => self::get_wc_store_country(),
				'wc_store_timezone' => self::get_wc_store_timezone(),
				'wc_store_currency' => self::get_wc_store_currency(),
				'is_multisite'      => is_multisite(),
				'theme_template'    => $theme->get_template(),
				'theme_stylesheet'  => $theme->get_stylesheet(),
				'theme_version'     => $theme->get( 'Version' ),
				'plugins'           => $plugins,
			);
		}



		/**
		 * Get the WooCommerce store base country code.
		 */
		private static function get_wc_store_country() {
			if ( ! function_exists( 'WC' ) || ! WC() ) {
				return null;
			}

			$country = WC()->countries->get_base_country();

			return $country ? $country : null;
		}



		/**
		 * Get the store timezone identifier used for WooCommerce reporting.
		 */
		private static function get_wc_store_timezone() {
			$timezone_name = self::get_telemetry_timezone()->getName();

			return $timezone_name ? $timezone_name : null;
		}



		/**
		 * Get the WooCommerce store currency code.
		 */
		private static function get_wc_store_currency() {
			if ( ! function_exists( 'get_woocommerce_currency' ) ) {
				return null;
			}

			$currency = get_woocommerce_currency();

			return $currency ? $currency : null;
		}



		/**
		 * Whether sales metrics history backfill has already been sent.
		 *
		 * @param string|null $api_url Telemetry API base URL.
		 */
		private static function has_telemetry_sales_backfill_sent( $api_url = null ) {
			$api_url = self::resolve_api_url( $api_url );
			$config  = self::get_telemetry_config( $api_url );

			return 'yes' === get_option( $config['sales_backfill_sent_option'], 'no' );
		}



		/**
		 * Get the site timezone used for monthly sales metrics.
		 */
		private static function get_telemetry_timezone() {
			return wp_timezone();
		}



		/**
		 * Get the last closed calendar month key (YYYY-MM) in the site timezone.
		 */
		private static function get_last_closed_calendar_month_key() {
			$date = new DateTimeImmutable( 'first day of last month', self::get_telemetry_timezone() );

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
				self::get_telemetry_timezone()
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
			$timezone   = self::get_telemetry_timezone();
			$aggregates = array();

			foreach ( $month_keys as $month_key ) {
				$aggregates[ $month_key ] = array(
					'orders_count' => 0,
					'gross_sales'  => 0.0,
				);
			}

			$orders_limit = 100;
			$page         = 1;

			do {
				$orders = wc_get_orders(
					array(
						'limit'        => $orders_limit,
						'page'         => $page,
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

				$page++;
			} while ( count( $orders ) === $orders_limit );

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
		 *
		 * @param string|null $api_url Telemetry API base URL.
		 */
		private static function build_sales_metrics_catchup( $api_url = null ) {
			if ( ! function_exists( 'wc_get_orders' ) ) {
				return array();
			}

			$api_url         = self::resolve_api_url( $api_url );
			$config          = self::get_telemetry_config( $api_url );
			$last_sent_month = get_option( $config['last_sales_metrics_month_option'], '' );

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
		 * Remember the last closed sales month sent in a successful telemetry.
		 *
		 * @param array       $payload Telemetry payload.
		 * @param string|null $api_url Telemetry API base URL.
		 */
		private static function update_telemetry_last_sales_metrics_month( $payload, $api_url = null ) {
			if ( empty( $payload['sales_metrics']['month'] ) ) {
				return;
			}

			$api_url = self::resolve_api_url( $api_url );
			$config  = self::get_telemetry_config( $api_url );

			update_option(
				$config['last_sales_metrics_month_option'],
				sanitize_text_field( (string) $payload['sales_metrics']['month'] )
			);
		}



		/**
		 * Build monthly sales metrics history for the first telemetry.
		 */
		private static function build_sales_metrics_history() {
			if ( ! function_exists( 'wc_get_orders' ) ) {
				return array();
			}

			$lite_activation = (int) get_option( 'fc_plugin_activation_time', 0 );

			if ( $lite_activation <= 0 ) {
				return array();
			}

			$timezone          = self::get_telemetry_timezone();
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
		 * Build first-activation timestamps for registered own plugins.
		 *
		 * @param string|null $api_url Telemetry API base URL from the consuming plugin.
		 */
		private static function build_plugin_activations( $api_url = null ) {
			$activations = array();

			foreach ( self::get_own_plugins_option_map( $api_url ) as $plugin_slug => $plugin_options ) {
				if ( empty( $plugin_options['activation_time_option'] ) ) {
					$activations[ $plugin_slug ] = null;
					continue;
				}

				$timestamp = get_option( $plugin_options['activation_time_option'], null );

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
		 * @param array $payload Telemetry payload.
		 */
		public static function get_telemetry_fingerprint( $payload ) {
			$minimal = array(
				'site_domain'   => $payload['site_domain'] ?? '',
				'report_groups' => $payload['report_groups'] ?? array(),
			);

			if ( ! empty( $payload['report_groups'] ) && in_array( 'basic_environment', $payload['report_groups'], true ) ) {
				$minimal['wp_version']       = $payload['wp_version'] ?? '';
				$minimal['php_version']      = $payload['php_version'] ?? '';
				$minimal['locale']           = $payload['locale'] ?? '';
				$minimal['wc_store_country']  = $payload['wc_store_country'] ?? null;
				$minimal['wc_store_timezone'] = $payload['wc_store_timezone'] ?? null;
				$minimal['wc_store_currency'] = $payload['wc_store_currency'] ?? null;
				$minimal['is_multisite']     = $payload['is_multisite'] ?? false;
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
		 * POST the telemetry payload to the plugin's licenses API.
		 *
		 * @param array       $payload Telemetry payload.
		 * @param string|null $api_url Telemetry API base URL.
		 */
		public static function send_telemetry( $payload, $api_url = null ) {
			$api_url = untrailingslashit( self::get_remote_api_url( self::resolve_api_url( $api_url ) ) );

			// Bail if API URL is not defined
			if ( empty( $api_url ) ) {
				return new WP_Error( 'fc_telemetry_missing_remote_api_url', 'Remote API URL is not defined.' );
			}

			self::remember_api_host( $api_url );
			self::maybe_register_http_origin_hooks();

			return wp_remote_post(
				$api_url . '/wp-json/fc-licenses/v1/sites/telemetry',
				array(
					'headers' => self::get_api_request_headers(
						array(
							'Content-Type' => 'application/json',
							'User-Agent'   => 'Fluid Checkout Telemetry/' . self::get_telemetry_user_agent_version( $api_url ),
						)
					),
					'body'    => wp_json_encode( $payload ),
					'timeout' => 15,
				)
			);
		}




		private static function get_telemetry_user_agent_version( $api_url = null ) {
			// Bail if `get_plugins` function is not available
			if ( ! function_exists( 'get_plugins' ) ) { return 'unknown'; }

			$own_plugins = array_keys( self::get_own_plugins_option_map( $api_url ) );
			$plugins     = get_plugins();

			foreach ( $plugins as $plugin_file => $plugin_data ) {
				$plugin_slug = self::get_plugin_slug_from_file( $plugin_file );

				// Skip if not a registered own plugin
				if ( ! in_array( $plugin_slug, $own_plugins, true ) ) { continue; }

				if ( ! is_plugin_active( $plugin_file ) ) { continue; }

				if ( ! empty( $plugin_data['Version'] ) ) {
					return $plugin_data['Version'];
				}
			}

			return 'unknown';
		}



		private static function get_own_plugins_option_map( $api_url = null ) {
			$plugins = apply_filters( 'fc_telemetry_own_plugins', array(), $api_url );

			if ( ! is_array( $plugins ) ) {
				return array();
			}

			return $plugins;
		}



		private static function maybe_add_own_plugin_license_hash_row( &$plugin_row, $plugin_slug, $api_url = null ) {
			$plugins_map = self::get_own_plugins_option_map( $api_url );

			if ( ! array_key_exists( $plugin_slug, $plugins_map ) ) {
				return;
			}

			$plugin_options = $plugins_map[ $plugin_slug ];

			// Bail if this own plugin has no license options (e.g. Lite)
			if ( empty( $plugin_options['license_key_option'] ) || empty( $plugin_options['license_key_hash_option'] ) ) {
				return;
			}

			$license_key  = get_option( $plugin_options['license_key_option'], '' );
			$license_hash = get_option( $plugin_options['license_key_hash_option'], '' );

			// Prefer stored hash; otherwise derive from plaintext key (never hash masked display values).
			if ( ! empty( $license_hash ) && self::looks_like_license_key_hash( $license_hash ) ) {
				$plugin_row['license_key_hash'] = strtolower( (string) $license_hash );
			} elseif ( ! empty( $license_key ) && ! self::looks_like_masked_license_key( $license_key ) && ! self::looks_like_license_key_hash( $license_key ) ) {
				$plugin_row['license_key_hash'] = self::hash_license_key( $license_key );
			} elseif ( ! empty( $license_key ) && self::looks_like_license_key_hash( $license_key ) ) {
				$plugin_row['license_key_hash'] = strtolower( (string) $license_key );
			}
		}



		private static function get_plugin_slug_from_file( $plugin_file ) {
			$plugin_file = str_replace( '\\', '/', $plugin_file );
			$parts       = explode( '/', $plugin_file );

			return sanitize_key( $parts[0] );
		}

	}
}
