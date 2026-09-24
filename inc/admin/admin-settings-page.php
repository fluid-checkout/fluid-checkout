<?php
defined( 'ABSPATH' ) || exit;

/**
 * Fluid Checkout admin menu and settings page with vertical sidebar navigation.
 */
class FluidCheckout_Admin_Settings_Page extends FluidCheckout {

	/**
	 * Settings page slug.
	 */
	const PAGE_SLUG = 'fluid-checkout';

	/**
	 * Capability required to access the settings page.
	 */
	const CAPABILITY = 'manage_woocommerce';

	/**
	 * Default tab when the requested tab is missing or invalid.
	 */
	const DEFAULT_TAB = 'checkout';

	/**
	 * WooCommerce settings tab ID used by the settings sections.
	 */
	const WC_SETTINGS_TAB_ID = 'fc_checkout';

	/**
	 * Settings page hook suffix, set when registering the admin menu.
	 *
	 * @var string
	 */
	private $page_hook_suffix = '';

	/**
	 * Settings tabs, cached after first retrieval.
	 *
	 * @var array|null
	 */
	private $tabs = null;



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
		// Admin menu
		add_action( 'admin_menu', array( $this, 'register_menu' ), 10 );
		add_filter( 'submenu_file', array( $this, 'maybe_set_current_submenu' ), 10, 2 );

		// Save settings
		add_action( 'admin_init', array( $this, 'maybe_save_settings' ), 10 );

