<?php
/**
 * Fluid Licenses Client.
 * Allow activation, auto updates, and site environment reports for plugins hosted with Fluid Licenses.
 */
if ( ! class_exists( 'FC_Licenses_Client' ) ) {
	class FC_Licenses_Client {
		/**
		 * Parsed plugin configs keyed by plugin slug.
		 *
		 * @var array
		 */
		private static $plugin_configs = array();

		/**
		 * Per-plugin flag for whether the update API was called.
		 *
		 * @var array
		 */
		private static $api_update_called = array();

		/**
		 * Whether WP update hooks have been registered.
		 *
		 * @var bool
		 */
		private static $update_hooks_registered = false;

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



		//
		// PLUGIN UPDATE FUNCTIONS
		//



		/**
		 * Parse and normalize client config.
		 *
		 * @param   string  $plugin_slug  Plugin slug.
		 * @param   string  $plugin_file  Main plugin file path.
		 * @param   array   $config       Client config array.
		 */
		private static function parse_client_config( $plugin_slug, $plugin_file, $config ) {
			$config = wp_parse_args(
				$config,
				array(
					'product_id'              => '',
					'api_url'                 => '',
					'license_key'             => '',
					'license_key_option'      => '',
					'license_key_hash_option' => '',
					'activate_option'         => '',
				)
			);

			$config['plugin_slug'] = $plugin_slug;
			$config['plugin_file'] = $plugin_file;

			return $config;
		}



		/**
		 * Initialize plugin update hooks for a plugin.
		 *
		 * @param   string  $plugin_slug  Plugin slug.
		 * @param   string  $plugin_file  Main plugin file path.
		 * @param   array   $config       Unfiltered client config array.
		 */
		public static function init_plugin_update_hooks( $plugin_slug, $plugin_file, $config ) {
			self::$plugin_configs[ $plugin_slug ] = self::parse_client_config( $plugin_slug, $plugin_file, $config );

			if ( ! self::$update_hooks_registered ) {
				add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'filter_set_transient' ) );
				add_filter( 'plugins_api', array( __CLASS__, 'filter_set_plugin_info' ), 10, 3 );
				add_filter( 'upgrader_post_install', array( __CLASS__, 'filter_post_install' ), 10, 3 );
				self::$update_hooks_registered = true;
			}
		}



		/**
		 * Get filtered plugin config.
		 *
		 * @param   string  $plugin_slug  Plugin slug.
		 */
		public static function get_plugin_config( $plugin_slug ) {
			if ( ! isset( self::$plugin_configs[ $plugin_slug ] ) ) {
				return array();
			}

			$config = self::$plugin_configs[ $plugin_slug ];

			return apply_filters( 'fc_licenses_client_config', $config, $plugin_slug );
		}



		/**
		 * Get plugin context for update operations.
		 *
		 * @param   string  $plugin_slug  Plugin slug.
		 */
		private static function get_plugin_context( $plugin_slug ) {
			$config = self::get_plugin_config( $plugin_slug );

			if ( empty( $config ) || empty( $config['plugin_file'] ) ) {
				return null;
			}

			return array(
				'slug'        => plugin_basename( $config['plugin_file'] ),
				'plugin_data' => get_plugin_data( $config['plugin_file'] ),
				'config'      => $config,
			);
		}



		/**
		 * Get default HTTP headers for license and site-report API requests.
		 *
		 * @param array $extra Optional headers to merge.
		 */
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
		 * Hash a license key the same way as FC_Licenses_LicenseKeyRepository.
		 *
		 * @param string $license_key Raw license key.
		 */
		public static function hash_license_key( $license_key ) {
			return hash( 'sha256', strtoupper( trim( (string) $license_key ) ) );
		}



		/**
		 * Whether a value looks like a SHA-256 license key hash.
		 *
		 * @param string $value Candidate value.
		 */
		public static function looks_like_license_key_hash( $value ) {
			return (bool) preg_match( '/^[a-f0-9]{64}$/i', (string) $value );
		}



		/**
		 * Get the stored license key hash for a plugin config.
		 *
		 * @param array $config Parsed client config.
		 */
		public static function get_stored_license_key_hash( $config ) {
			if ( empty( $config['license_key_hash_option'] ) ) {
				return '';
			}

			$hash = get_option( $config['license_key_hash_option'], '' );

			return is_string( $hash ) ? $hash : '';
		}



		/**
		 * Resolve the license key hash to use for API paths and package URLs.
		 *
		 * Prefers the stored hash option; otherwise hashes an in-memory raw key.
		 *
		 * @param array $config Parsed client config.
		 */
		public static function resolve_license_key_hash( $config ) {
			$hash = self::get_stored_license_key_hash( $config );

			if ( ! empty( $hash ) ) {
				return $hash;
			}

			if ( empty( $config['license_key'] ) ) {
				return '';
			}

			$license_key = (string) $config['license_key'];

			if ( self::looks_like_license_key_hash( $license_key ) ) {
				return strtolower( $license_key );
			}

			return self::hash_license_key( $license_key );
		}



		/**
		 * Invalidate license activation status cache.
		 *
		 * @param string $license_key_hash License key hash.
		 */
		private static function invalidate_license_activation_cache( $license_key_hash ) {
			if ( empty( $license_key_hash ) ) {
				return;
			}

			$domain = wp_parse_url( home_url(), PHP_URL_HOST );
			$domain = is_string( $domain ) ? $domain : '';

			delete_transient( 'fc_lcs_license_active_' . md5( $license_key_hash . '|' . $domain ) );
		}



		/**
		 * Whether a license get_license_key_details/activate_license_key response is successful own-API data.
		 *
		 * @param mixed $response Decoded API response.
		 */
		public static function is_own_license_data_response( $response ) {
			return is_object( $response )
				&& isset( $response->data )
				&& is_object( $response->data )
				&& isset( $response->data->id )
				&& ! isset( $response->code );
		}



		/**
		 * Check whether a plugin license is activated for this site (via get_license_key_details).
		 *
		 * @param string $plugin_slug Plugin slug.
		 */
		public static function is_license_activated( $plugin_slug ) {
			$config = self::get_plugin_config( $plugin_slug );

			if ( empty( $config ) || empty( $config['plugin_file'] ) ) {
				return false;
			}

			$hash = self::resolve_license_key_hash( $config );

			if ( empty( $hash ) ) {
				return false;
			}

			$domain    = wp_parse_url( home_url(), PHP_URL_HOST );
			$domain    = is_string( $domain ) ? $domain : '';
			$cache_key = 'fc_lcs_license_active_' . md5( $hash . '|' . $domain );
			$cached    = get_transient( $cache_key );

			if ( false !== $cached ) {
				return (bool) $cached;
			}

			$config['license_key'] = $hash;
			$result                = self::get_license_key_details( $plugin_slug, $config['plugin_file'], $config );
			$is_active             = self::is_own_license_data_response( $result );

			set_transient( $cache_key, $is_active ? 1 : 0, DAY_IN_SECONDS );

			return $is_active;
		}



		/**
		 * Call API with GET.
		 *
		 * @param string $url     API URL.
		 * @param array  $headers Optional HTTP headers.
		 */
		private static function call_api( $url, $headers = array() ) {
			$response = wp_remote_get(
				$url,
				array(
					'headers' => self::get_api_request_headers( $headers ),
					'timeout' => 15,
				)
			);

			if ( is_wp_error( $response ) ) {
				return '';
			}

			return wp_remote_retrieve_body( $response );
		}



		/**
		 * Call API with POST.
		 *
		 * @param string $url     API URL.
		 * @param array  $headers Optional HTTP headers.
		 * @param array  $args    Optional extra wp_remote_post args.
		 */
		private static function call_api_post( $url, $headers = array(), $args = array() ) {
			$request_args = array_merge(
				array(
					'headers' => self::get_api_request_headers( $headers ),
					'timeout' => 15,
				),
				is_array( $args ) ? $args : array()
			);

			$response = wp_remote_post( $url, $request_args );

			if ( is_wp_error( $response ) ) {
				return '';
			}

			return wp_remote_retrieve_body( $response );
		}



		/**
		 * Normalize an API base URL for grouping and cache keys.
		 *
		 * @param string      $api_url     Remote API base URL.
		 * @param string|null $plugin_slug Plugin slug.
		 */
		private static function normalize_api_url( $api_url, $plugin_slug = null ) {
			return untrailingslashit( self::get_remote_api_url( $api_url, $plugin_slug ) );
		}



		/**
		 * Get registered product IDs for a normalized API URL.
		 *
		 * @param string $api_url Normalized API base URL.
		 */
		private static function get_registered_product_ids_for_api_url( $api_url ) {
			$product_ids = array();

			foreach ( array_keys( self::$plugin_configs ) as $plugin_slug ) {
				$config = self::get_plugin_config( $plugin_slug );

				if ( empty( $config['product_id'] ) ) {
					continue;
				}

				$config_api_url = self::normalize_api_url( $config['api_url'], $config['plugin_slug'] );

				if ( $config_api_url !== $api_url ) {
					continue;
				}

				$product_ids[] = absint( $config['product_id'] );
			}

			$product_ids = array_values( array_unique( array_filter( $product_ids ) ) );
			sort( $product_ids, SORT_NUMERIC );

			return $product_ids;
		}



		/**
		 * Build batch updates transient name from API URL + product ID set.
		 *
		 * @param string $api_url     Normalized API base URL.
		 * @param array  $product_ids Sorted unique product IDs.
		 */
		private static function get_batch_updates_transient_name( $api_url, $product_ids ) {
			$fingerprint = md5( $api_url . '|' . implode( ',', $product_ids ) );

			return 'fc_lcs_batch_updates_' . $fingerprint;
		}



		/**
		 * Fetch batch product update metadata for an API URL + product ID set.
		 *
		 * @param string $api_url      Normalized API base URL.
		 * @param array  $product_ids  Product IDs.
		 * @param bool   $force_check  Whether to bypass the batch transient.
		 */
		private static function fetch_batch_product_updates( $api_url, $product_ids, $force_check = false ) {
			$product_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $product_ids ) ) ) );
			sort( $product_ids, SORT_NUMERIC );

			if ( empty( $api_url ) || empty( $product_ids ) ) {
				return null;
			}

			$transient_name = self::get_batch_updates_transient_name( $api_url, $product_ids );

			if ( ! $force_check ) {
				$transient = get_transient( $transient_name );
				if ( false !== $transient ) {
					return $transient;
				}
			}

			$url = add_query_arg(
				array(
					'product_ids' => $product_ids,
				),
				$api_url . '/wp-json/fc-licenses/v1/products/updates'
			);

			$response = self::call_api( $url );
			$decoded  = $response ? json_decode( $response ) : null;

			set_transient( $transient_name, $decoded, DAY_IN_SECONDS );

			return $decoded;
		}



		/**
		 * Prefetch batch updates for all registered plugins, grouped by API URL.
		 *
		 * @param bool $force_check Whether to force fresh API checks.
		 */
		private static function prefetch_batch_updates_for_registered_plugins( $force_check = false ) {
			$groups = array();

			foreach ( array_keys( self::$plugin_configs ) as $plugin_slug ) {
				$config = self::get_plugin_config( $plugin_slug );

				if ( empty( $config['product_id'] ) || empty( $config['api_url'] ) ) {
					continue;
				}

				$api_url = self::normalize_api_url( $config['api_url'], $config['plugin_slug'] );

				if ( empty( $api_url ) ) {
					continue;
				}

				if ( ! isset( $groups[ $api_url ] ) ) {
					$groups[ $api_url ] = array();
				}

				$groups[ $api_url ][] = $plugin_slug;
			}

			foreach ( $groups as $api_url => $plugin_slugs ) {
				$product_ids = self::get_registered_product_ids_for_api_url( $api_url );
				$group_force = false;

				foreach ( $plugin_slugs as $plugin_slug ) {
					if ( empty( self::$api_update_called[ $plugin_slug ] ) && $force_check ) {
						$group_force = true;
						break;
					}
				}

				self::fetch_batch_product_updates( $api_url, $product_ids, $group_force );

				foreach ( $plugin_slugs as $plugin_slug ) {
					self::$api_update_called[ $plugin_slug ] = true;
				}
			}
		}



		/**
		 * Get information regarding plugin releases from repository.
		 *
		 * @param   string  $plugin_slug   Plugin slug.
		 * @param   bool    $force_check   Whether to force a fresh API check.
		 */
		private static function get_release_info( $plugin_slug, $force_check = false ) {
			$config = self::get_plugin_config( $plugin_slug );

			if ( empty( $config ) || empty( $config['product_id'] ) ) {
				return null;
			}

			$api_url     = self::normalize_api_url( $config['api_url'], $config['plugin_slug'] );
			$product_ids = self::get_registered_product_ids_for_api_url( $api_url );
			$batch       = self::fetch_batch_product_updates( $api_url, $product_ids, $force_check );

			self::$api_update_called[ $plugin_slug ] = true;

			if ( ! $batch || ! isset( $batch->data ) || ! isset( $batch->data->products ) ) {
				return null;
			}

			$product_id_key = (string) absint( $config['product_id'] );
			$products       = $batch->data->products;

			if ( is_object( $products ) && isset( $products->{$product_id_key} ) ) {
				$wrapper       = new \stdClass();
				$wrapper->data = $products->{$product_id_key};
				return $wrapper;
			}

			if ( is_array( $products ) && isset( $products[ $product_id_key ] ) ) {
				$wrapper       = new \stdClass();
				$wrapper->data = $products[ $product_id_key ];
				return $wrapper;
			}

			return null;
		}



		/**
		 * Get the plugin license key details from the license manager server.
		 *
		 * @param   string  $plugin_slug  Plugin slug.
		 * @param   string  $plugin_file  Main plugin file path.
		 * @param   array   $config       Client config array.
		 */
		public static function get_license_key_details( $plugin_slug, $plugin_file, $config ) {
			$config = self::parse_client_config( $plugin_slug, $plugin_file, $config );

			$license_key_hash = self::resolve_license_key_hash( $config );

			if ( empty( $license_key_hash ) ) {
				$error_response          = new \stdClass();
				$error_response->code    = 'fwplm_missing_license_key';
				$error_response->message = 'Missing the license key hash. Activate a license key first.';
				return $error_response;
			}

			$api_url = self::normalize_api_url( $config['api_url'], $config['plugin_slug'] );
			$url     = $api_url . '/wp-json/fc-licenses/v1/licenses/' . rawurlencode( $license_key_hash );

			$response = self::call_api( $url );

			if ( $response ) {
				$data = json_decode( $response );
				return $data;
			}

			$error_response          = new \stdClass();
			$error_response->code    = 'fwplm_rest_connection_error';
			$error_response->message = sprintf( 'Couldn\'t connect to the license server (%s). Try again later.', $api_url );
			return $error_response;
		}



		/**
		 * Activate the plugin license key against the license manager server.
		 *
		 * @param   string  $plugin_slug  Plugin slug.
		 * @param   string  $plugin_file  Main plugin file path.
		 * @param   array   $config       Client config array.
		 */
		public static function activate_license_key( $plugin_slug, $plugin_file, $config ) {
			$config = self::parse_client_config( $plugin_slug, $plugin_file, $config );

			if ( empty( $config['license_key'] ) ) {
				$error_response          = new \stdClass();
				$error_response->code    = 'fwplm_missing_license_key';
				$error_response->message = 'Missing the license key. Please provide a valid license key and try again.';
				return $error_response;
			}

			$raw_license_key = (string) $config['license_key'];

			if ( self::looks_like_license_key_hash( $raw_license_key ) ) {
				$error_response          = new \stdClass();
				$error_response->code    = 'fc_lcs_license_key_hash_not_allowed';
				$error_response->message = 'Hashed license keys are not allowed for activation. Provide the raw license key.';
				return $error_response;
			}

			$api_url = self::normalize_api_url( $config['api_url'], $config['plugin_slug'] );
			$url     = $api_url . '/wp-json/fc-licenses/v1/licenses/activate/' . rawurlencode( $raw_license_key );

			$response = self::call_api_post( $url );

			if ( $response ) {
				$data = json_decode( $response );

				if ( self::is_own_license_data_response( $data ) ) {
					$hash = self::hash_license_key( $raw_license_key );

					if ( ! empty( $config['license_key_hash_option'] ) ) {
						update_option( $config['license_key_hash_option'], $hash, false );
					}

					if ( ! empty( $config['license_key_option'] ) ) {
						delete_option( $config['license_key_option'] );
					}

					if ( ! empty( $config['activate_option'] ) ) {
						delete_option( $config['activate_option'] );
					}

					self::invalidate_license_activation_cache( $hash );

					return $data;
				}

				if ( $data && isset( $data->message ) && $data->message ) {
					$error_response          = new \stdClass();
					$error_response->code    = isset( $data->code ) ? $data->code : 'fwplm_generic_error';
					$error_response->message = $data->message;
					return $error_response;
				}
			}

			$error_response          = new \stdClass();
			$error_response->code    = 'fwplm_rest_connection_error';
			$error_response->message = sprintf( 'Couldn\'t connect to the license server (%s). Try again later.', $api_url );
			return $error_response;
		}



		/**
		 * Filter callback for plugin update transients.
		 *
		 * @param   object  $transient  Update plugins transient.
		 */
		public static function filter_set_transient( $transient ) {
			if ( ! isset( $transient->response ) ) {
				return $transient;
			}

			$force_check = ! empty( $_GET['force-check'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			self::prefetch_batch_updates_for_registered_plugins( $force_check );

			foreach ( array_keys( self::$plugin_configs ) as $plugin_slug ) {
				$transient = self::set_transient_for_plugin( $transient, $plugin_slug );
			}

			return $transient;
		}



		/**
		 * Push in plugin version information to get the update notification.
		 *
		 * @param   object  $transient    Update plugins transient.
		 * @param   string  $plugin_slug  Plugin slug.
		 */
		private static function set_transient_for_plugin( $transient, $plugin_slug ) {
			$context = self::get_plugin_context( $plugin_slug );

			if ( null === $context ) {
				return $transient;
			}

			$config      = $context['config'];
			$slug        = $context['slug'];
			$plugin_data = $context['plugin_data'];

			$force_check  = empty( self::$api_update_called[ $plugin_slug ] ) ? ! empty( $_GET['force-check'] ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$release_info = self::get_release_info( $plugin_slug, $force_check );

			if ( ! $release_info || ! isset( $release_info->data ) || ! isset( $release_info->data->version ) ) {
				return $transient;
			}

			$do_update = version_compare( $release_info->data->version, $plugin_data['Version'] );

			if ( 1 === $do_update ) {
				$license_key_hash = self::resolve_license_key_hash( $config );

				$obj              = new \stdClass();
				$obj->slug        = $slug;
				$obj->new_version = $release_info->data->version;
				$obj->tested      = $release_info->data->tested;
				$obj->url         = $plugin_data['PluginURI'];
				$obj->package     = ! empty( $license_key_hash ) && isset( $release_info->data->package )
					? str_replace( '{license_key}', $license_key_hash, $release_info->data->package )
					: '';

				if ( ! empty( $release_info->data->icons ) ) {
					$obj->icons = array();
					foreach ( $release_info->data->icons as $key => $value ) {
						$obj->icons[ $key ] = $value;
					}
				}

				$transient->response[ $slug ] = $obj;
			}

			return $transient;
		}



		/**
		 * Filter callback for plugin information API.
		 *
		 * @param   mixed   $res     Plugin information response.
		 * @param   string  $action  API action.
		 * @param   object  $args    API arguments.
		 */
		public static function filter_set_plugin_info( $res, $action, $args ) {
			if ( 'plugin_information' !== $action ) {
				return $res;
			}

			foreach ( array_keys( self::$plugin_configs ) as $plugin_slug ) {
				$res = self::set_plugin_info_for_plugin( $res, $action, $args, $plugin_slug );
			}

			return $res;
		}



		/**
		 * Push in plugin version information to display in the details lightbox.
		 *
		 * @param   mixed   $res          Plugin information response.
		 * @param   string  $action       API action.
		 * @param   object  $args         API arguments.
		 * @param   string  $plugin_slug  Plugin slug.
		 */
		private static function set_plugin_info_for_plugin( $res, $action, $args, $plugin_slug ) {
			$context = self::get_plugin_context( $plugin_slug );

			if ( null === $context ) {
				return $res;
			}

			$slug        = $context['slug'];
			$plugin_data = $context['plugin_data'];

			if ( ! $plugin_data || ! is_array( $plugin_data ) ) {
				return $res;
			}

			$release_info = self::get_release_info( $plugin_slug );

			if ( ! $release_info || ! isset( $release_info->data ) || ! isset( $release_info->data->version ) ) {
				return $res;
			}

			if ( $args->slug == $slug ) {
				$res = new \stdClass();

				$res->slug = $slug;
				$res->name = $plugin_data['Name'];
				$res->author = $plugin_data['Author'];
				$res->homepage = $plugin_data['PluginURI'];

				foreach ( $release_info->data as $key => $value ) {
					if ( 'sections' == $key ) {
						continue;
					}

					$res->$key = $value;
				}

				if ( ! empty( $release_info->data->icons ) ) {
					$res->icons = array();
					foreach ( $release_info->data->icons as $key => $value ) {
						$res->icons[ $key ] = $value;
					}
				}

				if ( ! empty( $release_info->data->banners ) ) {
					$res->banners = array();
					foreach ( $release_info->data->banners as $key => $value ) {
						$res->banners[ $key ] = $value;
					}
				}

				if ( ! empty( $release_info->data->sections ) ) {
					$res->sections = array();
					foreach ( $release_info->data->sections as $key => $value ) {
						$res->sections[ $key ] = $value;
					}
				}

				$license_key_hash = self::resolve_license_key_hash( $context['config'] );
				if ( ! empty( $license_key_hash ) && ! empty( $res->package ) ) {
					$res->package = str_replace( '{license_key}', $license_key_hash, $res->package );
				}
			}

			return $res;
		}



		/**
		 * Filter callback for post-install actions.
		 *
		 * @param   bool   $true        Whether to proceed with install.
		 * @param   array  $hook_extra  Extra hook data.
		 * @param   array  $result      Install result.
		 */
		public static function filter_post_install( $true, $hook_extra, $result ) {
			foreach ( array_keys( self::$plugin_configs ) as $plugin_slug ) {
				$result = self::post_install_for_plugin( $true, $hook_extra, $result, $plugin_slug );
			}

			return $result;
		}



		/**
		 * Perform additional actions to successfully install our plugin.
		 *
		 * @param   bool    $true         Whether to proceed with install.
		 * @param   array   $hook_extra   Extra hook data.
		 * @param   array   $result       Install result.
		 * @param   string  $plugin_slug  Plugin slug.
		 */
		private static function post_install_for_plugin( $true, $hook_extra, $result, $plugin_slug ) {
			$context = self::get_plugin_context( $plugin_slug );

			if ( null === $context ) {
				return $result;
			}

			$slug = $context['slug'];

			if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $slug ) {
				return $result;
			}

			$was_activated = is_plugin_active( $slug );

			global $wp_filesystem;
			$plugin_folder = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . dirname( $slug );
			$wp_filesystem->move( $result['destination'], $plugin_folder );
			$result['destination'] = $plugin_folder;

			if ( $was_activated ) {
				activate_plugin( $slug );
			}

			return $result;
		}





		//
		// SITE REPORT FUNCTIONS
		//


		/**
		 * Whether site environment reporting is enabled for this site.
		 */
		public static function is_site_report_enabled() {
			return 'yes' === get_option( self::SITE_REPORT_ENABLE_OPTION, 'no' );
		}



		/**
		 * Whether site environment reporting is supported by this license client.
		 */
		public static function is_site_report_supported() {
			return method_exists( __CLASS__, 'schedule_site_report_cron' ) && method_exists( __CLASS__, 'maybe_send_site_report' );
		}



		/**
		 * Schedule the weekly site report cron event.
		 *
		 * @param string      $plugin_slug Plugin slug from the consuming plugin.
		 * @param string|null $cron_hook   Cron hook name from the consuming plugin.
		 */
		public static function schedule_site_report_cron( $plugin_slug, $cron_hook = null ) {
			if ( null === $cron_hook || '' === $cron_hook ) {
				$cron_hook = apply_filters( 'fc_licenses_site_report_cron_hook', '', $plugin_slug );
			}

			// Bail if cron hook is not defined by the consuming plugin
			if ( empty( $cron_hook ) ) { return; }

			// Bail if already scheduled
			if ( wp_next_scheduled( $cron_hook ) ) { return; }

			wp_schedule_event( time() + WEEK_IN_SECONDS, 'weekly', $cron_hook );
		}



		/**
		 * Schedule the weekly site report cron when reporting is enabled.
		 *
		 * @param string      $plugin_slug Plugin slug from the consuming plugin.
		 * @param string|null $cron_hook   Cron hook name from the consuming plugin.
		 */
		public static function maybe_schedule_site_report_cron( $plugin_slug, $cron_hook = null ) {
			// Bail if site report is not supported
			if ( ! self::is_site_report_supported() ) { return; }

			// Bail if site reporting is disabled
			if ( ! self::is_site_report_enabled() ) { return; }

			self::schedule_site_report_cron( $plugin_slug, $cron_hook );
		}



		/**
		 * Run the weekly site environment report cron job.
		 *
		 * @param string      $plugin_slug Plugin slug from the consuming plugin.
		 * @param string|null $api_url     Site report API base URL from the consuming plugin.
		 */
		public static function run_site_report_cron( $plugin_slug, $api_url = null ) {
			// Bail if site report is not supported
			if ( ! self::is_site_report_supported() ) { return; }

			self::maybe_send_site_report( $plugin_slug, $api_url );
		}



		/**
		 * Register init and cron hooks for weekly site environment reports.
		 *
		 * @param string      $plugin_slug Plugin slug from the consuming plugin.
		 * @param string      $cron_hook   Cron hook name from the consuming plugin.
		 * @param string|null $api_url     Site report API base URL from the consuming plugin.
		 */
		public static function register_site_report_cron_hooks( $plugin_slug, $cron_hook, $api_url = null ) {
			add_action(
				'init',
				function () use ( $plugin_slug, $cron_hook ) {
					self::maybe_schedule_site_report_cron( $plugin_slug, $cron_hook );
				}
			);

			add_action(
				$cron_hook,
				function () use ( $plugin_slug, $api_url ) {
					self::run_site_report_cron( $plugin_slug, $api_url );
				}
			);
		}



		/**
		 * Maybe send a consolidated site environment report to a plugin.
		 *
		 * @param string      $plugin_slug Plugin slug from the consuming plugin.
		 * @param string|null $api_url     Site report API base URL from the consuming plugin.
		 */
		public static function maybe_send_site_report( $plugin_slug, $api_url = null ) {
			// Bail if site reporting is disabled
			if ( ! self::is_site_report_enabled() ) { return; }

			self::send_site_report_now( null, false, false, $plugin_slug, $api_url );
		}



		/**
		 * Send a site environment report immediately.
		 *
		 * @param array|null  $groups             Optional data groups to include.
		 * @param bool        $enable_if_disabled Whether to enable scheduled reporting before sending.
		 * @param bool        $respect_send_rules Whether to apply fingerprint send intervals.
		 * @param string|null $plugin_slug        Plugin slug from the consuming plugin.
		 * @param string|null $api_url            Site report API base URL from the consuming plugin.
		 * @param string|null $cron_hook          Cron hook name from the consuming plugin.
		 */
		public static function send_site_report_now( $groups = null, $enable_if_disabled = false, $respect_send_rules = true, $plugin_slug = null, $api_url = null, $cron_hook = null ) {
			if ( get_transient( self::SITE_REPORT_SEND_LOCK_TRANSIENT ) ) {
				return array(
					'success'    => false,
					'error_code' => 'in_progress',
				);
			}

			if ( $enable_if_disabled && ! self::is_site_report_enabled() ) {
				update_option( self::SITE_REPORT_ENABLE_OPTION, 'yes' );
				self::schedule_site_report_cron( $plugin_slug, $cron_hook );
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

			$payload = self::build_site_report_payload( $groups, null, $api_url );

			if ( empty( $payload ) || empty( $payload['site_domain'] ) ) {
				delete_transient( self::SITE_REPORT_SEND_LOCK_TRANSIENT );

				self::log_site_report_error(
					'Site report payload is empty or missing site_domain.',
					array(
						'plugin_slug' => $plugin_slug,
					)
				);

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

			$response       = self::send_site_report( $payload, $api_url, $plugin_slug );
			$request_url    = untrailingslashit( self::get_remote_api_url( $api_url, $plugin_slug ) ) . '/wp-json/fc-licenses/v1/site-report';
			$response_code  = 0;

			if ( is_wp_error( $response ) ) {
				self::log_site_report_error(
					$response->get_error_message(),
					array(
						'plugin_slug' => $plugin_slug,
						'request_url' => $request_url,
						'error_code'  => $response->get_error_code(),
					)
				);
			} else {
				$response_code = (int) wp_remote_retrieve_response_code( $response );

				if ( 200 !== $response_code ) {
					$response_body = wp_remote_retrieve_body( $response );
					$log_message   = 'Site report request failed with HTTP ' . $response_code . '.';

					if ( ! empty( $response_body ) ) {
						$log_message .= ' Response: ' . $response_body;
					}

					self::log_site_report_error(
						$log_message,
						array(
							'plugin_slug'   => $plugin_slug,
							'request_url'   => $request_url,
							'response_code' => $response_code,
						)
					);
				}
			}

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
				return array(
					'success'       => false,
					'error_code'    => 'request_failed',
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
		 * Log a site report error to the WooCommerce logger.
		 *
		 * @param string $message Log message.
		 * @param array  $context Optional context data.
		 */
		private static function log_site_report_error( $message, $context = array() ) {
			if ( ! function_exists( 'wc_get_logger' ) ) { return; }

			$context['source'] = 'fc-site-report';

			wc_get_logger()->error( $message, $context );
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
		 * Get the plain site domain for telemetry payloads.
		 */
		private static function get_site_report_domain() {
			$parsed = wp_parse_url( home_url() );

			if ( empty( $parsed['host'] ) ) {
				return '';
			}

			$domain = strtolower( $parsed['host'] );

			if ( filter_var( $domain, FILTER_VALIDATE_IP ) ) {
				return '';
			}

			if ( substr_count( $domain, '.' ) < 1 ) {
				return '';
			}

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
					return '';
				}
			}

			if ( 'localhost' === $domain ) {
				return '';
			}

			return $domain;
		}



		/**
		 * Build the site environment report payload.
		 *
		 * @param array|null  $groups               Optional data groups to include. When omitted, uses saved settings and requires opt-in.
		 * @param string|null $plugins_report_scope Whether the plugins list is a full inventory or partial subset. Defaults to `complete`.
		 * @param string|null $api_url              Site report API base URL from the consuming plugin.
		 */
		public static function build_site_report_payload( $groups = null, $plugins_report_scope = null, $api_url = null ) {
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

			$site_domain = self::get_site_report_domain();

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
		 *
		 * @param string|null $api_url Site report API base URL from the consuming plugin.
		 */
		private static function build_basic_environment_payload( $api_url = null ) {
			$theme   = wp_get_theme();
			$plugins = array();

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

					self::maybe_add_own_plugin_license_status_row( $plugin_row, $plugin_slug, $api_url );
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
			$timezone_name = self::get_site_report_timezone()->getName();

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
		 * Build first-activation timestamps for registered own plugins.
		 *
		 * @param string|null $api_url Site report API base URL from the consuming plugin.
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
		 * @param array $payload Site report payload.
		 */
		public static function get_site_report_fingerprint( $payload ) {
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
		 * POST the site report payload to the plugin's licenses API.
		 *
		 * @param array       $payload     Site report payload.
		 * @param string|null $api_url     Site report API base URL from the consuming plugin.
		 * @param string|null $plugin_slug Plugin slug from the consuming plugin.
		 */
		public static function send_site_report( $payload, $api_url = null, $plugin_slug = null ) {
			$api_url = untrailingslashit( self::get_remote_api_url( $api_url, $plugin_slug ) );

			// Bail if API URL is not defined by the consuming plugin
			if ( empty( $api_url ) ) {
				return new WP_Error( 'fc_licenses_missing_remote_api_url', 'Remote API URL is not defined.' );
			}

			return wp_remote_post(
				$api_url . '/wp-json/fc-licenses/v1/site-report',
				array(
					'headers' => self::get_api_request_headers(
						array(
							'Content-Type' => 'application/json',
							'User-Agent'   => 'Fluid Licenses Site Report/' . self::get_site_report_user_agent_version( $api_url ),
						)
					),
					'body'    => wp_json_encode( $payload ),
					'timeout' => 15,
				)
			);
		}



		/**
		 * Get the remote plugin's licenses API base URL.
		 *
		 * Used by site reports, plugin updates, and license API calls.
		 *
		 * @param string|null $api_url     Remote API base URL from the consuming plugin.
		 * @param string|null $plugin_slug Plugin slug from the consuming plugin.
		 */
		public static function get_remote_api_url( $api_url = null, $plugin_slug = null ) {
			$api_url = apply_filters( 'fc_licenses_api_url', $api_url, $plugin_slug );

			// Bail if API URL is not defined
			if ( empty( $api_url ) ) { return ''; }

			return $api_url;
		}



		/**
		 * Get the plugin version for the site report User-Agent header.
		 *
		 * @param string|null $api_url Site report API base URL from the consuming plugin.
		 */
		private static function get_site_report_user_agent_version( $api_url = null ) {
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



		/**
		 * Get own plugins option map keyed by plugin folder slug.
		 *
		 * Empty by default. Each client plugin merges its entry via `fc_licenses_own_plugins`
		 * when loading the licenses client (activation time and optional license options).
		 *
		 * @param string|null $api_url Remote API base URL from the consuming plugin.
		 * @return array {
		 *     @type array $plugin_slug {
		 *         @type string $activation_time_option
		 *         @type string $license_key_option       Optional.
		 *         @type string $license_key_hash_option  Optional.
		 *         @type string $license_activated_option Optional.
		 *     }
		 * }
		 */
		private static function get_own_plugins_option_map( $api_url = null ) {
			$plugins = apply_filters( 'fc_licenses_own_plugins', array(), $api_url );

			if ( ! is_array( $plugins ) ) {
				return array();
			}

			return $plugins;
		}



		/**
		 * Attach license fields to an own plugin row when applicable.
		 *
		 * @param array       $plugin_row  Plugin payload row.
		 * @param string      $plugin_slug Plugin folder slug.
		 * @param string|null $api_url     Remote API base URL from the consuming plugin.
		 */
		private static function maybe_add_own_plugin_license_status_row( &$plugin_row, $plugin_slug, $api_url = null ) {
			$plugins_map = self::get_own_plugins_option_map( $api_url );

			if ( ! array_key_exists( $plugin_slug, $plugins_map ) ) {
				return;
			}

			$plugin_options = $plugins_map[ $plugin_slug ];

			// Bail if this own plugin has no license options (e.g. Lite)
			if ( empty( $plugin_options['license_key_option'] ) || empty( $plugin_options['license_key_hash_option'] ) || empty( $plugin_options['license_activated_option'] ) ) {
				return;
			}

			$license_key   = get_option( $plugin_options['license_key_option'], '' );
			$license_hash  = get_option( $plugin_options['license_key_hash_option'], '' );
			$activated_opt = get_option( $plugin_options['license_activated_option'], '' );

			$license_status = self::get_own_plugin_license_status(
				$plugin_slug,
				$license_key,
				$license_hash,
				$activated_opt
			);

			$plugin_row['license_status'] = $license_status;

			// Prefer stored hash; otherwise derive from plaintext key
			if ( ! empty( $license_hash ) && is_string( $license_hash ) ) {
				$plugin_row['license_key_hash'] = $license_hash;
			} elseif ( ! empty( $license_key ) ) {
				$plugin_row['license_key_hash'] = self::hash_license_key( $license_key );
			}
		}



		/**
		 * Resolve license status for an own plugin.
		 *
		 * Considers legacy activated option and/or live get_license_key_details via is_license_activated().
		 *
		 * @param string $plugin_slug            Plugin folder slug.
		 * @param string $license_key            License key option value.
		 * @param string $license_hash           License key hash option value.
		 * @param string $activated_option_value Activation option value.
		 */
		private static function get_own_plugin_license_status( $plugin_slug, $license_key, $license_hash, $activated_option_value ) {
			$key_present = ! empty( $license_key ) || ! empty( $license_hash );

			if ( ! $key_present ) {
				return 'missing';
			}

			if ( 'yes' === $activated_option_value ) {
				return 'valid';
			}

			if ( self::is_license_activated( $plugin_slug ) ) {
				return 'valid';
			}

			if ( in_array( $activated_option_value, array( 'no', '', null ), true ) && empty( $license_hash ) ) {
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
