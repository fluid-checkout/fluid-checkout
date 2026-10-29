<?php
defined( 'ABSPATH' ) || exit;

/**
 * Checkout admin options.
 */
class FluidCheckout_Admin extends FluidCheckout {

	/**
	 * Installs on or after this date (site timezone, start of day) ship with the new settings experience.
	 */
	const INSTALL_CUTOFF_DATE_V5 = '2026-11-09';



	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->load_settings_access();
		$this->load_guided_setup();
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Plugin settings link
		add_filter( 'plugin_action_links_' . self::$plugin_basename, array( $this, 'add_plugin_settings_link' ), 10 );

		// Settings page
		add_action( 'init', array( $this, 'load_settings_page' ), 10 );

		// Load dashboard
		add_action( 'init', array( $this, 'load_dashboard' ), 10 );

		// Setting types
		add_action( 'init', array( $this, 'load_setting_types' ), 10 );

		// Settings providers (FC hooks)
		add_action( 'init', array( $this, 'load_settings_providers' ), 10 );

		// WooCommerce Settings redirect stub (deprecated, remove in 6.0)
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_pages' ), 50 );

		// Register assets
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_dashboard_assets' ), 10 );

		// Clear cache after saving settings
		add_action( 'woocommerce_settings_saved', array( $this, 'flush_cache' ), 10 );
		add_action( 'fc_admin_settings_saved', array( $this, 'flush_cache' ), 10 );
	}



	/**
	 * Whether the site was installed before the new settings experience cutoff.
	 */
	public static function is_legacy_install() {
		$install_date = (int) get_option( 'fc_plugin_activation_time', 0 );
		$cutoff       = strtotime( self::INSTALL_CUTOFF_DATE_V5 . ' 00:00:00' );

		// Bail if the cutoff timestamp is invalid
		if ( false === $cutoff ) { return true; }

		// Treat missing activation time as a legacy install
		if ( $install_date < 1 ) { return true; }

		return $install_date < $cutoff;
	}

	/**
	 * Strip third-party WordPress admin notices on FC admin screens.
	 *
	 * Guided setup: remove all `admin_notices` / `all_admin_notices` callbacks.
	 * Settings: remove all, then re-register the settings header and Fluid Checkout notices only.
	 *
	 * @param   bool  $re_register_fc_notices  Whether to re-attach FC header and notice display callbacks.
	 */
	public static function strip_non_fc_admin_notices_on_load( $re_register_fc_notices = false ) {
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );

		// Bail if FC notices should stay hidden (guided setup)
		if ( ! $re_register_fc_notices ) { return; }

		// Re-attach settings page header (hooked on `admin_notices` at priority 1)
		if ( class_exists( 'FluidCheckout_Admin_Settings_Page' ) ) {
			add_action( 'admin_notices', array( FluidCheckout_Admin_Settings_Page::instance(), 'output_admin_header' ), 1 );
		}

		// Re-attach Fluid Checkout notices (Lite renderer; PRO notices register on `fc_admin_notices`)
		if ( class_exists( 'FluidCheckout_AdminNotices' ) ) {
			add_action( 'admin_notices', array( FluidCheckout_AdminNotices::instance(), 'display_notices' ), 10 );
		}
	}



	/**
	 * Register admin settings assets.
	 * `fc-settings-page` is registered by the settings page class.
	 */
	public function register_assets() {
		// Bail if checkout assets are not loaded yet
		if ( ! class_exists( 'FluidCheckout_Enqueue' ) ) { return; }

		// Styles
		wp_register_style( 'fc-admin-options', FluidCheckout_Enqueue::instance()->get_style_url( 'css/admin-options' ), array(), NULL );
		wp_register_style( 'fc-admin-dashboard', FluidCheckout_Enqueue::instance()->get_style_url( 'css/admin-dashboard' ), array(), NULL );
		wp_register_style( 'fc-admin-guided-setup', FluidCheckout_Enqueue::instance()->get_style_url( 'css/admin-guided-setup' ), array(), NULL );

		// Scripts
		wp_register_script( 'fc-guided-setup', FluidCheckout_Enqueue::instance()->get_script_url( 'js/admin/fc-guided-setup' ), array( 'fc-utils', 'fc-collapsible-block' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}



	/**
	 * Load the settings access registry so add-ons can unlock features during bootstrap.
	 */
	public function load_settings_access() {
		include_once self::$directory_path . 'inc/admin/admin-settings-access.php';
	}

	/**
	 * Load the guided setup base class and Fluid Checkout smoke wizard.
	 */
	public function load_guided_setup() {
		require_once self::$directory_path . 'inc/admin/guided-setup/abstract-guided-setup.php';
		require_once self::$directory_path . 'inc/admin/guided-setup/guided-setup-fluid-checkout.php';
	}



	/**
	 * Load the Fluid Checkout settings page and field renderer.
	 */
	public function load_settings_page() {
		$this->load_settings_access();
		include_once self::$directory_path . 'inc/admin/admin-settings-renderer.php';
		include_once self::$directory_path . 'inc/admin/admin-settings-page.php';
	}



	/**
	 * Enqueue admin settings assets.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-settings-page' );

		// Styles
		wp_enqueue_style( 'fc-admin-options' );
	}

	/**
	 * Maybe enqueue admin settings assets.
	 *
	 * @param  string  $hook_suffix  Hook suffix for the current admin page.
	 */
	public function maybe_enqueue_assets( $hook_suffix ) {
		// Bail if not on WooCommerce settings page
		if ( 'woocommerce_page_wc-settings' !== $hook_suffix ) { return; }

		$this->enqueue_assets();
	}

	/**
	 * Enqueue admin dashboard assets.
	 */
	public function enqueue_dashboard_assets() {
		// Styles
		wp_enqueue_style( 'fc-admin-dashboard' );
	}

	/**
	 * Maybe enqueue admin dashboard assets.
	 *
	 * @param  string  $hook_suffix  Hook suffix for the current admin page.
	 */
	public function maybe_enqueue_dashboard_assets( $hook_suffix ) {
		// Get current screen
		$current_screen = get_current_screen();

		// Bail if current screen is not available
		if ( ! $current_screen ) { return; }

		// Bail if not on WooCommerce settings page
		if ( $current_screen->id !== 'woocommerce_page_wc-settings' ) { return; }

		// Get current tab and section
		$current_tab = isset( $_GET[ 'tab' ] ) ? sanitize_text_field( wp_unslash( $_GET[ 'tab' ] ?? '' ) ) : 'general';
		$current_section = isset( $_GET[ 'section' ] ) ? sanitize_text_field( wp_unslash( $_GET[ 'section' ] ?? '' ) ) : '';

		// Bail if not on dashboard settings page
		if ( 'fc_checkout' !== $current_tab || ! empty( $current_section ) ) { return; }

		$this->enqueue_dashboard_assets();
	}

	/**
	 * Load dashboard section types.
	 */
	public function load_dashboard() {
		include_once self::$directory_path . 'inc/admin/admin-dashboard-actions.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-setup.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-addons.php';
	}

	/**
	 * Load custom setting field types.
	 */
	public function load_setting_types() {
		// Load settings field types
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-paragraph.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-separator.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-promo.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-input.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-select.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-multiselect.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-telemetry.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-textarea.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-layout-selector.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-template-selector.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-image-uploader.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-gaa-google-api-key.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-button.php';

		// Telemetry controls
		include_once self::$directory_path . 'inc/admin/admin-telemetry.php';
	}

	/**
	 * Load settings providers that register tabs, sections and fields on FC hooks.
	 */
	public function load_settings_providers() {
		// Settings pages
		include_once self::$directory_path . 'inc/admin/admin-settings-wc-shipping.php';
		include_once self::$directory_path . 'inc/admin/admin-settings-wc-accounts.php';
		include_once self::$directory_path . 'inc/admin/admin-settings-dashboard.php';
		include_once self::$directory_path . 'inc/admin/admin-settings-checkout.php';
		include_once self::$directory_path . 'inc/admin/admin-settings-cart.php';
		include_once self::$directory_path . 'inc/admin/admin-settings-order-received.php';
		include_once self::$directory_path . 'inc/admin/admin-settings-order-pay.php';
		include_once self::$directory_path . 'inc/admin/admin-settings-express-checkout.php';
		include_once self::$directory_path . 'inc/admin/admin-settings-customer-accounts.php';
		include_once self::$directory_path . 'inc/admin/admin-settings-local-pickup.php';
		include_once self::$directory_path . 'inc/admin/admin-settings-gift-options.php';
		include_once self::$directory_path . 'inc/admin/admin-settings-international-phone.php';
		include_once self::$directory_path . 'inc/admin/admin-settings-trust-symbols.php';
		include_once self::$directory_path . 'inc/admin/admin-settings-address-autocomplete.php';
		include_once self::$directory_path . 'inc/admin/admin-settings-address-book.php';
		include_once self::$directory_path . 'inc/admin/admin-settings-vat-assistant.php';
		include_once self::$directory_path . 'inc/admin/admin-settings-integrations.php';
		include_once self::$directory_path . 'inc/admin/admin-settings-tools.php';
		include_once self::$directory_path . 'inc/admin/admin-settings-license-keys.php';

		// Live preview
		include_once self::$directory_path . 'inc/admin/admin-settings-preview.php';
	}

	/**
	 * Add the deprecated WooCommerce Settings redirect stub for Fluid Checkout.
	 * Settings providers are loaded on `init` via `load_settings_providers()`.
	 * Remove this stub in version 6.0.
	 *
	 * @param  array  $settings  WooCommerce settings page instances.
	 */
	public function add_settings_pages( $settings ) {
		// `$settings` need to be an array
		if ( ! is_array( $settings ) ) { $settings = array( $settings ); }

		// Bail if the merchant opted out of the WooCommerce Settings tab
		if ( 'yes' === get_option( 'fc_hide_wc_settings_tab', 'no' ) ) {
			return $settings;
		}

		// Maybe add settings tab stub if not already added
		/**
		 * Filters whether the Fluid Checkout settings tab has already been added.
		 *
		 * @since 4.0.5
		 *
		 * @param bool $value Value to filter. Default false.
		 */
		if ( ! apply_filters( 'fc_admin_tab_fluidcheckout_exists', false ) ) {
			$settings[] = include self::$directory_path . 'inc/admin/admin-tab-fluid-checkout.php';

			// Set admin tab as existent so it won't be loaded again
			add_filter( 'fc_admin_tab_fluidcheckout_exists', '__return_true', 10 );
		}

		return $settings;
	}



	/**
	 * Add settings page link to plugin listing.
	 * @param array $links
	 */
	public function add_plugin_settings_link( $links = array() ) {
		// Add links before existing ones
		$new_links = array(
			sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=fluid-checkout&tab=checkout' ) ), esc_html( __( 'Settings', 'fluid-checkout' ) ) ),
			sprintf( '<a href="%s" target="_blank">%s</a>', 'https://fluidcheckout.com/support/', esc_html( __( 'Support', 'fluid-checkout' ) ) ),
		);

		$links = array_merge( $new_links, $links );

		// Maybe add PRO version promotion
		if ( ! FluidCheckout::instance()->is_pro_activated() ) {
			$links[] = sprintf( '<a href="%s" target="_blank" style="color:#007F01;font-weight:bold;">%s</a>', 'https://fluidcheckout.com/pricing/?mtm_campaign=upgrade-pro&mtm_kwd=plugins-list&mtm_source=lite-plugin', esc_html( __( 'Upgrade to PRO', 'fluid-checkout' ) ) );
		}

		return $links;
	}



	/**
	 * Get the legacy "Upgrade to PRO" settings description.
	 *
	 * @deprecated 5.0.0 Use `get_pro_feature_badge_html()` for new settings UI. Will be removed in version 6.0.0.
	 *
	 * @param  bool  $newline  Whether to prefix the link with a line break.
	 */
	public function get_upgrade_pro_html( $newline = true ) {
		// Bail if PRO is already activated
		if ( FluidCheckout::instance()->is_pro_activated() ) { return ''; }

		// translators: %s: Upgrade link.
		$html = wp_kses_post( sprintf( __( '<a target="_blank" href="%s">Upgrade to PRO</a> to unlock more options.', 'fluid-checkout' ), 'https://fluidcheckout.com/pricing/?mtm_campaign=upgrade-pro&mtm_kwd=plugin-settings&mtm_source=lite-plugin' ) );

		// Maybe add a line break before the link
		if ( $newline ) {
			$html = ' <br>' . $html;
		}

		return $html;
	}

	/**
	 * Get HTML for the PRO feature promo pill badge.
	 *
	 * @param  string       $section_slug  Section slug used in tracking as `mtm_kwd=pro-badge-{slug}`.
	 * @param  string|null  $label         Optional badge label. Defaults to "PRO feature".
	 */
	public function get_pro_feature_badge_html( $section_slug = '', $label = null ) {
		// Bail if PRO is already activated
		if ( FluidCheckout::instance()->is_pro_activated() ) { return ''; }

		$section_slug = sanitize_title( $section_slug );
		$mtm_kwd = ! empty( $section_slug ) ? 'pro-badge-' . $section_slug : 'pro-badge';
		$label = null !== $label ? $label : __( 'PRO feature', 'fluid-checkout' );

		$url = add_query_arg(
			array(
				'mtm_campaign' => 'upgrade-pro',
				'mtm_kwd'      => $mtm_kwd,
				'mtm_source'   => 'lite-plugin',
			),
			'https://fluidcheckout.com/pricing/'
		);

		return sprintf(
			'<a class="fc-settings-promo-pill" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( $url ),
			esc_html( $label )
		);
	}

	/**
	 * Get HTML for the PRO feature promo pill on add-on settings sections.
	 * Returns empty when the add-on feature is already unlocked.
	 *
	 * @param  string  $section_slug  Section slug used in tracking as `mtm_kwd=pro-badge-{slug}`.
	 * @param  string  $product_url   Unused. Kept for backward compatibility with existing call sites.
	 * @param  string  $feature_slug  Feature slug checked against the settings access registry.
	 */
	public function get_addon_feature_badge_html( $section_slug = '', $product_url = '', $feature_slug = '' ) {
		// Bail if the add-on feature is already unlocked
		if ( ! empty( $feature_slug ) && class_exists( 'FluidCheckout_Admin_Settings_Access' ) && FluidCheckout_Admin_Settings_Access::instance()->is_unlocked( $feature_slug ) ) {
			return '';
		}

		return $this->get_pro_feature_badge_html( $section_slug );
	}

	/**
	 * Get HTML for the “Other plugins” category promo pill badge (non-interactive).
	 */
	public function get_other_plugins_badge_html() {
		return sprintf(
			'<span class="fc-settings-promo-pill fc-settings-promo-pill--addon fc-settings-promo-pill--static">%s</span>',
			esc_html( __( 'Other plugins', 'fluid-checkout' ) )
		);
	}



	/**
	 * Get the purchase button label for an add-on.
	 *
	 * @param  string  $price  Formatted price including currency, e.g. `29 EUR`.
	 */
	public function get_addon_purchase_button_label( $price ) {
		/* translators: %s: formatted price including currency, e.g. "29 EUR" */
		return sprintf( __( 'Get only this add-on &mdash; %s', 'fluid-checkout' ), $price );
	}

	/**
	 * Get the purchase button label for an add-on without a price.
	 *
	 * Used when PRO is installed so entitled customers are not nudged to pay again.
	 */
	public function get_addon_purchase_button_label_without_price() {
		return __( 'Install add-on', 'fluid-checkout' );
	}

	/**
	 * Get the upgrade button label for Fluid Checkout PRO.
	 *
	 * @param  string  $price  Formatted price including currency, e.g. `129 EUR`.
	 */
	public function get_pro_upgrade_button_label( $price = '129 EUR' ) {
		/* translators: %s: formatted price including currency, e.g. "129 EUR" */
		return sprintf( __( 'Upgrade to PRO &mdash; %s', 'fluid-checkout' ), $price );
	}

	/**
	 * Get the upgrade button label for Fluid Checkout PRO without a price.
	 */
	public function get_pro_upgrade_button_label_without_price() {
		return __( 'Upgrade to PRO', 'fluid-checkout' );
	}

	/**
	 * Get HTML for PRO features label.
	 *
	 * @param  bool  $add_space_after  Whether to add a space after the text. Defaults to `false`, which adds the space before.
	 */
	public function get_pro_feature_option_html( $add_space_after = false ) {
		// Bail if PRO is already activated
		if ( FluidCheckout::instance()->is_pro_activated() ) { return ''; }

		// Get the HTML for the PRO feature labels
		$html = __( '(PRO)', 'fluid-checkout' );

		// Maybe add space after the text
		if ( $add_space_after ) {
			$html = $html . ' ';
		}
		// Otherwise add the space before
		else {
			$html = ' ' . $html;
		}

		return $html;
	}

	/**
	 * Get HTML for Experimental features label.
	 * 
	 * @param  bool  $newline  Whether to add a new line before.
	 */
	public function get_experimental_feature_html( $newline = false ) {
		return ' ' . ( $newline ? '<br>' : '' ) . __( '(experimental)', 'fluid-checkout' );
	}

	/**
	 * Append the experimental label suffix to a settings field option label.
	 *
	 * @param  string  $label    Option label text, usually the field `desc` for checkboxes.
	 * @param  bool    $newline  Whether to add a new line before the suffix.
	 */
	public function append_experimental_to_option_label( $label, $newline = false ) {
		$label = (string) $label;

		// Bail if label is empty
		if ( '' === $label ) {
			return ltrim( $this->get_experimental_feature_html( $newline ) );
		}

		return $label . $this->get_experimental_feature_html( $newline );
	}

	/**
	 * Get HTML experimental features explanation.
	 * 
	 * @param  bool  $newline  Whether to add a new line before.
	 */
	public function get_experimental_feature_explanation_html( $newline = false ) {		
		return ' ' . ( $newline ? '<br>' : '' ) . __( 'This is an experimental feature and may not work as expected in all setups. Use it with caution.', 'fluid-checkout' );
	}

	/**
	 * Get HTML for the locked add-on notice displayed on settings of add-ons that are not active.
	 *
	 * @param  string  $addon_name   Add-on name.
	 * @param  string  $product_url  URL of the add-on product page.
	 */
	public function get_addon_locked_notice_html( $addon_name, $product_url ) {
		// translators: %1$s: Add-on product page URL, %2$s: Add-on name.
		$html = sprintf( __( '<a target="_blank" href="%1$s">Get %2$s</a> to unlock these options.', 'fluid-checkout' ), esc_url( $product_url ), esc_html( $addon_name ) );

		return wp_kses_post( '<span class="fc-settings-badge">' . esc_html( __( 'Add-on', 'fluid-checkout' ) ) . '</span> ' . $html );
	}

	/**
	 * Maybe append Matomo tracking query args to a Fluid Checkout documentation URL.
	 *
	 * @param  string  $url      Documentation URL.
	 * @param  string  $mtm_kwd  Optional tracking keyword. Defaults to the last URL path segment.
	 */
	public function maybe_add_documentation_mtm_args( $url, $mtm_kwd = '' ) {
		// Bail if not a Fluid Checkout URL
		if ( false === strpos( $url, 'fluidcheckout.com' ) ) { return $url; }

		// Maybe derive keyword from the documentation URL path
		if ( empty( $mtm_kwd ) ) {
			$path = wp_parse_url( $url, PHP_URL_PATH );
			$mtm_kwd = sanitize_title( basename( untrailingslashit( (string) $path ) ) );
		}

		// Fallback keyword
		if ( empty( $mtm_kwd ) ) {
			$mtm_kwd = 'docs';
		}

		return add_query_arg(
			array(
				'mtm_campaign' => 'settings-docs',
				'mtm_kwd'      => $mtm_kwd,
				'mtm_source'   => 'lite-plugin',
			),
			$url
		);
	}

	/**
	 * Get HTML for documentation link to be used on settings descriptions.
	 *
	 * @param  string  $url      Documentation URL.
	 * @param  string  $mtm_kwd  Optional Matomo keyword for Fluid Checkout docs URLs.
	 */
	public function get_documentation_link_html( $url = 'https://fluidcheckout.com/docs/', $mtm_kwd = '' ) {
		$url = $this->maybe_add_documentation_mtm_args( $url, $mtm_kwd );

		return sprintf( '<a target="_blank" href="%s">%s</a>', esc_url( $url ), __( 'Read the documentation.', 'fluid-checkout' ) );
	}

	/**
	 * Get HTML for a documentation info icon link, typically used in settings card headers.
	 *
	 * @param  string  $url      Documentation URL.
	 * @param  string  $mtm_kwd  Optional Matomo keyword for Fluid Checkout docs URLs.
	 */
	public function get_documentation_icon_html( $url = 'https://fluidcheckout.com/docs/', $mtm_kwd = '' ) {
		$label = __( 'View documentation', 'fluid-checkout' );
		$url = $this->maybe_add_documentation_mtm_args( $url, $mtm_kwd );

		return sprintf(
			'<a class="fc-settings-docs-icon" href="%1$s" target="_blank" rel="noopener noreferrer" aria-label="%2$s" title="%2$s"><span class="dashicons dashicons-info-outline" aria-hidden="true"></span></a>',
			esc_url( $url ),
			esc_attr( $label )
		);
	}

	/**
	 * Get HTML for an external-link icon, typically shown on settings buttons that open a new tab.
	 */
	public function get_external_tab_icon_html() {
		return '<span class="fc-settings-external-tab-icon dashicons dashicons-external" aria-hidden="true"></span>';
	}



	/**
	 * Encloses the function `wp_cache_flush()` to ensure no parameter is passed into it.
	 */
	public function flush_cache() {
		wp_cache_flush();
	}

}

FluidCheckout_Admin::instance();
