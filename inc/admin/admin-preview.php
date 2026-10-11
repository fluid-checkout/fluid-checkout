<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin preview of the frontend pages, accessed with a signed preview token.
 */
class FluidCheckout_Admin_Preview extends FluidCheckout {

	/**
	 * Capability required to use the preview, same as the settings page.
	 */
	const CAPABILITY = 'manage_woocommerce';

	/**
	 * Query argument that carries the preview token.
	 */
	const TOKEN_QUERY_ARG = 'fc_preview';

	/**
	 * Preview token lifetime, in seconds.
	 */
	const TOKEN_LIFETIME = DAY_IN_SECONDS;

	/**
	 * Query argument that carries the preview mode.
	 */
	const MODE_QUERY_ARG = 'fc_preview_mode';

	/**
	 * Preview mode that renders pages for a guest.
	 */
	const MODE_GUEST = 'guest';

	/**
	 * Preview mode that renders pages for the dummy logged-in customer.
	 */
	const MODE_LOGGED_IN = 'logged_in';

	/**
	 * Maximum number of line items in the preview cart.
	 */
	const CART_ITEMS_LIMIT = 3;

	/**
	 * Query argument that carries the shipping methods chosen in the preview, which the frame script sends with each request.
	 */
	const SHIPPING_METHODS_QUERY_ARG = 'fc_preview_shipping_methods';

	/**
	 * Admin AJAX action that saves the unsaved settings shown in the preview.
	 */
	const DRAFT_AJAX_ACTION = 'fc_admin_preview_save_draft';

	/**
	 * Prefix of the transients that keep the unsaved settings of each preview token.
	 */
	const DRAFT_TRANSIENT_PREFIX = 'fc_admin_preview_draft_';

	/**
	 * Whether the current request is a preview request, cached after first check.
	 *
	 * @var bool|null
	 */
	private $is_preview_request = null;

	/**
	 * Preview tokens created in the current request, by user ID.
	 *
	 * @var array
	 */
	private $tokens = array();

	/**
	 * Unsaved settings values of the current preview request, cached after first read.
	 *
	 * @var array|null
	 */
	private $draft_values = null;



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
		// Preview requests
		add_action( 'init', array( $this, 'preview_request_hooks' ), 100 ); // Late so the hooks removed from other plugins are already registered

		// Expired previews
		add_action( 'init', array( $this, 'maybe_reject_expired_preview_request' ), -100 ); // Before WooCommerce sets up the session and customer at priority `0`

		// Unsaved settings
		add_action( 'wp_ajax_' . self::DRAFT_AJAX_ACTION, array( $this, 'ajax_save_draft' ), 10 );

		// Preview guards
		$this->preview_guard_hooks();

		// Preview optimizations
		$this->preview_optimization_hooks();

		// Preview session
		$this->preview_session_hooks();

		// Preview cart
		$this->preview_cart_hooks();

		// Preview orders
		$this->preview_order_hooks();