		// Admin body class
		add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ), 10 );

		// Admin page header
		add_action( 'admin_notices', array( $this, 'output_admin_header' ), 1 );

		// Remove WooCommerce Help tabs on this page
		add_action( 'admin_head', array( $this, 'maybe_remove_help_tabs' ), 10 );

		// Register assets
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets after shared library scripts are registered
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 20 );

		// License key field assets
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_register_license_key_assets' ), 10 );
	}



	/**
	 * Register the Fluid Checkout admin menu.
	 * The Dashboard and Settings submenu items are links to tabs of the same settings page.
	 */
	public function register_menu() {
		$this->page_hook_suffix = add_menu_page(
			__( 'Fluid Checkout', 'fluid-checkout' ),
			__( 'Fluid Checkout', 'fluid-checkout' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'output_page' ),
			$this->get_menu_icon_url(),
			56
		);

		add_submenu_page( self::PAGE_SLUG, __( 'Dashboard', 'fluid-checkout' ), __( 'Dashboard', 'fluid-checkout' ), self::CAPABILITY, $this->get_settings_menu_slug( 'dashboard' ) );
		add_submenu_page( self::PAGE_SLUG, __( 'Settings', 'fluid-checkout' ), __( 'Settings', 'fluid-checkout' ), self::CAPABILITY, $this->get_settings_menu_slug( self::DEFAULT_TAB ) );

		// Remove the submenu item automatically added for the top-level page,
		// so the top-level menu links to the Dashboard tab
		remove_submenu_page( self::PAGE_SLUG, self::PAGE_SLUG );
	}

	/**
	 * Get the admin menu icon as a base64-encoded SVG data URI for WordPress color scheme painting.
	 */
	public function get_menu_icon_url() {
		$icon_path = FluidCheckout::$directory_path . 'images/admin/logo--menu.svg';

		// Bail if icon file is missing
		if ( ! file_exists( $icon_path ) ) {
			return 'dashicons-cart';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local plugin asset.
		$svg = file_get_contents( $icon_path );

		// Bail if icon could not be read
		if ( false === $svg || '' === $svg ) {
			return 'dashicons-cart';
		}

		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	/**
	 * Get the relative admin URL used as the menu slug for a settings tab submenu item.
	 *
	 * @param  string  $tab  Settings tab slug.
	 */
	public function get_settings_menu_slug( $tab ) {
		return 'admin.php?page=' . self::PAGE_SLUG . '&tab=' . $tab;
	}

	/**
	 * Highlight the submenu item that matches the current settings tab.
	 *
	 * @param  string|null  $submenu_file  The submenu file.
	 * @param  string       $parent_file   The parent file.
	 */
	public function maybe_set_current_submenu( $submenu_file, $parent_file ) {
		// Bail if not on the settings page
		if ( ! $this->is_settings_page() ) { return $submenu_file; }

		$current_tab = $this->get_current_tab();

		// Highlight the Dashboard submenu item
		if ( 'dashboard' === $current_tab ) {
			return $this->get_settings_menu_slug( 'dashboard' );
		}

		return $this->get_settings_menu_slug( self::DEFAULT_TAB );
	}



	/**
	 * Check whether the current request is for the settings page.
	 *
	 * @param  string  $tab  Optional. Only return `true` when the current tab matches this tab slug.
	 */
	public function is_settings_page( $tab = '' ) {
		// Bail if not in the admin
		if ( ! is_admin() ) { return false; }

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET[ 'page' ] ) ? sanitize_text_field( wp_unslash( $_GET[ 'page' ] ) ) : '';

		// Bail if not on the settings page
		if ( self::PAGE_SLUG !== $page ) { return false; }

		// Bail if the current tab does not match
		if ( ! empty( $tab ) && $tab !== $this->get_current_tab() ) { return false; }

		return true;
	}

	/**
	 * Add a body class on the Fluid Checkout settings page.
	 *
	 * @param  string  $classes  Space-separated list of admin body classes.
	 */
	public function add_admin_body_class( $classes ) {
		// Bail if not on the settings page
		if ( ! $this->is_settings_page() ) { return $classes; }

		$classes .= ' fc-settings-page';

		return $classes;
	}

	/**
	 * Remove WooCommerce Help tabs from the settings page.
	 */
	public function maybe_remove_help_tabs() {
		// Bail if not on the settings page
		if ( ! $this->is_settings_page() ) { return; }

		$screen = get_current_screen();

		// Bail if the current screen is not available
		if ( ! $screen ) { return; }

		$screen->remove_help_tabs();
	}

	/**
	 * Output the Fluid Checkout admin page header.
	 */
	public function output_admin_header() {
		// Bail if not on the settings page
		if ( ! $this->is_settings_page() ) { return; }

		$tabs = $this->get_tabs();
		$current_tab = $this->get_current_tab();
		$can_save = ! empty( $tabs[ $current_tab ][ 'show_save_button' ] );

		$logo_url = FluidCheckout::$directory_url . 'images/admin/fluid-checkout-icon.png';
		$home_url = 'https://fluidcheckout.com/?mtm_campaign=admin-header&mtm_kwd=logo&mtm_source=settings-header';
		$support_url = 'https://fluidcheckout.com/support/?mtm_campaign=admin-header&mtm_kwd=support&mtm_source=settings-header';
		$docs_url = 'https://fluidcheckout.com/docs/?mtm_campaign=admin-header&mtm_kwd=docs&mtm_source=settings-header';
		$upgrade_url = 'https://fluidcheckout.com/pricing/?mtm_campaign=admin-header&mtm_kwd=upgrade-pro&mtm_source=settings-header';
		?>
		<div id="fc-header">
			<a class="fc-header__logo" href="<?php echo esc_url( $home_url ); ?>" target="_blank" rel="noopener noreferrer">
				<img class="fc-header__logo-icon" src="<?php echo esc_url( $logo_url ); ?>" alt="" />
				<span class="fc-header__logo-text"><?php echo esc_html( __( 'Fluid Checkout', 'fluid-checkout' ) ); ?></span>
			</a>

			<a class="fc-header__button" href="<?php echo esc_url( $support_url ); ?>" target="_blank" rel="noopener noreferrer">
				<span class="dashicons dashicons-email-alt" aria-hidden="true"></span><?php echo esc_html( __( 'Support', 'fluid-checkout' ) ); ?>
			</a>
			<a class="fc-header__button" href="<?php echo esc_url( $docs_url ); ?>" target="_blank" rel="noopener noreferrer">
				<span class="dashicons dashicons-book" aria-hidden="true"></span><?php echo esc_html( __( 'Docs', 'fluid-checkout' ) ); ?>
			</a>
			<?php if ( ! FluidCheckout::instance()->is_pro_activated() ) : ?>
				<a class="fc-header__button fc-header__button--upgrade" href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener noreferrer">
					<span class="dashicons dashicons-upload" aria-hidden="true"></span><?php echo esc_html( __( 'Upgrade to PRO', 'fluid-checkout' ) ); ?>
				</a>
			<?php endif; ?>
			<button
				type="submit"
				form="mainform"
				class="fc-header__button fc-header__button--save"
				data-fc-settings-save
				<?php disabled( ! $can_save ); ?>
			><?php echo esc_html( __( 'Save settings', 'fluid-checkout' ) ); ?></button>
		</div>
		<?php
	}



	/**
	 * Load the WooCommerce settings pages, which register the settings sections used by the settings page tabs.
	 */
	public function load_settings_pages() {
		// Maybe load the WooCommerce admin settings class
		if ( ! class_exists( 'WC_Admin_Settings', false ) && function_exists( 'WC' ) ) {
			include_once WC()->plugin_path() . '/includes/admin/class-wc-admin-settings.php';
		}

		// Bail if the WooCommerce admin settings class is not available
		if ( ! class_exists( 'WC_Admin_Settings', false ) ) { return; }

		WC_Admin_Settings::get_settings_pages();
	}



	/**
	 * Get the settings tabs.
	 * Settings sections registered by other plugins which are not part of the default tabs are added as extra tabs.
	 */
	public function get_tabs() {
		// Maybe return cached tabs
		if ( null !== $this->tabs ) { return $this->tabs; }

		$this->load_settings_pages();

		$tabs = array(
			'dashboard'                => array( 'label' => __( 'Dashboard', 'fluid-checkout' ), 'section' => '', 'show_save_button' => true ),
			'checkout'                 => array( 'label' => __( 'Checkout', 'fluid-checkout' ), 'section' => 'checkout' ),
			'cart'                     => array( 'label' => __( 'Cart', 'fluid-checkout' ), 'section' => 'cart' ),
			'order_received'           => array( 'label' => __( 'Thank You', 'fluid-checkout' ), 'section' => 'order_received' ),
			'order_pay'                => array( 'label' => __( 'Order Pay', 'fluid-checkout' ), 'section' => 'order_pay' ),
			'separator'                => array( 'type' => 'separator' ),
			'express_checkout'         => array( 'label' => __( 'Express Checkout', 'fluid-checkout' ), 'section' => 'express_checkout' ),
			'account_matching'         => array( 'label' => __( 'Account Matching', 'fluid-checkout' ), 'section' => 'account_matching' ),
			'local_pickup'             => array( 'label' => __( 'Local Pickup', 'fluid-checkout' ), 'section' => 'local_pickup' ),
			'gift_options'             => array( 'label' => __( 'Gift Options', 'fluid-checkout' ), 'section' => 'gift_options' ),
			'international_phone'      => array( 'label' => __( 'International Phone Numbers', 'fluid-checkout' ), 'section' => 'international_phone' ),
			'separator_2'              => array( 'type' => 'separator' ),
			'address_autocomplete'     => array( 'label' => __( 'Address Autocomplete', 'fluid-checkout' ), 'section' => 'address_autocomplete' ),
			'address_book'             => array( 'label' => __( 'Address Book', 'fluid-checkout' ), 'section' => 'address_book' ),
			'vat_assistant'            => array( 'label' => __( 'EU-VAT Assistant', 'fluid-checkout' ), 'section' => 'vat_number' ),
		);
		$tabs_after = array(
			'separator_3'              => array( 'type' => 'separator' ),
			'integrations'             => array( 'label' => __( 'Integrations', 'fluid-checkout' ), 'section' => 'integrations' ),
			'tools'                    => array( 'label' => __( 'Tools', 'fluid-checkout' ), 'section' => 'tools' ),
			'license_keys'             => array( 'label' => __( 'License Keys', 'fluid-checkout' ), 'section' => 'license_keys', 'show_save_button' => false ),
		);

		// Get settings sections registered for the WooCommerce settings tab
		$sections = apply_filters( 'woocommerce_get_sections_' . self::WC_SETTINGS_TAB_ID, array() );
		$sections = is_array( $sections ) ? $sections : array();

		// Collect section slugs already claimed by the fixed tabs (skip separators)
		$known_sections = array();
		foreach ( array_merge( $tabs, $tabs_after ) as $tab_args ) {
			// Skip separators and tabs without a section key
			if ( ! is_array( $tab_args ) || ! array_key_exists( 'section', $tab_args ) ) { continue; }

			$known_sections[] = $tab_args[ 'section' ];
		}

		// Add settings sections from other plugins as extra tabs
		foreach ( $sections as $section => $label ) {
			$section = (string) $section;

			// Skip sections which already have a tab
			if ( in_array( $section, $known_sections, true ) ) { continue; }

			$tabs[ sanitize_key( $section ) ] = array( 'label' => $label, 'section' => $section );
		}

		$tabs = array_merge( $tabs, $tabs_after );

		/**
		 * Filter the tabs of the Fluid Checkout settings page.
		 * Each tab is an array with the keys `label`, `section`, and optionally `show_save_button`.
		 * Separators are entries with `type` set to `separator`.
		 */
		$tabs = apply_filters( 'fc_admin_settings_tabs', $tabs );

		$this->tabs = $this->normalize_tabs( $tabs );

		return $this->tabs;
	}

	/**
	 * Normalize tab arguments and remove leading, trailing and repeated separators.
	 *
	 * @param  array  $tabs  Settings tabs.
	 */
	public function normalize_tabs( $tabs ) {
		$normalized_tabs = array();
		$last_type = 'separator';

		foreach ( (array) $tabs as $slug => $tab ) {
			// Skip invalid tabs
			if ( ! is_array( $tab ) ) { continue; }

			$tab = wp_parse_args( $tab, array(
				'type'             => 'tab',
				'label'            => '',
				'section'          => '',
				'show_save_button' => true,
			) );

			// Skip repeated separators
			if ( 'separator' === $tab[ 'type' ] && 'separator' === $last_type ) { continue; }

			$normalized_tabs[ $slug ] = $tab;
			$last_type = $tab[ 'type' ];
		}

		// Remove trailing separator
		if ( 'separator' === $last_type ) {
			array_pop( $normalized_tabs );
		}

		return $normalized_tabs;
	}

	/**
	 * Check whether a tab slug is a valid settings tab.
	 *
	 * @param  string  $tab  Settings tab slug.
	 */
	public function is_valid_tab( $tab ) {
		$tabs = $this->get_tabs();
		return is_string( $tab ) && array_key_exists( $tab, $tabs ) && 'tab' === $tabs[ $tab ][ 'type' ];
	}

	/**
	 * Get the current settings tab slug. Falls back to the Checkout tab when missing or invalid.
	 */
	public function get_current_tab() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET[ 'tab' ] ) ? sanitize_key( wp_unslash( $_GET[ 'tab' ] ) ) : '';

		// Use default tab if requested tab is not valid
		if ( ! $this->is_valid_tab( $tab ) ) {
			$tab = self::DEFAULT_TAB;
		}

		return $tab;
	}

	/**
	 * Get the settings tab slug that displays a settings section of the WooCommerce settings tab.
	 * Falls back to the Checkout tab when no tab displays the section.
	 *
	 * @param  string  $section  Settings section slug.
	 */
	public function get_tab_for_section( $section ) {
		foreach ( $this->get_tabs() as $slug => $tab ) {
			if ( 'tab' === $tab[ 'type' ] && '' !== $section && $section === $tab[ 'section' ] ) {
				return $slug;
			}
		}

		return self::DEFAULT_TAB;
	}

	/**
	 * Get the URL for a settings tab.
	 *
	 * @param  string  $tab  Optional. Settings tab slug. Defaults to the Checkout tab.
	 */
	public function get_settings_url( $tab = self::DEFAULT_TAB ) {
		return add_query_arg( array( 'page' => self::PAGE_SLUG, 'tab' => $tab ), admin_url( 'admin.php' ) );
	}

	/**
	 * Get the settings for a tab.
	 *
	 * @param  string  $tab  Settings tab slug.
	 */
	public function get_tab_settings( $tab ) {
		$tabs = $this->get_tabs();

		// Bail if tab is not valid
		if ( ! $this->is_valid_tab( $tab ) ) { return array(); }

		// Get settings from the settings sections of the WooCommerce settings tab
		$settings = apply_filters( 'woocommerce_get_settings_' . self::WC_SETTINGS_TAB_ID, array(), $tabs[ $tab ][ 'section' ] );

		// Bail if settings are not valid
		if ( ! is_array( $settings ) ) { return array(); }

		// Enable settings that require unlocked features
		return FluidCheckout_Admin_Settings_Access::instance()->apply_to_settings( $settings );
	}

	/**
	 * Remove settings which should not be saved, such as locked fields.
	 *
	 * @param  array  $settings  Settings arrays.
	 */
	public function get_saveable_settings( $settings ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		return FluidCheckout_Admin_Settings_Access::instance()->get_saveable_settings( $settings, $_POST );
	}



	/**
	 * Maybe save settings for all saveable tabs.
	 * All tab panels are rendered in one form, so every saveable tab is saved together.
	 */
	public function maybe_save_settings() {
		// Bail if not on the settings page
		if ( ! $this->is_settings_page() ) { return; }

		// Bail if not a save request
		if ( ! isset( $_POST[ 'fc_settings_action' ] ) || 'save' !== sanitize_key( wp_unslash( $_POST[ 'fc_settings_action' ] ) ) ) { return; }

		// Bail if user does not have enough permissions
		if ( ! current_user_can( self::CAPABILITY ) ) { return; }

		check_admin_referer( 'fc_settings_save', 'fc_settings_nonce' );

		$current_tab = $this->get_current_tab();
		$tabs = $this->get_tabs();

		foreach ( $tabs as $tab => $tab_args ) {
			// Skip separators and tabs without settings to save
			if ( 'tab' !== $tab_args[ 'type' ] || ! $tab_args[ 'show_save_button' ] ) { continue; }

			$settings = $this->get_saveable_settings( $this->get_tab_settings( $tab ) );
			WC_Admin_Settings::save_fields( $settings );

			// Run the WooCommerce section save hook
			if ( '' !== $tab_args[ 'section' ] ) {
				do_action( 'woocommerce_update_options_' . self::WC_SETTINGS_TAB_ID . '_' . $tab_args[ 'section' ] );
			}
		}

		/**
		 * After settings are saved on the Fluid Checkout settings page.
		 *
		 * @param  string  $tab  Settings tab slug that was active when saving.
		 */
		do_action( 'fc_admin_settings_saved', $current_tab );

		wp_safe_redirect( add_query_arg( 'settings-updated', 'true', $this->get_settings_url( $current_tab ) ) );
		exit;
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Styles
		wp_register_style( 'fc-admin-options', FluidCheckout_Enqueue::instance()->get_style_url( 'css/admin-options' ), array(), NULL );
		wp_register_style( 'fc-admin-dashboard', FluidCheckout_Enqueue::instance()->get_style_url( 'css/admin-dashboard' ), array(), NULL );
		wp_register_style( 'fc-admin-settings', FluidCheckout_Enqueue::instance()->get_style_url( 'css/admin-settings' ), array( 'fc-admin-options' ), NULL );

		// Scripts
		wp_register_script( 'fc-admin-settings-tooltips', FluidCheckout_Enqueue::instance()->get_script_url( 'js/admin/admin-settings-tooltips' ), array(), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_register_script( 'fc-admin-settings-nav', FluidCheckout_Enqueue::instance()->get_script_url( 'js/admin/admin-settings-nav' ), array(), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_register_script( 'fc-admin-settings-colorpicker', FluidCheckout_Enqueue::instance()->get_script_url( 'js/admin/admin-settings-colorpicker' ), array( 'jquery', 'iris' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_register_script( 'fc-admin-settings-enhanced-select', FluidCheckout_Enqueue::instance()->get_script_url( 'js/admin/admin-settings-enhanced-select' ), array( 'tomselect' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-admin-settings-enhanced-select', 'window.addEventListener("load",function(){FCAdminSettingsEnhancedSelect.init();});' );
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets() {
		// Styles
		wp_enqueue_style( 'fc-admin-options' );
		wp_enqueue_style( 'fc-admin-settings' );
		wp_enqueue_style( 'fc-admin-dashboard' );
		wp_enqueue_style( 'wp-color-picker' );

		// Scripts
		wp_enqueue_script( 'iris' );
		wp_enqueue_script( 'fc-admin-settings-tooltips' );
		wp_enqueue_script( 'fc-admin-settings-nav' );
		wp_enqueue_script( 'fc-admin-settings-colorpicker' );
		wp_enqueue_script( 'fc-admin-settings-enhanced-select' );
	}

	/**
	 * Maybe enqueue assets.
	 *
	 * @param  string  $hook_suffix  Hook suffix for the current admin page.
	 */
	public function maybe_enqueue_assets( $hook_suffix ) {
		// Bail if not on the settings page
		if ( $hook_suffix !== $this->page_hook_suffix ) { return; }

		$this->enqueue_assets();
	}

	/**
	 * Maybe register the license key field assets for the License Keys tab.
	 * The license key field type is provided by licensed products.
	 * Assets are registered on every settings page load because all tabs are rendered at once.
	 *
	 * @param  string  $hook_suffix  Hook suffix for the current admin page.
	 */
	public function maybe_register_license_key_assets( $hook_suffix ) {
		// Bail if not on the settings page
		if ( $hook_suffix !== $this->page_hook_suffix ) { return; }

		// Bail if the license key field type is not available
		if ( ! class_exists( 'FluidCheckout_Admin_SettingType_LicenseKey' ) ) { return; }

		// The license key field type only registers its assets for the WooCommerce settings page hook
		FluidCheckout_Admin_SettingType_LicenseKey::instance()->register_scripts( 'woocommerce_page_wc-settings' );
	}



	/**
	 * Output the settings page.
	 * All tabs are rendered in the same form so navigation can switch panels without a page reload.
	 */
	public function output_page() {
		// Bail if user does not have enough permissions
		if ( ! current_user_can( self::CAPABILITY ) ) { return; }

		$tabs = $this->get_tabs();
		$current_tab = $this->get_current_tab();
		$can_save = ! empty( $tabs[ $current_tab ][ 'show_save_button' ] );
		?>
		<div class="wrap woocommerce fc-wrap fc-settings-wrap">
			<form method="post" action="<?php echo esc_url( $this->get_settings_url( $current_tab ) ); ?>" id="mainform" class="fc-settings-form" enctype="multipart/form-data" data-fc-settings-form>
				<div class="fc-settings-layout">

					<div class="fc-settings-sidebar">
						<div class="fc-settings-sidebar__inner">
							<div class="fc-settings-sidebar-header">
								<h1 class="fc-settings-sidebar-header__title"><?php echo esc_html( __( 'Settings', 'fluid-checkout' ) ); ?></h1>
							</div>
							<?php $this->output_sidebar_nav( $tabs, $current_tab ); ?>
						</div>
					</div>

					<div class="fc-settings-content">
						<hr class="wp-header-end">

						<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
						<?php if ( isset( $_GET[ 'settings-updated' ] ) ) : ?>
							<div class="notice notice-success is-dismissible"><p><?php echo esc_html( __( 'Your settings have been saved.', 'fluid-checkout' ) ); ?></p></div>
						<?php endif; ?>

						<?php foreach ( $tabs as $tab => $tab_args ) : ?>
							<?php
							// Skip separators
							if ( 'separator' === $tab_args[ 'type' ] ) { continue; }

							$is_active = $tab === $current_tab;
							?>
							<div
								class="fc-settings-tab fc-settings-tab--<?php echo esc_attr( $tab ); ?><?php echo $is_active ? ' is-active' : ''; ?>"
								data-fc-settings-tab="<?php echo esc_attr( $tab ); ?>"
								data-fc-settings-show-save="<?php echo $tab_args[ 'show_save_button' ] ? 'yes' : 'no'; ?>"
							>
								<h2 class="fc-settings-content__title"><?php echo esc_html( $tab_args[ 'label' ] ); ?></h2>

								<?php
								FluidCheckout_Admin_Settings_Renderer::instance()->output_fields( $this->get_tab_settings( $tab ) );

								/**
								 * Output additional content for a tab of the Fluid Checkout settings page.
								 */
								do_action( 'fc_admin_settings_tab_' . $tab );
								?>
							</div>
						<?php endforeach; ?>

						<input type="hidden" name="fc_settings_action" value="save">
						<?php wp_nonce_field( 'fc_settings_save', 'fc_settings_nonce' ); ?>

						<p class="fc-settings-submit submit" data-fc-settings-submit <?php echo $can_save ? '' : 'hidden'; ?>>
							<button type="submit" class="fc-header__button fc-header__button--save fc-settings-submit__button" data-fc-settings-save <?php disabled( ! $can_save ); ?>><?php echo esc_html( __( 'Save settings', 'fluid-checkout' ) ); ?></button>
						</p>
					</div>

				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Output the sidebar navigation.
	 *
	 * @param  array   $tabs         Settings tabs.
	 * @param  string  $current_tab  Current settings tab slug.
	 */
	public function output_sidebar_nav( $tabs, $current_tab ) {
		?>
		<ul class="fc-settings-nav" data-fc-settings-nav>
			<?php foreach ( $tabs as $slug => $tab ) : ?>
				<?php if ( 'separator' === $tab[ 'type' ] ) : ?>
					<li class="fc-settings-nav__separator" role="separator" aria-hidden="true"></li>
				<?php else : ?>
					<?php $is_active = $slug === $current_tab; ?>
					<li class="fc-settings-nav__item fc-settings-nav__item--<?php echo esc_attr( $slug ); ?> <?php echo $is_active ? 'is-active' : ''; ?>" data-fc-settings-nav-item="<?php echo esc_attr( $slug ); ?>">
						<a class="fc-settings-nav__link" href="<?php echo esc_url( $this->get_settings_url( $slug ) ); ?>" data-fc-settings-nav-link="<?php echo esc_attr( $slug ); ?>" <?php echo $is_active ? 'aria-current="page"' : ''; ?>>
							<span class="fc-settings-nav__marker" aria-hidden="true">&#8985;</span>
							<span class="fc-settings-nav__label"><?php echo esc_html( $tab[ 'label' ] ); ?></span>
						</a>
					</li>
				<?php endif; ?>
			<?php endforeach; ?>
		</ul>
		<?php
	}

}

FluidCheckout_Admin_Settings_Page::instance();