		// Preview settings
		$this->preview_settings_hooks();
	}

	/**
	 * Add or remove preview request hooks.
	 */
	public function preview_request_hooks() {
		// Bail if not a preview request
		if ( ! $this->is_preview_request() ) { return; }

		// Response headers
		add_filter( 'wp_headers', array( $this, 'add_preview_headers' ), 100 ); // Late to override values set by other plugins
		add_action( 'template_redirect', array( $this, 'replace_security_headers' ), PHP_INT_MAX ); // Last before the page is output, as other plugins also send these headers with `header()`
		add_filter( 'wp_robots', 'wp_robots_no_robots', 10 );

		// Admin bar
		add_filter( 'show_admin_bar', '__return_false', 10 );

		// Empty cart
		add_filter( 'woocommerce_checkout_redirect_empty_cart', '__return_false', 10 );
		add_filter( 'woocommerce_checkout_update_order_review_expired', '__return_false', 10 );

		// Coming soon mode
		add_filter( 'woocommerce_coming_soon_exclude', '__return_true', 10 );

		// URLs that stay inside the preview
		add_filter( 'woocommerce_ajax_get_endpoint', array( $this, 'add_preview_args_to_url' ), 10 );
		add_filter( 'woocommerce_get_checkout_url', array( $this, 'add_preview_args_to_url' ), 10 ); // Required as the checkout form posts to this URL without JavaScript
		add_filter( 'woocommerce_get_cart_url', array( $this, 'add_preview_args_to_url' ), 10 );
		add_filter( 'wp_redirect', array( $this, 'maybe_add_preview_args_to_redirect' ), 10 );

		// Body classes
		add_filter( 'body_class', array( $this, 'add_body_class' ), 10 );

		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_theme_compat_styles' ), 10 );

		// Orders and payments
		remove_action( 'wp', array( 'WC_Form_Handler', 'pay_action' ), 20 );
		remove_action( 'wp_loaded', array( 'WC_Form_Handler', 'cancel_order' ), 20 );
		remove_action( 'wp', array( 'WC_Form_Handler', 'add_payment_method_action' ), 20 );
		remove_action( 'wp', array( 'WC_Form_Handler', 'delete_payment_method_action' ), 20 );
		remove_action( 'wp', array( 'WC_Form_Handler', 'set_default_payment_method_action' ), 20 );

		// Accounts
		remove_action( 'wp_loaded', array( 'WC_Form_Handler', 'process_lost_password' ), 20 );
		remove_action( 'wp_loaded', array( 'WC_Form_Handler', 'process_reset_password' ), 20 );
		remove_action( 'template_redirect', array( 'WC_Form_Handler', 'redirect_reset_password_link' ), 10 );
		remove_action( 'template_redirect', array( 'WC_Form_Handler', 'resend_set_password' ), 10 );
		remove_action( 'template_redirect', array( 'WC_Form_Handler', 'save_address' ), 10 );
		remove_action( 'template_redirect', array( 'WC_Form_Handler', 'save_account_details' ), 10 );
	}

	/**
	 * Add or remove hooks that stop preview requests from placing orders, logging in, adding to cart, sending emails, saving data and contacting other sites.
	 * Registered when the class loads, so they also apply to requests handled before `init` finishes.
	 */
	public function preview_guard_hooks() {
		// Bail if not a preview request
		if ( ! $this->is_preview_request() ) { return; }

		// Orders and payments
		add_action( 'woocommerce_before_checkout_process', array( $this, 'prevent_checkout_processing' ), 10 );
		add_action( 'woocommerce_before_order_object_save', array( $this, 'maybe_prevent_new_order_save' ), 10 ); // Last resort for orders saved outside checkout processing, such as by express payment endpoints
		add_filter( 'rest_pre_dispatch', array( $this, 'maybe_prevent_store_api_checkout' ), 10, 3 );

		// Logins and accounts
		add_filter( 'woocommerce_process_login_errors', array( $this, 'add_login_error' ), 10 ); // Stops login forms before authentication, so attempts do not count as failed logins
		add_filter( 'authenticate', array( $this, 'prevent_login' ), PHP_INT_MAX ); // Last resort for other login forms, last to override users authenticated by other filters
		add_filter( 'woocommerce_registration_errors', array( $this, 'add_registration_error' ), 10 );
		add_filter( 'send_auth_cookies', '__return_false', 10 ); // Required to keep the visitor's own login cookies, as preview pages share the domain

		// Add to cart
		add_filter( 'woocommerce_add_to_cart_validation', '__return_false', PHP_INT_MAX ); // Last, to refuse add-to-cart forms and AJAX, so add-to-cart events do not reach analytics and marketing plugins
		add_filter( 'woocommerce_cart_redirect_after_error', array( $this, 'get_add_to_cart_error_redirect_url' ), 10 ); // Required to keep the frame in the preview, as scripts open this URL when adding to cart fails

		// Emails
		add_filter( 'pre_wp_mail', '__return_false', 10 ); // Required as preview requests must not send emails

		// Database writes
		add_filter( 'query', array( $this, 'maybe_skip_database_write' ), PHP_INT_MAX ); // Last, to check queries after other filters change them

		// Scheduled actions
		add_filter( 'pre_as_enqueue_async_action', '__return_zero', 10 ); // Required as Action Scheduler fails with an error when it cannot save scheduled actions
		add_filter( 'pre_as_schedule_single_action', '__return_zero', 10 );
		add_filter( 'pre_as_schedule_recurring_action', '__return_zero', 10 );
		add_filter( 'pre_as_schedule_cron_action', '__return_zero', 10 );

		// Requests to other sites
		add_filter( 'pre_http_request', array( $this, 'maybe_block_request_to_other_site' ), PHP_INT_MAX, 3 ); // Last, as other filters may answer requests without sending them
	}

	/**
	 * Add or remove preview optimization hooks, which leave the scripts and styles of preview pages as they are.
	 */
	public function preview_optimization_hooks() {
		// Bail if not a preview request
		if ( ! $this->is_preview_request() ) { return; }

		// Page optimizations
		add_action( 'after_setup_theme', array( $this, 'define_no_optimization_constants' ), 15 ); // Before optimization plugins read the constants at `init`
		add_filter( 'perfmatters_delay_js', '__return_false', 10 ); // Perfmatters has no constant
		add_filter( 'perfmatters_defer_js', '__return_false', 10 );
		add_filter( 'wpo_minify_run_on_page', '__return_false', 10 ); // WP-Optimize has no constant
	}

	/**
	 * Add or remove preview session hooks.
	 */
	public function preview_session_hooks() {
		// Bail if not a preview request
		if ( ! $this->is_preview_request() ) { return; }

		// Preview customer
		add_action( 'after_setup_theme', array( $this, 'switch_to_preview_customer' ), 15 ); // Before Fluid Checkout add-ons load at priority `20`, as some check whether the visitor is logged in while loading

		// Session
		add_filter( 'woocommerce_session_handler', array( $this, 'get_session_handler_class' ), 100 ); // Late to override session handlers from other plugins
		add_filter( 'woocommerce_persistent_cart_enabled', '__return_false', 10 );

		// Cookies
		add_filter( 'woocommerce_set_cookie_enabled', '__return_false', 10 ); // Required to keep the visitor's own session cookies unchanged when the preview is on the same domain

		// Dummy customer meta
		add_filter( 'get_user_metadata', array( $this, 'maybe_get_dummy_customer_meta' ), 10, 4 );
		add_filter( 'add_user_metadata', array( $this, 'maybe_skip_dummy_customer_meta_write' ), 10, 2 );
		add_filter( 'update_user_metadata', array( $this, 'maybe_skip_dummy_customer_meta_write' ), 10, 2 );
		add_filter( 'delete_user_metadata', array( $this, 'maybe_skip_dummy_customer_meta_write' ), 10, 2 );

		// Dummy customer fields
		add_action( 'wp_loaded', array( $this, 'maybe_set_dummy_guest_customer_fields' ), 5 ); // Before WooCommerce loads the cart and calculates shipping at priority `10`
		add_action( 'wp_loaded', array( $this, 'set_dummy_new_address_session_values' ), 5 );
		add_action( 'wp_loaded', array( $this, 'set_dummy_order_fields_session_values' ), 5 );
		add_filter( 'woocommerce_checkout_get_value', array( $this, 'maybe_get_dummy_checkout_field_value' ), PHP_INT_MAX, 2 ); // Last, to only fill fields that other filters leave empty, such as with session values
	}

	/**
	 * Add or remove preview cart hooks.
	 */
	public function preview_cart_hooks() {
		// Bail if not a preview request
		if ( ! $this->is_preview_request() ) { return; }

		// Dummy products
		add_filter( 'woocommerce_product_type_query', array( $this, 'maybe_get_dummy_product_type' ), PHP_INT_MAX, 2 ); // Last, to override types set by other plugins
		add_filter( 'woocommerce_product_class', array( $this, 'maybe_get_dummy_product_class' ), PHP_INT_MAX, 4 ); // Last, to override classes set by other plugins, some of which also use `PHP_INT_MAX`

		// Cart items
		add_action( 'woocommerce_load_cart_from_session', array( $this, 'maybe_set_preview_cart_items' ), 10 );

		// Shipping methods
		add_action( 'woocommerce_load_cart_from_session', array( $this, 'maybe_set_preview_shipping_methods' ), 10 );
	}

	/**
	 * Add or remove preview order hooks.
	 */
	public function preview_order_hooks() {
		// Bail if not a preview request
		if ( ! $this->is_preview_request() ) { return; }

		// Dummy orders
		add_action( 'woocommerce_init', array( $this, 'use_preview_order_factory' ), 10 ); // After WooCommerce creates its order factory

		// Dummy order meta
		add_filter( 'add_post_metadata', array( $this, 'maybe_skip_dummy_order_meta_write' ), 10, 2 );
		add_filter( 'update_post_metadata', array( $this, 'maybe_skip_dummy_order_meta_write' ), 10, 2 );
		add_filter( 'delete_post_metadata', array( $this, 'maybe_skip_dummy_order_meta_write' ), 10, 2 );
	}

	/**
	 * Add or remove preview settings hooks.
	 */
	public function preview_settings_hooks() {
		// Bail if not a preview request
		if ( ! $this->is_preview_request() ) { return; }

		// Unsaved settings
		add_filter( 'fc_settings_effective_values', array( $this, 'add_draft_values' ), 10 );

		// Settings profiles
		add_filter( 'fc_settings_context_profile', '__return_null', 100 ); // Late, to show the settings being edited instead of the settings profiles of experiments
	}



	/**
	 * Get the preview token of the current user, created once per request so all preview URLs and the unsaved settings share it.
	 * Self-contained so it also works when the frontend is on a different domain from the admin.
	 *
	 * @return  string  The preview token, or an empty string when the current user cannot use the preview.
	 */
	public function get_token() {
		// Bail if the current user cannot use the preview
		if ( ! current_user_can( self::CAPABILITY ) ) { return ''; }

		// Get current user
		$user = wp_get_current_user();

		// Maybe create the token of the current user
		if ( ! isset( $this->tokens[ $user->ID ] ) ) {
			$payload = $user->ID . '.' . ( time() + self::TOKEN_LIFETIME );
			$this->tokens[ $user->ID ] = $payload . '.' . $this->get_token_signature( $payload, $user );
		}

		return $this->tokens[ $user->ID ];
	}

	/**
	 * Get the preview URL of a frontend page for the current user, which opens the page as a preview request.
	 *
	 * @param   string  $url  Frontend page URL.
	 *
	 * @return  string  The preview URL, or an empty string when the current user cannot use the preview.
	 */
	public function get_preview_url( $url ) {
		// Get preview token for the current user
		$token = $this->get_token();

		// Bail if the current user cannot use the preview
		if ( '' === $token ) { return ''; }

		return add_query_arg( self::TOKEN_QUERY_ARG, $token, $url );
	}

	/**
	 * Get the signature of a preview token payload.
	 *
	 * @param   string   $payload  Token payload, as `{user_id}.{expiration}`.
	 * @param   WP_User  $user     Token user.
	 *
	 * @return  string  The token signature.
	 */
	public function get_token_signature( $payload, $user ) {
		// Include a password fragment, like WordPress auth cookies, so changing the password invalidates the token
		return hash_hmac( 'sha256', 'fc_preview|' . $payload . '|' . substr( $user->user_pass, -4 ), wp_salt() );
	}

	/**
	 * Get the user ID of a valid preview token.
	 *
	 * @param   string  $token  Preview token.
	 *
	 * @return  int  The token user ID, or `0` when the token is not valid.
	 */
	public function get_token_user_id( $token ) {
		// Bail if token format is not valid
		if ( ! is_string( $token ) || ! preg_match( '/^(?<payload>(?<user_id>\d+)\.(?<expiration>\d+))\.(?<signature>[a-f0-9]{64})$/D', $token, $token_parts ) ) { return 0; }

		// Bail if token has expired
		if ( (int) $token_parts[ 'expiration' ] < time() ) { return 0; }

		// Get token user
		$user = get_userdata( (int) $token_parts[ 'user_id' ] );

		// Bail if user does not exist
		if ( ! $user ) { return 0; }

		// Bail if signature does not match
		if ( ! hash_equals( $this->get_token_signature( $token_parts[ 'payload' ], $user ), $token_parts[ 'signature' ] ) ) { return 0; }

		// Bail if user cannot use the preview anymore
		if ( ! user_can( $user, self::CAPABILITY ) ) { return 0; }

		return $user->ID;
	}



	/**
	 * Get the preview token from the current request.
	 *
	 * @return  string  The preview token, or an empty string when the request has none.
	 */
	public function get_request_token() {
		// Bail if request has no preview token
		if ( ! isset( $_GET[ self::TOKEN_QUERY_ARG ] ) ) { return ''; }

		return sanitize_text_field( wp_unslash( $_GET[ self::TOKEN_QUERY_ARG ] ) );
	}

	/**
	 * Check whether the current request carries a valid preview token.
	 *
	 * @return  bool
	 */
	public function is_preview_request() {
		// Maybe validate the request token, only once per request, ignoring admin page loads but not AJAX requests sent from preview pages
		if ( null === $this->is_preview_request ) {
			$this->is_preview_request = ( ! is_admin() || wp_doing_ajax() ) && 0 < $this->get_token_user_id( $this->get_request_token() );
		}

		return $this->is_preview_request;
	}

	/**
	 * Get the preview mode from the current request.
	 *
	 * @return  string  `logged_in` for the dummy logged-in customer, or `guest` otherwise.
	 */
	public function get_preview_mode() {
		// Get requested mode
		$mode = isset( $_GET[ self::MODE_QUERY_ARG ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::MODE_QUERY_ARG ] ) ) : '';

		return self::MODE_LOGGED_IN === $mode ? self::MODE_LOGGED_IN : self::MODE_GUEST;
	}

	/**
	 * Get the query arguments that make requests preview requests: the preview token, and the mode when logged in.
	 *
	 * @return  array  Query argument values by argument name.
	 */
	public function get_preview_args() {
		// Define preview token argument
		$preview_args = array( self::TOKEN_QUERY_ARG => $this->get_request_token() );

		// Maybe add the logged-in mode
		if ( self::MODE_LOGGED_IN === $this->get_preview_mode() ) {
			$preview_args[ self::MODE_QUERY_ARG ] = self::MODE_LOGGED_IN;
		}

		return $preview_args;
	}



	/**
	 * Get the admin origin, as `{scheme}://{host}` with the port when present.
	 *
	 * @return  string
	 */
	public function get_admin_origin() {
		// Get admin URL parts
		$admin_url_parts = wp_parse_url( admin_url() );

		// Bail if admin URL has no origin
		if ( empty( $admin_url_parts[ 'scheme' ] ) || empty( $admin_url_parts[ 'host' ] ) ) { return ''; }

		// Get admin origin
		$admin_origin = $admin_url_parts[ 'scheme' ] . '://' . $admin_url_parts[ 'host' ];

		// Maybe add the port
		if ( array_key_exists( 'port', $admin_url_parts ) ) {
			$admin_origin .= ':' . $admin_url_parts[ 'port' ];
		}

		return $admin_origin;
	}

	/**
	 * Get the hosts of other sites that preview pages and preview requests may contact.
	 *
	 * @return  string[]  Host names, where a leading `*.` matches any subdomain.
	 */
	public function get_allowed_hosts() {
		/**
		 * Filter the hosts of other sites that preview pages and preview requests may contact, where a leading `*.` matches any subdomain.
		 */
		$hosts = (array) apply_filters( 'fc_admin_preview_allowed_hosts', array() );

		// Keep valid host names only, as they are added to the content security policy of preview pages
		return array_values( array_filter( $hosts, function( $host ) {
			return is_string( $host ) && 1 === preg_match( '/^(\*\.)?[a-z0-9-]+(\.[a-z0-9-]+)+$/i', $host );
		} ) );
	}



	/**
	 * Add the response headers for preview requests.
	 *
	 * @param   array  $headers  Response headers.
	 */
	public function add_preview_headers( $headers ) {
		// Prevent caching, as preview pages must never be served to visitors
		WC_Cache_Helper::set_nocache_constants();
		$headers = array_merge( $headers, wp_get_nocache_headers() );

		// Keep preview pages out of search engines, and the token out of referrers sent to other domains
		$headers[ 'X-Robots-Tag' ] = 'noindex, nofollow, noarchive';
		$headers[ 'Referrer-Policy' ] = 'same-origin';

		return $headers;
	}

	/**
	 * Replace the frame options and content security policies sent for preview pages with one policy, keeping the directives of other policies.
	 */
	public function replace_security_headers() {
		// Bail if the headers are already sent
		if ( headers_sent() ) { return; }

		// Define directives of the content security policies sent so far
		$policy_directives = array();

		// Iterate headers sent so far
		foreach ( headers_list() as $header ) {
			// Get header name and value
			$header_parts = array_map( 'trim', explode( ':', $header, 2 ) );

			// Skip headers other than content security policies
			if ( 2 !== count( $header_parts ) || 0 !== strcasecmp( $header_parts[ 0 ], 'Content-Security-Policy' ) ) { continue; }

			// Iterate directives of the policy
			foreach ( array_map( 'trim', explode( ';', $header_parts[ 1 ] ) ) as $directive ) {
				// Skip empty directives, and the frame ancestors and connect sources which preview pages set
				if ( '' === $directive || 0 === stripos( $directive, 'frame-ancestors' ) || 0 === stripos( $directive, 'connect-src' ) ) { continue; }

				$policy_directives[] = $directive;
			}
		}

		// Only allow scripts to send requests to this site and allowed hosts, so tracking scripts send nothing from preview pages
		$policy_directives[] = trim( "connect-src 'self' " . implode( ' ', $this->get_allowed_hosts() ) );

		// Only allow the frontend and the admin to load preview pages in a frame, replacing frame options sent by the site and other plugins
		$policy_directives[] = "frame-ancestors 'self' " . $this->get_admin_origin();
		header_remove( 'X-Frame-Options' );

		// Send the policy, replacing the policies sent so far
		header( 'Content-Security-Policy: ' . implode( '; ', $policy_directives ) );
	}

	/**
	 * Add the preview token and mode to a URL, so requests to it are also preview requests.
	 *
	 * @param   string  $url  URL, such as the WooCommerce AJAX endpoint URL.
	 */
	public function add_preview_args_to_url( $url ) {
		// Get URL query arguments
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query_args );

		// Bail if the URL already has the preview token
		if ( array_key_exists( self::TOKEN_QUERY_ARG, $query_args ) ) { return $url; }

		// Split off the fragment, so the arguments go into the query string
		$url_parts = explode( '#', $url, 2 );

		// Get query string separator
		$separator = false === strpos( $url_parts[ 0 ], '?' ) ? '?' : '&';

		// Append the arguments directly, as `add_query_arg()` would encode the `%%endpoint%%` placeholder replaced by scripts
		$preview_query = $separator . http_build_query( $this->get_preview_args(), '', '&', PHP_QUERY_RFC3986 );

		return $url_parts[ 0 ] . $preview_query . ( isset( $url_parts[ 1 ] ) ? '#' . $url_parts[ 1 ] : '' );
	}

	/**
	 * Add the preview token and mode to redirects within the frontend, so the frame stays in the preview, like the WordPress Customizer does.
	 *
	 * @param   string  $location  Redirect URL.
	 */
	public function maybe_add_preview_args_to_redirect( $location ) {
		// Bail if redirecting outside the frontend: to another site, the admin or the login page
		if ( 0 !== strpos( $location, home_url( '/' ) ) || 0 === strpos( $location, admin_url() ) || 0 === strpos( $location, wp_login_url() ) ) { return $location; }

		return $this->add_preview_args_to_url( $location );
	}



	/**
	 * Add the preview body classes, with the header and footer hidden until the admin page shows them.
	 *
	 * @param   array  $classes  Body classes.
	 */
	public function add_body_class( $classes ) {
		return array_merge( $classes, array( 'fc-admin-preview', 'fc-admin-preview--hide-header-footer' ) );
	}

	/**
	 * Register the frame script and styles.
	 */
	public function register_assets() {
		// Scripts, initialized right away instead of on `load`, so the frame guards apply before other scripts handle clicks or send requests
		// Settings are passed directly, as the plugin settings object is not output on every page the preview shows
		wp_register_script( 'fc-admin-preview-frame', FluidCheckout_Enqueue::instance()->get_script_url( 'js/admin-preview-frame' ), array( 'fc-utils' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-admin-preview-frame', 'FCAdminPreviewFrame.init(' . wp_json_encode( $this->get_frame_script_settings() ) . ');' );

		// Add the preview token and mode to REST requests sent through `wp.apiFetch`, also before the deferred frame script runs, such as Store API requests from blocks
		// Uses a middleware, as filtering the REST URL would break how `wp.apiFetch` appends request paths to it
		wp_add_inline_script( 'wp-api-fetch', 'wp.apiFetch.use(function(options,next){return next(options.path?Object.assign({},options,{path:wp.url.addQueryArgs(options.path,' . wp_json_encode( $this->get_preview_args() ) . ')}):options);});' );

		// Styles
		wp_register_style( 'fc-admin-preview-frame', FluidCheckout_Enqueue::instance()->get_style_url( 'css/admin-preview-frame' ), array(), NULL );
	}

	/**
	 * Enqueue the frame script and styles.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-admin-preview-frame' );

		// Styles
		wp_enqueue_style( 'fc-admin-preview-frame' );
	}

	/**
	 * Enqueue themes compatibility styles for preview pages.
	 */
	public function enqueue_theme_compat_styles() {
		// Get currently active theme and child theme
		$theme_slugs = array( get_template(), get_stylesheet() );

		// Iterate theme slugs
		foreach ( $theme_slugs as $theme_slug ) {
			// Maybe skip compat file
			if ( apply_filters( 'fc_enable_compat_theme_admin_preview_style_' . $theme_slug, true ) === false ) { continue; }

			// Get file path
			$compat_file_path = FluidCheckout_Enqueue::instance()->get_style_url( 'css/compat/themes/compat-admin-preview-' . $theme_slug, false );

			// Maybe load compatibility file
			if ( file_exists( self::$directory_path . $compat_file_path ) ) {
				wp_enqueue_style( 'fc-theme-compat-admin-preview-' . $theme_slug, self::$directory_url . $compat_file_path, array( 'fc-admin-preview-frame' ), null );
			}
		}
	}

	/**
	 * Get the frame script settings.
	 *
	 * @return  array
	 */
	public function get_frame_script_settings() {
		/**
		 * Filter the settings of the admin preview frame script.
		 */
		return apply_filters( 'fc_admin_preview_frame_script_settings', array(
			'adminOrigin'                 => $this->get_admin_origin(),
			'tokenQueryArg'               => self::TOKEN_QUERY_ARG,
			'previewArgs'                 => $this->get_preview_args(),
			'shippingMethodsQueryArg'     => self::SHIPPING_METHODS_QUERY_ARG,
			'darkModeCssVariables'        => FluidCheckout_DesignTemplates::instance()->get_css_variables_dark_mode(),
		) );
	}



	/**
	 * Reject requests with a preview token that is no longer valid, such as from a preview left open after its token expired.
	 * Without this, the open preview would continue with the visitor's own session and account.
	 */
	public function maybe_reject_expired_preview_request() {
		// Bail if the request has no preview token
		if ( '' === $this->get_request_token() ) { return; }

		// Bail if the preview token is valid
		if ( $this->is_preview_request() ) { return; }

		// Bail if loading an admin page, which ignores preview tokens
		if ( is_admin() && ! wp_doing_ajax() ) { return; }

		wp_die( esc_html__( 'This preview has expired. Reload the admin page to start a new preview.', 'fluid-checkout' ), esc_html__( 'Preview expired', 'fluid-checkout' ), array( 'response' => 403 ) );
	}

	/**
	 * Get the message shown when an order is placed in the preview.
	 *
	 * @return  string
	 */
	public function get_orders_unavailable_message() {
		return __( 'Orders cannot be placed in the preview.', 'fluid-checkout' );
	}

	/**
	 * Get the message shown when logging in from the preview.
	 *
	 * @return  string
	 */
	public function get_login_unavailable_message() {
		return __( 'Logging in is not available in the preview.', 'fluid-checkout' );
	}

	/**
	 * Stop checkout processing, which WooCommerce shows as an error notice.
	 *
	 * @throws  Exception  Always, as orders cannot be placed from the preview.
	 */
	public function prevent_checkout_processing() {
		throw new Exception( $this->get_orders_unavailable_message() );
	}

	/**
	 * Stop saving new orders, which WooCommerce catches and logs before the order is created.
	 *
	 * @param   WC_Abstract_Order  $order  Order being saved.
	 *
	 * @throws  Exception  When the order is new, as preview requests must not create orders.
	 */
	public function maybe_prevent_new_order_save( $order ) {
		// Bail if the order already exists, as WooCommerce would add an error note to it when stopping the save
		if ( $order instanceof WC_Abstract_Order && $order->get_id() ) { return; }

		throw new Exception( $this->get_orders_unavailable_message() );
	}

	/**
	 * Reject Store API checkout requests, which create and place orders.
	 *
	 * @param   mixed            $result   Response to use instead of dispatching the request, `null` to dispatch it.
	 * @param   WP_REST_Server   $server   REST server.
	 * @param   WP_REST_Request  $request  REST request.
	 */
	public function maybe_prevent_store_api_checkout( $result, $server, $request ) {
		// Bail if not a Store API checkout request, matched without case like WordPress matches routes
		if ( ! preg_match( '#^/wc/store(/v\d+)?/checkout#i', $request->get_route() ) ) { return $result; }

		return new WP_Error( 'fc_admin_preview_checkout', $this->get_orders_unavailable_message(), array( 'status' => 403 ) );
	}

	/**
	 * Add an error to login form submissions, so WooCommerce and Fluid Checkout login forms stop before authentication.
	 *
	 * @param   WP_Error  $errors  Login errors.
	 */
	public function add_login_error( $errors ) {
		$errors->add( 'fc_admin_preview_login', $this->get_login_unavailable_message() );

		return $errors;
	}

	/**
	 * Reject logins from other login forms, as logging in from the preview would change the visitor's own session.
	 *
	 * @param   WP_User|WP_Error|null  $user  Authenticated user, error, or `null` when not authenticated yet.
	 */
	public function prevent_login( $user ) {
		return new WP_Error( 'fc_admin_preview_login', $this->get_login_unavailable_message() );
	}

	/**
	 * Add an error to account registrations, so WooCommerce does not create customers from the preview.
	 *
	 * @param   WP_Error  $errors  Registration errors.
	 */
	public function add_registration_error( $errors ) {
		$errors->add( 'fc_admin_preview_registration', __( 'Accounts cannot be created in the preview.', 'fluid-checkout' ) );

		return $errors;
	}

	/**
	 * Get the URL that scripts open when adding to cart fails: the preview page that sent the request.
	 *
	 * @param   string  $url  URL to open, the product page by default.
	 */
	public function get_add_to_cart_error_redirect_url( $url ) {
		// Get the page that sent the request, which carries the preview token
		$referer = wp_get_referer();

		return false !== $referer ? $referer : wc_get_cart_url();
	}

	/**
	 * Skip queries that write to the database, so plugins save nothing from preview requests.
	 *
	 * @param   string  $query  Database query.
	 */
	public function maybe_skip_database_write( $query ) {
		// Bail if the query does not write to the database
		if ( 1 !== preg_match( '/^\s*(INSERT|UPDATE|DELETE|REPLACE|ALTER|DROP|TRUNCATE|RENAME|CREATE(?!\s+TEMPORARY))\s/i', $query ) ) { return $query; }

		// Return an empty query, which WordPress skips
		return '';
	}

	/**
	 * Block server requests to other sites, so plugins send nothing from preview requests.
	 *
	 * @param   false|array|WP_Error  $preempt  Response to return instead of sending the request, or `false` to send it.
	 * @param   array                 $args     Request arguments.
	 * @param   string                $url      Request URL.
	 */
	public function maybe_block_request_to_other_site( $preempt, $args, $url ) {
		// Bail if another filter already answers the request
		if ( false !== $preempt ) { return $preempt; }

		// Get request host
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );

		// Bail if the request is sent to this site, such as background task requests
		if ( in_array( $host, array( wp_parse_url( home_url(), PHP_URL_HOST ), wp_parse_url( site_url(), PHP_URL_HOST ) ), true ) ) { return $preempt; }

		// Iterate hosts the preview may contact
		foreach ( $this->get_allowed_hosts() as $allowed_host ) {
			// Bail if the request is sent to an allowed host, where `*.` matches any subdomain
			if ( 1 === preg_match( '/^' . str_replace( '\*', '.+', preg_quote( $allowed_host, '/' ) ) . '$/i', $host ) ) { return $preempt; }
		}

		return new WP_Error( 'fc_admin_preview_request_blocked', __( 'Requests to other sites are blocked in the preview.', 'fluid-checkout' ) );
	}



	/**
	 * Define the constants that optimization plugins check to leave a page as it is.
	 * Delayed scripts would run the frame script after other scripts, and combined styles would hide the custom styles that the preview refreshes.
	 */
	public function define_no_optimization_constants() {
		// Iterate constants of Autoptimize and others, WP Rocket and LiteSpeed Cache
		foreach ( array( 'DONOTMINIFY', 'DONOTROCKETOPTIMIZE', 'LITESPEED_NO_OPTM' ) as $constant ) {
			// Skip constants already defined
			if ( defined( $constant ) ) { continue; }

			define( $constant, true );
		}
	}



	/**
	 * Switch the current user to the preview customer: the dummy customer in logged-in mode, or a guest.
	 */
	public function switch_to_preview_customer() {
		// Maybe use the dummy customer in logged-in mode
		if ( self::MODE_LOGGED_IN === $this->get_preview_mode() ) {
			// Cache the dummy user in memory only, so WordPress and WooCommerce load it like a real user
			wp_cache_add_non_persistent_groups( array( 'users' ) );
			wp_cache_set( FluidCheckout_Admin_Preview_Dummy_Data::CUSTOMER_ID, FluidCheckout_Admin_Preview_Dummy_Data::instance()->get_customer_user_data(), 'users' );

			wp_set_current_user( FluidCheckout_Admin_Preview_Dummy_Data::CUSTOMER_ID );
		}
		// Otherwise, use a guest
		else {
			wp_set_current_user( 0 );
		}
	}

	/**
	 * Get the WooCommerce session customer ID for the preview request.
	 *
	 * @return  string  The dummy customer user ID in logged-in mode, or a guest ID.
	 */
	public function get_preview_customer_id() {
		// Return the dummy customer ID in logged-in mode
		if ( self::MODE_LOGGED_IN === $this->get_preview_mode() ) {
			return (string) FluidCheckout_Admin_Preview_Dummy_Data::CUSTOMER_ID;
		}

		// Derive the guest ID from the token, so it stays the same across requests and nonces keep working
		return 't_' . substr( md5( $this->get_request_token() ), 0, 30 );
	}

	/**
	 * Get the WooCommerce session handler class for preview requests.
	 *
	 * @param   string  $class_name  Session handler class name.
	 */
	public function get_session_handler_class( $class_name ) {
		return 'FluidCheckout_Admin_Preview_Session_Handler';
	}



	/**
	 * Get the meta values of the dummy logged-in customer without reading the database.
	 *
	 * @param   mixed   $value     Meta value, `null` to read it from the database.
	 * @param   int     $user_id   User ID.
	 * @param   string  $meta_key  Meta key, or an empty string for all meta values.
	 * @param   bool    $single    Whether a single value is requested.
	 */
	public function maybe_get_dummy_customer_meta( $value, $user_id, $meta_key, $single ) {
		// Bail if not the dummy customer
		if ( FluidCheckout_Admin_Preview_Dummy_Data::CUSTOMER_ID !== (int) $user_id ) { return $value; }

		// Get dummy customer meta
		$dummy_meta = FluidCheckout_Admin_Preview_Dummy_Data::instance()->get_customer_meta();

		// Maybe return all meta values, each wrapped in an array like the database results
		if ( '' === $meta_key ) {
			return array_map( function( $meta_value ) { return array( $meta_value ); }, $dummy_meta );
		}

		// Maybe return the meta value, wrapped in an array as WordPress unwraps single values
		if ( array_key_exists( $meta_key, $dummy_meta ) ) {
			return array( $dummy_meta[ $meta_key ] );
		}

		// Return an empty value, as the dummy customer has no other meta
		return $single ? '' : array();
	}

	/**
	 * Skip writing meta values of the dummy logged-in customer to the database.
	 *
	 * @param   null|bool  $check    Whether to skip writing, `null` to write the meta value.
	 * @param   int        $user_id  User ID.
	 */
	public function maybe_skip_dummy_customer_meta_write( $check, $user_id ) {
		// Bail if not the dummy customer
		if ( FluidCheckout_Admin_Preview_Dummy_Data::CUSTOMER_ID !== (int) $user_id ) { return $check; }

		return true;
	}

	/**
	 * Set the dummy customer fields on the guest customer, as guests have no saved address to fill the checkout fields.
	 */
	public function maybe_set_dummy_guest_customer_fields() {
		// Bail if logged in, as the dummy customer fields come from its saved account meta
		if ( is_user_logged_in() ) { return; }

		// Bail if customer is not available
		if ( ! WC()->customer instanceof WC_Customer ) { return; }

		// Set the fields the customer has properties for, skipping invalid values set through filters
		WC()->customer->set_props( FluidCheckout_Admin_Preview_Dummy_Data::instance()->get_customer_fields() );
	}

	/**
	 * Set the dummy addresses as the new addresses Fluid Checkout restores when a "same as" address checkbox is unchecked, as read-only fields cannot be filled.
	 */
	public function set_dummy_new_address_session_values() {
		// Bail if session is not available
		if ( ! WC()->session instanceof WC_Session ) { return; }

		// Iterate dummy customer fields
		foreach ( FluidCheckout_Admin_Preview_Dummy_Data::instance()->get_customer_fields() as $field_key => $value ) {
			FluidCheckout_Steps::instance()->set_checkout_field_value_to_session( 'save_' . $field_key, $value );
		}
	}

	/**
	 * Set the dummy values of order fields, such as order notes, in the session, where Fluid Checkout and its add-ons read them.
	 */
	public function set_dummy_order_fields_session_values() {
		// Bail if session is not available
		if ( ! WC()->session instanceof WC_Session ) { return; }

		// Iterate dummy order fields
		foreach ( FluidCheckout_Admin_Preview_Dummy_Data::instance()->get_order_fields() as $field_key => $value ) {
			FluidCheckout_Steps::instance()->set_checkout_field_value_to_session( $field_key, $value );
		}
	}

	/**
	 * Get the dummy values of checkout fields the customer has no property for, such as fields added by other plugins.
	 *
	 * @param   mixed   $value  Field value, `null` to get it from the customer.
	 * @param   string  $input  Field key.
	 */
	public function maybe_get_dummy_checkout_field_value( $value, $input ) {
		// Bail if the value is already set
		if ( null !== $value ) { return $value; }

		// Bail if the customer has a property for the field, already filled with the dummy value
		if ( method_exists( 'WC_Customer', 'get_' . $input ) ) { return $value; }

		// Get dummy customer fields
		$dummy_fields = FluidCheckout_Admin_Preview_Dummy_Data::instance()->get_customer_fields();

		// Bail if not a dummy customer field
		if ( ! array_key_exists( $input, $dummy_fields ) ) { return $value; }

		return $dummy_fields[ $input ];
	}



	/**
	 * Get the product type of dummy products, which have no database record to read it from.
	 *
	 * @param   string|false  $product_type  Product type, or `false` to read it from the database.
	 * @param   int           $product_id    Product ID.
	 */
	public function maybe_get_dummy_product_type( $product_type, $product_id ) {
		// Bail if not a dummy product
		if ( ! FluidCheckout_Admin_Preview_Dummy_Data::instance()->is_dummy_product_id( $product_id ) ) { return $product_type; }

		return 'simple';
	}

	/**
	 * Get the product class of dummy products, which loads them from memory instead of the database.
	 *
	 * @param   string  $class_name    Product class name.
	 * @param   string  $product_type  Product type.
	 * @param   string  $post_type     Post type of the product.
	 * @param   int     $product_id    Product ID.
	 */
	public function maybe_get_dummy_product_class( $class_name, $product_type, $post_type, $product_id ) {
		// Bail if not a dummy product
		if ( ! FluidCheckout_Admin_Preview_Dummy_Data::instance()->is_dummy_product_id( $product_id ) ) { return $class_name; }

		return 'FluidCheckout_Admin_Preview_Dummy_Product';
	}



	/**
	 * Get the products for the preview cart: recent catalog products, those that need shipping first, topped up with dummy products.
	 *
	 * @return  WC_Product[]
	 */
	public function get_preview_cart_products() {
		// Define product lists
		$shippable_products = array();
		$other_products = array();

		// Define query arguments for recent catalog products, more than needed as some may not be purchasable
		$query_args = array( 'status' => 'publish', 'type' => array( 'simple', 'variable' ), 'stock_status' => 'instock', 'limit' => 10, 'orderby' => 'date', 'order' => 'DESC', 'return' => 'ids' );

		// Get recent catalog product IDs, products that are not virtual first so recent virtual products cannot hide the shipping step
		$catalog_product_ids = array_unique( array_merge( wc_get_products( array_merge( $query_args, array( 'virtual' => false ) ) ), wc_get_products( $query_args ) ) );

		// Iterate catalog product IDs
		foreach ( $catalog_product_ids as $catalog_product_id ) {
			// Stop when enough products that need shipping are found
			if ( count( $shippable_products ) >= self::CART_ITEMS_LIMIT ) { break; }

			// Get product
			$product = wc_get_product( $catalog_product_id );

			// Maybe use the first purchasable variation of variable products
			if ( $product && $product->is_type( 'variable' ) ) {
				$product = $this->get_preview_variation( $product );
			}

			// Skip products that cannot be bought
			if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) { continue; }

			// Maybe add product to the list shown first, so the preview includes the shipping step
			if ( $product->needs_shipping() ) {
				$shippable_products[] = $product;
			}
			// Otherwise, add product to the list of other products
			else {
				$other_products[] = $product;
			}
		}

		// Get catalog products up to the cart items limit
		$products = array_slice( array_merge( $shippable_products, $other_products ), 0, self::CART_ITEMS_LIMIT );

		// Iterate dummy product IDs, to top up the cart
		foreach ( array_keys( FluidCheckout_Admin_Preview_Dummy_Data::instance()->get_products() ) as $dummy_product_id ) {
			// Stop when the cart items limit is reached
			if ( count( $products ) >= self::CART_ITEMS_LIMIT ) { break; }

			// Get dummy product
			$product = wc_get_product( $dummy_product_id );

			// Maybe add the dummy product, which only loads on preview requests
			if ( $product ) {
				$products[] = $product;
			}
		}

		return $products;
	}

	/**
	 * Get the first variation of a variable product that can be bought and has all its attributes set.
	 *
	 * @param   WC_Product_Variable  $product  Variable product.
	 *
	 * @return  WC_Product_Variation|false  The variation, or `false` when none can be bought.
	 */
	public function get_preview_variation( $product ) {
		// Iterate variations
		foreach ( $product->get_children() as $variation_id ) {
			// Get variation
			$variation = wc_get_product( $variation_id );

			// Skip variations that cannot be bought, or with attributes open to any value
			if ( ! $variation || ! $variation->is_purchasable() || ! $variation->is_in_stock() || in_array( '', $variation->get_variation_attributes(), true ) ) { continue; }

			return $variation;
		}

		return false;
	}

	/**
	 * Set the preview cart items in the session, before WooCommerce loads the cart from it.
	 * Uses the session instead of `add_to_cart()`, so add-to-cart events are not sent to analytics and marketing plugins.
	 */
	public function maybe_set_preview_cart_items() {
		// Bail if the session already has a cart
		if ( null !== WC()->session->get( 'cart', null ) ) { return; }

		// Define cart items
		$cart_items = array();

		// Iterate preview cart products
		foreach ( $this->get_preview_cart_products() as $product ) {
			// Maybe use the parent product ID and the attributes of variations
			if ( $product->is_type( 'variation' ) ) {
				$product_id = $product->get_parent_id();
				$variation_id = $product->get_id();
				$variation = $product->get_variation_attributes();
			}
			// Otherwise, use the product ID without variation
			else {
				$product_id = $product->get_id();
				$variation_id = 0;
				$variation = array();
			}

			// Add cart item, in the same shape WooCommerce saves to the session
			$cart_item_key = WC()->cart->generate_cart_id( $product_id, $variation_id, $variation );
			$cart_items[ $cart_item_key ] = array(
				'key'               => $cart_item_key,
				'product_id'        => $product_id,
				'variation_id'      => $variation_id,
				'variation'         => $variation,
				'quantity'          => 1,
			);
		}

		WC()->session->set( 'cart', $cart_items );
	}

	/**
	 * Set the shipping methods chosen in the preview, which the frame script sends with each request, as the preview session does not keep them between requests.
	 */
	public function maybe_set_preview_shipping_methods() {
		// Bail if the request has no shipping methods chosen in the preview
		if ( ! isset( $_GET[ self::SHIPPING_METHODS_QUERY_ARG ] ) || ! is_array( $_GET[ self::SHIPPING_METHODS_QUERY_ARG ] ) ) { return; }

		// Set the chosen shipping methods, by package index
		WC()->session->set( 'chosen_shipping_methods', wc_clean( wp_unslash( $_GET[ self::SHIPPING_METHODS_QUERY_ARG ] ) ) );
	}



	/**
	 * Use the preview order factory, which also gets the dummy orders shown on the order received and order pay pages.
	 */
	public function use_preview_order_factory() {
		WC()->order_factory = new FluidCheckout_Admin_Preview_Order_Factory();
	}

	/**
	 * Skip writing post meta values of the dummy orders to the database, as plugins write order meta with post meta functions on the order received page, such as to remember tracked purchases.
	 *
	 * @param   null|bool  $check    Whether to skip writing, `null` to write the meta value.
	 * @param   int        $post_id  Post ID.
	 */
	public function maybe_skip_dummy_order_meta_write( $check, $post_id ) {
		// Bail if not a dummy order
		if ( ! FluidCheckout_Admin_Preview_Dummy_Data::instance()->is_dummy_order_id( $post_id ) ) { return $check; }

		return true;
	}



	/**
	 * Get the name of the transient that keeps the unsaved settings of a preview token.
	 *
	 * @param   string  $token  Preview token.
	 *
	 * @return  string  Transient name.
	 */
	public function get_draft_transient_name( $token ) {
		return self::DRAFT_TRANSIENT_PREFIX . md5( $token );
	}

	/**
	 * Get the unsaved settings values of the current preview request.
	 *
	 * @return  array  Unsaved settings values by option name.
	 */
	public function get_draft_values() {
		// Maybe read the unsaved settings, once per request
		if ( null === $this->draft_values ) {
			$draft_values = get_transient( $this->get_draft_transient_name( $this->get_request_token() ) );
			$this->draft_values = is_array( $draft_values ) ? $draft_values : array();
		}

		return $this->draft_values;
	}

	/**
	 * Add the unsaved settings values to the effective settings values.
	 *
	 * @param   array  $values  Effective settings values by option name.
	 *
	 * @return  array  Effective settings values with the unsaved values.
	 */
	public function add_draft_values( $values ) {
		return array_merge( $values, $this->get_draft_values() );
	}

	/**
	 * Save the unsaved settings posted from the settings page for a preview token, so its preview requests show them.
	 */
	public function ajax_save_draft() {
		// Bail if the request nonce is not valid
		check_ajax_referer( self::DRAFT_AJAX_ACTION, 'nonce' );

		// Bail if the current user cannot use the preview
		if ( ! current_user_can( self::CAPABILITY ) ) { wp_send_json_error( null, 403 ); }

		// Get preview token
		$token = isset( $_POST[ 'token' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'token' ] ) ) : '';

		// Bail if the token is not a valid preview token of the current user
		if ( get_current_user_id() !== $this->get_token_user_id( $token ) ) { wp_send_json_error( null, 403 ); }

		// Get settings fields
		$settings_page = FluidCheckout_Admin_Settings_Page::instance();
		$settings = array();

		// Iterate settings tabs, as all tabs are in the settings form
		foreach ( $settings_page->get_tabs() as $tab => $tab_args ) {
			// Skip separators and tabs without settings to save
			if ( 'tab' !== $tab_args[ 'type' ] || ! $tab_args[ 'show_save_button' ] ) { continue; }

			$settings = array_merge( $settings, $settings_page->get_saveable_settings( $settings_page->get_tab_settings( $tab ) ) );
		}

		// Keep the posted values, sanitized like when saving the settings, until the token expires
		set_transient( $this->get_draft_transient_name( $token ), FluidCheckout_Settings::instance()->get_sanitized_settings_values( $settings ), self::TOKEN_LIFETIME );

		wp_send_json_success();
	}

}

FluidCheckout_Admin_Preview::instance();
