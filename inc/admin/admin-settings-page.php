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
	 * Dashboard admin page slug.
	 */
	const DASHBOARD_PAGE_SLUG = 'fluid-checkout-dashboard';

	/**
	 * Design editor admin page slug (Lite promo page; same slug as PRO Design editor).
	 */
	const DESIGN_EDITOR_PAGE_SLUG = 'fluid-checkout-design-editor';

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
	 * Prefix of the notice names that store the dismissed messages of settings moved to another page.
	 */
	const MOVED_SETTING_NOTICE_PREFIX = 'moved_setting_';

	/**
	 * Settings page hook suffix, set when registering the admin menu.
	 *
	 * @var string
	 */
	private $page_hook_suffix = '';

	/**
	 * Dashboard page hook suffix, set when registering the admin menu.
	 *
	 * @var string
	 */
	private $dashboard_page_hook_suffix = '';

	/**
	 * Design editor page hook suffix, set when registering the admin menu.
	 *
	 * @var string
	 */
	private $design_editor_page_hook_suffix = '';

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
		add_action( 'admin_menu', array( $this, 'normalize_admin_submenu' ), 999 );
		add_action( 'admin_init', array( $this, 'maybe_redirect_legacy_dashboard_tab' ), 1 );
		add_action( 'admin_init', array( $this, 'maybe_handle_load_dashboard_page' ), 5 );
		add_action( 'admin_init', array( $this, 'maybe_handle_load_settings_page' ), 5 );
		add_filter( 'submenu_file', array( $this, 'maybe_set_current_submenu' ), 10, 2 );
		add_filter( 'fc_is_admin_settings_layout_page', array( $this, 'filter_is_settings_layout_page' ), 10 );

		// Save settings
		add_action( 'admin_init', array( $this, 'maybe_save_settings' ), 10 );
		add_action( 'wp_ajax_fc_settings_save', array( $this, 'ajax_save_settings' ), 10 );

		// Messages of settings moved to another page
		add_action( 'wp_ajax_fc_settings_set_moved_setting_message_dismissed', array( $this, 'ajax_set_moved_setting_message_dismissed' ), 10 );

		// Admin body class
		add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ), 10 );

		// Admin page header
		add_action( 'admin_notices', array( $this, 'maybe_output_admin_header' ), 1 ); // Before other admin notices, so the page header comes first

		// Remove WooCommerce Help tabs on this page
		add_action( 'admin_head', array( $this, 'maybe_remove_help_tabs' ), 10 );

		// Register assets
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets after shared library scripts are registered
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 20 );

		// Admin JS settings
		add_filter( 'fc_js_settings_admin', array( $this, 'add_js_settings_admin' ), 10 );
		add_action( 'admin_head', array( $this, 'maybe_output_settings_inline_script_admin' ), 5 );

		// License key field assets
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_register_license_key_assets' ), 10 );
	}



	/**
	 * Register the Fluid Checkout admin menu.
	 * Dashboard and Settings are separate admin pages under the same top-level menu.
	 */
	public function register_menu() {
		$this->dashboard_page_hook_suffix = add_menu_page(
			__( 'Fluid Checkout', 'fluid-checkout' ),
			__( 'Fluid Checkout', 'fluid-checkout' ),
			self::CAPABILITY,
			self::DASHBOARD_PAGE_SLUG,
			array( $this, 'output_dashboard_page' ),
			$this->get_menu_icon_url(),
			56
		);

		add_submenu_page( self::DASHBOARD_PAGE_SLUG, __( 'Settings', 'fluid-checkout' ), __( 'Settings', 'fluid-checkout' ), self::CAPABILITY, self::PAGE_SLUG, array( $this, 'output_page' ) );

		$this->design_editor_page_hook_suffix = add_submenu_page( self::DASHBOARD_PAGE_SLUG, __( 'Design', 'fluid-checkout' ), __( 'Design', 'fluid-checkout' ), self::CAPABILITY, self::DESIGN_EDITOR_PAGE_SLUG, array( $this, 'output_design_editor_page' ) );

		add_action( 'load-' . $this->design_editor_page_hook_suffix, array( $this, 'handle_load_design_editor_page' ), 10 );
	}

	/**
	 * Rename the auto-added Fluid Checkout submenu entry to Dashboard and drop duplicate slugs.
	 */
	public function normalize_admin_submenu() {
		global $submenu;

		// Bail if the Fluid Checkout submenu is not registered
		if ( empty( $submenu[ self::DASHBOARD_PAGE_SLUG ] ) || ! is_array( $submenu[ self::DASHBOARD_PAGE_SLUG ] ) ) {
			return;
		}

		$items = &$submenu[ self::DASHBOARD_PAGE_SLUG ];
		$dashboard_label = __( 'Dashboard', 'fluid-checkout' );
		$seen_dashboard_slug = false;

		// Iterate submenu entries
		foreach ( $items as $index => $item ) {
			// Skip malformed entries
			if ( ! is_array( $item ) || empty( $item[2] ) ) {
				continue;
			}

			// Rename the top-level duplicate entry to Dashboard
			if ( self::DASHBOARD_PAGE_SLUG === $item[2] ) {
				if ( $seen_dashboard_slug ) {
					unset( $items[ $index ] );
					continue;
				}

				$items[ $index ][0] = $dashboard_label;
				$seen_dashboard_slug = true;
			}
		}

		$submenu[ self::DASHBOARD_PAGE_SLUG ] = array_values( $items );
	}



	/**
	 * Run Dashboard page load tasks once per request.
	 */
	public function maybe_handle_load_dashboard_page() {
		// Bail if not on the Dashboard page
		if ( ! $this->is_dashboard_page() ) { return; }

		$this->handle_load_dashboard_page();
	}

	/**
	 * Run settings page load tasks once per request.
	 */
	public function maybe_handle_load_settings_page() {
		// Bail if not on the settings page
		if ( ! $this->is_settings_page() ) { return; }

		$this->handle_load_settings_page();
	}

	/**
	 * Mark getting-started checklist steps when loading the Dashboard page.
	 */
	public function handle_load_dashboard_page() {
		// Allow only Fluid Checkout admin notices on this screen (strip WooCommerce and other plugins)
		if ( class_exists( 'FluidCheckout_Admin' ) ) {
			FluidCheckout_Admin::strip_non_fc_admin_notices_on_load( true );
		}

		// Bail if user cannot manage settings
		if ( ! current_user_can( self::CAPABILITY ) ) { return; }

		// Bail if setup checklist handler is unavailable
		if ( ! class_exists( 'FluidCheckout_Admin_SettingType_Setup' ) ) { return; }

		FluidCheckout_Admin_SettingType_Setup::instance()->maybe_mark_checklist_step_from_tab( 'dashboard' );
	}

	/**
	 * Mark getting-started checklist steps when loading a mapped settings tab.
	 */
	public function handle_load_settings_page() {
		// Allow only Fluid Checkout admin notices on this screen (strip WooCommerce and other plugins)
		if ( class_exists( 'FluidCheckout_Admin' ) ) {
			FluidCheckout_Admin::strip_non_fc_admin_notices_on_load( true );
		}

		// Bail if user cannot manage settings
		if ( ! current_user_can( self::CAPABILITY ) ) { return; }

		// Bail if setup checklist handler is unavailable
		if ( ! class_exists( 'FluidCheckout_Admin_SettingType_Setup' ) ) { return; }

		FluidCheckout_Admin_SettingType_Setup::instance()->maybe_mark_checklist_step_from_tab( $this->get_current_tab() );
	}

	/**
	 * Get the admin menu icon as a base64-encoded SVG data URI for WordPress color scheme painting.
	 */
	public function get_menu_icon_url() {
		$icon_path = FluidCheckout::$directory_path . 'images/admin/menu-icon.svg';

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
	 * Get the URL for the Dashboard admin page.
	 */
	public function get_dashboard_url() {
		return add_query_arg( array( 'page' => self::DASHBOARD_PAGE_SLUG ), admin_url( 'admin.php' ) );
	}

	/**
	 * Get the admin menu slug for the Dashboard page.
	 */
	public function get_dashboard_menu_slug() {
		return 'admin.php?page=' . self::DASHBOARD_PAGE_SLUG;
	}

	/**
	 * Redirect legacy Dashboard tab URLs on the settings page to the Dashboard page.
	 */
	public function maybe_redirect_legacy_dashboard_tab() {
		// Bail if not in the admin
		if ( ! is_admin() ) { return; }

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET[ 'page' ] ) ? sanitize_text_field( wp_unslash( $_GET[ 'page' ] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET[ 'tab' ] ) ? sanitize_key( wp_unslash( $_GET[ 'tab' ] ) ) : '';

		// Bail if not the legacy Dashboard tab on the settings page
		if ( self::PAGE_SLUG !== $page || 'dashboard' !== $tab ) { return; }

		wp_safe_redirect( $this->get_dashboard_url() );
		exit;
	}

	/**
	 * Highlight the submenu item that matches the current admin page.
	 *
	 * @param  string|null  $submenu_file  The submenu file.
	 * @param  string       $parent_file   The parent file.
	 */
	public function maybe_set_current_submenu( $submenu_file, $parent_file ) {
		// Highlight the Design editor submenu item
		if ( $this->is_design_editor_page() ) {
			return self::DESIGN_EDITOR_PAGE_SLUG;
		}

		// Bail if not on a Fluid Checkout settings layout page
		if ( ! $this->is_dashboard_page() && ! $this->is_settings_page() ) { return $submenu_file; }

		// Highlight the Dashboard submenu item
		if ( $this->is_dashboard_page() ) {
			return self::DASHBOARD_PAGE_SLUG;
		}

		return self::PAGE_SLUG;
	}

	/**
	 * Include the Dashboard page in the settings layout filter.
	 *
	 * @param  bool  $is_settings_layout_page  Whether the current request uses the settings layout.
	 */
	public function filter_is_settings_layout_page( $is_settings_layout_page ) {
		return $is_settings_layout_page || $this->is_dashboard_page();
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
		if ( ! empty( $tab ) && $this->get_current_tab() !== $tab ) { return false; }

		return true;
	}

	/**
	 * Check whether the current request is for the Dashboard admin page.
	 */
	public function is_dashboard_page() {
		// Bail if not in the admin
		if ( ! is_admin() ) { return false; }

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET[ 'page' ] ) ? sanitize_text_field( wp_unslash( $_GET[ 'page' ] ) ) : '';

		return self::DASHBOARD_PAGE_SLUG === $page;
	}

	/**
	 * Check whether the current request is for the Design editor admin page.
	 */
	public function is_design_editor_page() {
		// Bail if not in the admin
		if ( ! is_admin() ) { return false; }

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET[ 'page' ] ) ? sanitize_text_field( wp_unslash( $_GET[ 'page' ] ) ) : '';

		return self::DESIGN_EDITOR_PAGE_SLUG === $page;
	}

	/**
	 * Whether the settings sidebar should show the Design accordion (Lite without PRO).
	 */
	public function should_show_design_editor_sidebar_accordion() {
		return true;
	}

	/**
	 * Admin URL for the Design editor page.
	 *
	 * @param  string  $tab  Optional. Design editor group slug, or `design_editor` for the Lite promo tab.
	 */
	public function get_design_editor_url( $tab = '' ) {
		$args = array( 'page' => self::DESIGN_EDITOR_PAGE_SLUG );

		if ( '' !== $tab && 'design_editor' !== $tab ) {
			$args[ 'tab' ] = $tab;
		}

		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * Output the Design admin page (PRO Design editor or Lite promo).
	 */
	public function output_design_editor_page() {
		if ( FluidCheckout::instance()->is_pro_activated() && class_exists( 'FluidCheckout_PRO_Admin_DesignEditor' ) ) {
			FluidCheckout_PRO_Admin_DesignEditor::instance()->output_page();
			return;
		}

		if ( class_exists( 'FluidCheckout_Admin_DesignEditorPromo' ) ) {
			FluidCheckout_Admin_DesignEditorPromo::instance()->output_page();
		}
	}

	/**
	 * Allow only Fluid Checkout admin notices on the Design page (strip WooCommerce and other plugins).
	 */
	public function handle_load_design_editor_page() {
		if ( ! class_exists( 'FluidCheckout_Admin' ) ) { return; }

		FluidCheckout_Admin::strip_non_fc_admin_notices_on_load( true );
	}

	/**
	 * Check whether the current request is for an admin page with the settings page layout.
	 */
	public function is_settings_layout_page() {
		/**
		 * Filters whether the current request is for an admin page with the settings page layout.
		 *
		 * @since 5.0.0
		 */
		return true === apply_filters( 'fc_is_admin_settings_layout_page', $this->is_settings_page() );
	}

	/**
	 * Add a body class on the Fluid Checkout settings page.
	 *
	 * @param  string  $classes  Space-separated list of admin body classes.
	 */
	public function add_admin_body_class( $classes ) {
		// Bail if not on the settings or Dashboard page
		if ( ! $this->is_settings_page() && ! $this->is_dashboard_page() ) { return $classes; }

		$classes .= ' fc-settings-page';

		return $classes;
	}

	/**
	 * Remove WooCommerce Help tabs from the settings page.
	 */
	public function maybe_remove_help_tabs() {
		// Bail if not on the settings or Dashboard page
		if ( ! $this->is_settings_page() && ! $this->is_dashboard_page() ) { return; }

		$screen = get_current_screen();

		// Bail if the current screen is not available
		if ( ! $screen ) { return; }

		$screen->remove_help_tabs();
	}

	/**
	 * Output the upgrade and save settings header action buttons.
	 *
	 * @param  bool  $can_save  Whether the save button should be enabled.
	 */
	public function output_settings_header_action_buttons( $can_save ) {
		$upgrade_url = 'https://fluidcheckout.com/pricing/?mtm_campaign=admin-header&mtm_kwd=upgrade-pro&mtm_source=settings-header';

		// Maybe output the upgrade button when PRO is not active
		if ( ! FluidCheckout::instance()->is_pro_activated() ) : ?>
			<a class="fc-header__button fc-header__button--upgrade" href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener noreferrer">
				<span class="dashicons dashicons-upload" aria-hidden="true"></span><?php echo esc_html( __( 'Upgrade to PRO', 'fluid-checkout' ) ); ?>
			</a>
		<?php endif; ?>
		<button
			type="submit"
			form="mainform"
			class="fc-header__button fc-header__button--save"
			data-fc-settings-save
			data-fc-settings-shortcut="save"
			<?php disabled( ! $can_save ); ?>
		><?php echo esc_html( __( 'Save settings', 'fluid-checkout' ) ); ?><span class="fc-settings-shortcut" data-fc-settings-shortcut-label aria-hidden="true"></span></button>
		<?php
	}

	/**
	 * Maybe output the Fluid Checkout admin page header on FC settings layout screens.
	 */
	public function maybe_output_admin_header() {
		if ( $this->is_design_editor_page() ) {
			$can_save = false;

			if ( FluidCheckout::instance()->is_pro_activated() && class_exists( 'FluidCheckout_PRO_Admin_DesignEditor' ) ) {
				$design_editor = FluidCheckout_PRO_Admin_DesignEditor::instance();
				$tabs          = $design_editor->get_tabs();
				$current_tab   = $design_editor->get_current_tab();
				$can_save      = ! empty( $tabs[ $current_tab ]['show_save_button'] );
			}

			$this->output_admin_header( $can_save );
			return;
		}

		$this->output_admin_header();
	}



	/**
	 * Output the Fluid Checkout admin page header.
	 *
	 * @param  bool|null  $can_save  Whether the save button should be enabled. Null derives it from the current settings tab.
	 */
	public function output_admin_header( $can_save = null ) {
		// Bail if not on a Fluid Checkout admin screen that uses this header (settings, Design editor, etc.)
		if ( ! $this->is_settings_layout_page() ) {
			return;
		}

		if ( null === $can_save ) {
			// Bail if not on the settings or Dashboard page
			if ( ! $this->is_settings_page() && ! $this->is_dashboard_page() ) {
				return;
			}

			// Dashboard is read-only in the layout
			if ( $this->is_dashboard_page() ) {
				$can_save = false;
			}
			else {
				$tabs        = $this->get_tabs();
				$current_tab = $this->get_current_tab();
				$can_save    = ! empty( $tabs[ $current_tab ]['show_save_button'] );
			}
		}

		$this->render_fc_admin_header(
			function () use ( $can_save ) {
				$this->output_settings_header_action_buttons( $can_save );
			}
		);
	}



	/**
	 * Output the shared admin header on a guided setup screen.
	 *
	 * @param FluidCheckout_Admin_GuidedSetup $guided_setup Guided setup instance.
	 */
	public function output_admin_header_for_guided_setup( $guided_setup ) {
		if ( ! is_object( $guided_setup ) || ! method_exists( $guided_setup, 'is_guided_setup_page' ) || ! $guided_setup->is_guided_setup_page() ) {
			return;
		}

		$this->render_fc_admin_header(
			function () use ( $guided_setup ) {
				$guided_setup->output_guided_setup_header_action_links();
			},
			false
		);
	}



	/**
	 * Render the Fluid Checkout admin header shell and trailing action controls.
	 *
	 * @param callable $output_action_buttons     Callback that echoes header action markup.
	 * @param bool     $show_support_and_docs     Whether to output Support and Docs links.
	 */
	private function render_fc_admin_header( callable $output_action_buttons, $show_support_and_docs = true ) {
		$logo_url    = FluidCheckout::$directory_url . 'images/admin/fluid-checkout-checkmark-logo.svg';
		$home_url    = 'https://fluidcheckout.com/?mtm_campaign=admin-header&mtm_kwd=logo&mtm_source=settings-header';
		$support_url = 'https://fluidcheckout.com/support/?mtm_campaign=admin-header&mtm_kwd=support&mtm_source=settings-header';
		$docs_url    = 'https://fluidcheckout.com/docs/?mtm_campaign=admin-header&mtm_kwd=docs&mtm_source=settings-header';
		?>
		<div id="fc-header">
			<div class="fc-header__notices" data-fc-settings-notices aria-live="polite"></div>
			<div class="fc-header__branding">
				<a class="fc-header__logo" href="<?php echo esc_url( $home_url ); ?>" target="_blank" rel="noopener noreferrer">
					<img class="fc-header__logo-icon" src="<?php echo esc_url( $logo_url ); ?>" alt="" />
					<span class="fc-header__logo-text"><?php echo esc_html( __( 'Fluid Checkout', 'fluid-checkout' ) ); ?></span>
				</a>
				<?php
				/**
				 * Filters HTML beside the settings header logo link (not inside the link).
				 *
				 * @since 5.0.0
				 *
				 * @param string $html Empty by default.
				 */
				echo wp_kses(
					apply_filters( 'fc_settings_header_logo_after_title', '' ),
					array(
						'span' => array(
							'class' => true,
						),
					)
				);
				?>
			</div>

			<?php if ( $show_support_and_docs ) : ?>
				<a class="fc-header__button" href="<?php echo esc_url( $support_url ); ?>" target="_blank" rel="noopener noreferrer">
					<span class="dashicons dashicons-email-alt" aria-hidden="true"></span><?php echo esc_html( __( 'Support', 'fluid-checkout' ) ); ?>
				</a>
				<a class="fc-header__button" href="<?php echo esc_url( $docs_url ); ?>" target="_blank" rel="noopener noreferrer">
					<span class="dashicons dashicons-book" aria-hidden="true"></span><?php echo esc_html( __( 'Docs', 'fluid-checkout' ) ); ?>
				</a>
			<?php endif; ?>
			<?php
			$output_action_buttons();
			?>
		</div>
		<?php
	}



	/**
	 * Get the settings tabs.
	 * Settings sections registered by other plugins which are not part of the default tabs are added as extra tabs.
	 */
	public function get_tabs() {
		// Maybe return cached tabs
		if ( null !== $this->tabs ) { return $this->tabs; }

		$tabs = array(
			'checkout'                 => array( 'label' => __( 'Checkout', 'fluid-checkout' ), 'section' => 'checkout' ),
			'cart'                     => array( 'label' => __( 'Cart', 'fluid-checkout' ), 'section' => 'cart' ),
			'order_received'           => array( 'label' => __( 'Thank you', 'fluid-checkout' ), 'section' => 'order_received' ),
			'order_pay'                => array( 'label' => __( 'Order pay', 'fluid-checkout' ), 'section' => 'order_pay' ),
			'separator'                => array( 'type' => 'separator' ),
			'address_autocomplete'     => array( 'label' => __( 'Address autocomplete', 'fluid-checkout' ), 'section' => 'address_autocomplete' ),
			'address_book'             => array( 'label' => __( 'Address book', 'fluid-checkout' ), 'section' => 'address_book' ),
			'express_checkout'         => array( 'label' => __( 'Express checkout', 'fluid-checkout' ), 'section' => 'express_checkout' ),
			'customer_accounts'        => array( 'label' => __( 'Customer accounts', 'fluid-checkout' ), 'section' => 'customer_accounts' ),
			'local_pickup'             => array( 'label' => __( 'Local pickup', 'fluid-checkout' ), 'section' => 'local_pickup' ),
			'gift_options'             => array( 'label' => __( 'Gift options', 'fluid-checkout' ), 'section' => 'gift_options' ),
			'international_phone'      => array( 'label' => __( 'Phone fields', 'fluid-checkout' ), 'section' => 'international_phone' ),
			'trust_symbols'            => array( 'label' => __( 'Trust badges', 'fluid-checkout' ), 'section' => 'trust_symbols' ),
			'vat_assistant'            => array( 'label' => __( 'EU-VAT Assistant', 'fluid-checkout' ), 'section' => 'vat_assistant' ),
		);
		$tabs_after = array(
			'separator_3'              => array( 'type' => 'separator' ),
			'integrations'             => array( 'label' => __( 'Integrations', 'fluid-checkout' ), 'section' => 'integrations' ),
			'tools'                    => array( 'label' => __( 'Tools', 'fluid-checkout' ), 'section' => 'tools' ),
			'license_keys'             => array( 'label' => __( 'License keys', 'fluid-checkout' ), 'section' => 'license_keys' ),
		);

		/**
		 * Filters settings sections registered for the Fluid Checkout settings page.
		 * Sections that are not already a built-in tab are added as extra tabs.
		 *
		 * @since 5.0.0
		 */
		$sections = apply_filters( 'fc_admin_settings_sections', array() );
		$sections = is_array( $sections ) ? $sections : array();

		// Collect section slugs already claimed by the fixed tabs (skip separators)
		$known_sections = array( '' );
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

			$tab_slug = sanitize_key( $section );

			// Skip the Dashboard section, it has its own admin page and accordion
			if ( '' === $tab_slug || 'dashboard' === $tab_slug ) { continue; }

			$tabs[ $tab_slug ] = array( 'label' => $label, 'section' => $section );
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
	 * Tab arguments for the Dashboard admin page panel.
	 */
	public function get_dashboard_tab() {
		return array(
			'type'             => 'tab',
			'label'            => __( 'Dashboard', 'fluid-checkout' ),
			'section'          => '',
			'show_save_button' => false,
		);
	}

	/**
	 * Check whether a tab slug is the Dashboard panel on the Dashboard admin page.
	 *
	 * @param  string  $tab  Tab slug.
	 */
	public function is_dashboard_tab( $tab ) {
		return 'dashboard' === $tab;
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
		$section = '';

		// Get the Dashboard section when rendering the Dashboard page panel
		if ( $this->is_dashboard_tab( $tab ) ) {
			$section = $this->get_dashboard_tab()[ 'section' ];
		}
		// Bail if tab is not valid
		elseif ( ! $this->is_valid_tab( $tab ) ) { return array(); }
		else {
			$tabs = $this->get_tabs();
			$section = $tabs[ $tab ][ 'section' ];
		}

		/**
		 * Filters the settings fields for one tab of the Fluid Checkout settings page.
		 *
		 * @since 5.0.0
		 *
		 * @param  string  $section  Settings section slug. An empty string is the dashboard section.
		 */
		$settings = apply_filters( 'fc_admin_settings', array(), $section );

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
	public function get_saveable_settings( $settings, $data = null ) {
		if ( null === $data ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$data = $_POST;
		}

		return FluidCheckout_Admin_Settings_Access::instance()->get_saveable_settings( $settings, $data );
	}

	/**
	 * Get the names of the fields in the form that sent the request, including unchecked checkboxes.
	 *
	 * @param  array  $post_data  Request data, with the field names the settings scripts send.
	 */
	public function get_posted_field_names( $post_data ) {
		// Bail if the request has no field names
		if ( ! is_array( $post_data ) || ! isset( $post_data[ 'fc_settings_field_names' ] ) || ! is_array( $post_data[ 'fc_settings_field_names' ] ) ) { return array(); }

		return preg_replace( '/\[\]$/', '', array_map( 'sanitize_text_field', wp_unslash( $post_data[ 'fc_settings_field_names' ] ) ) );
	}

	/**
	 * Get the settings of the fields in a form, so a form with some of the settings does not unset the others.
	 * Without field names, gets all settings except those moved to another page, as the settings page shows a message in their place.
	 *
	 * @param  array  $settings     Settings arrays.
	 * @param  array  $field_names  Names of the fields in the form.
	 */
	public function get_settings_in_form( $settings, $field_names ) {
		// Maybe keep all settings except those moved to another page, when the form does not send its field names
		if ( empty( $field_names ) ) {
			$settings = array_filter( $settings, function( $setting ) {
				return ! $this->is_settings_field_moved( $setting );
			} );
		}
		// Otherwise, keep the settings of the fields in the form
		else {
			$settings = array_filter( $settings, function( $setting ) use ( $field_names ) {
				return isset( $setting[ 'id' ] ) && in_array( isset( $setting[ 'field_name' ] ) ? $setting[ 'field_name' ] : $setting[ 'id' ], $field_names, true );
			} );
		}

		return $settings;
	}

	/**
	 * Get the settings tab slug active when saving settings.
	 *
	 * @param  array  $post_data  Request data from a settings save.
	 */
	public function get_current_tab_from_save_request( $post_data ) {
		if ( is_array( $post_data ) && ! empty( $post_data[ 'fc_settings_current_tab' ] ) ) {
			$tab = sanitize_key( $post_data[ 'fc_settings_current_tab' ] );

			if ( $this->is_valid_tab( $tab ) ) {
				return $tab;
			}
		}

		return $this->get_current_tab();
	}

	/**
	 * Save settings from a POST request.
	 * All tab panels are rendered in one form, so every saveable tab is saved together. Forms of other admin pages with some of the settings only save those.
	 *
	 * @param  array  $post_data  Request data from a settings save.
	 */
	public function save_settings_from_request( $post_data ) {
		$current_tab = $this->get_current_tab_from_save_request( $post_data );
		$tabs = $this->get_tabs();

		// Get the names of the fields in the form, empty when the request does not send them
		$field_names = $this->get_posted_field_names( $post_data );

		foreach ( $tabs as $tab => $tab_args ) {
			// Skip separators and tabs without settings to save
			if ( 'tab' !== $tab_args[ 'type' ] || ! $tab_args[ 'show_save_button' ] ) { continue; }

			// Get the settings of the tab that are in the form
			$settings = $this->get_settings_in_form( $this->get_saveable_settings( $this->get_tab_settings( $tab ), $post_data ), $field_names );

			// Skip tabs without settings in a form that sends its field names
			if ( ! empty( $field_names ) && empty( $settings ) ) { continue; }

			// Save the settings of the tab
			FluidCheckout_Settings::instance()->save_settings( $settings );

			// Run the section save hook
			if ( '' !== $tab_args[ 'section' ] ) {
				do_action( 'woocommerce_update_options_' . self::WC_SETTINGS_TAB_ID . '_' . $tab_args[ 'section' ] );

				/**
				 * Fires after one settings section is saved on the Fluid Checkout settings page.
				 *
				 * The dynamic portion of the hook name, `$section`, refers to the settings section slug.
				 * Possible hook names include:
				 *
				 * - `fc_admin_settings_update_checkout`
				 * - `fc_admin_settings_update_cart`
				 * - `fc_admin_settings_update_local_pickup`
				 *
				 * @since 5.0.0
				 */
				do_action( 'fc_admin_settings_update_' . $tab_args[ 'section' ] );
			}
		}

		/**
		 * After settings are saved on the Fluid Checkout settings page.
		 *
		 * @param  string  $tab  Settings tab slug that was active when saving.
		 */
		do_action( 'fc_admin_settings_saved', $current_tab );
	}

	/**
	 * Maybe save settings for all saveable tabs (non-JavaScript fallback).
	 */
	public function maybe_save_settings() {
		// Bail if not on the settings page
		if ( ! $this->is_settings_page() ) { return; }

		// Bail if not a save request
		if ( ! isset( $_POST[ 'fc_settings_action' ] ) || 'save' !== sanitize_key( wp_unslash( $_POST[ 'fc_settings_action' ] ) ) ) { return; }

		// Bail if user does not have enough permissions
		if ( ! current_user_can( self::CAPABILITY ) ) { return; }

		check_admin_referer( 'fc_settings_save', 'fc_settings_nonce' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$this->save_settings_from_request( $_POST );

		$current_tab = $this->get_current_tab_from_save_request( $_POST );

		wp_safe_redirect( add_query_arg( 'settings-updated', 'true', $this->get_settings_url( $current_tab ) ) );
		exit;
	}

	/**
	 * Save settings via AJAX.
	 */
	public function ajax_save_settings() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to save these settings.', 'fluid-checkout' ),
				),
				403
			);
		}

		check_ajax_referer( 'fc_settings_save', 'fc_settings_nonce' );

		if ( ! isset( $_POST[ 'fc_settings_action' ] ) || 'save' !== sanitize_key( wp_unslash( $_POST[ 'fc_settings_action' ] ) ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid save request.', 'fluid-checkout' ),
				)
			);
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$this->save_settings_from_request( $_POST );

		$response_data = array(
			'message' => __( 'Your settings have been saved.', 'fluid-checkout' ),
		);

		if ( class_exists( 'FluidCheckout_Admin_SettingType_Setup' ) ) {
			$response_data['checklist_state'] = FluidCheckout_Admin_SettingType_Setup::instance()->get_checklist_steps_completion_state();
		}

		wp_send_json_success( $response_data );
	}



	/**
	 * Move a settings field to another page, so the settings page shows a message with a link to its new place instead of the field.
	 * Other pages that show the field, such as its new place, still show and save it.
	 *
	 * @param  array   $settings     Admin settings argument values.
	 * @param  string  $option_name  Option name of the settings field to move.
	 * @param  array   $moved        Where the settings field moved: the `url` of its new place, and optionally the `text` of the message, with `%s` for the link, and the `link_text`.
	 */
	public function move_settings_field( $settings, $option_name, $moved ) {
		// Bail if the settings arguments are not valid
		if ( ! is_array( $settings ) ) { return $settings; }

		// Iterate settings
		foreach ( $settings as $key => $setting_args ) {
			// Skip settings other than the target settings field
			if ( ! is_array( $setting_args ) || ! array_key_exists( 'id', $setting_args ) || $option_name !== $setting_args[ 'id' ] ) { continue; }

			// Move the settings field
			$settings[ $key ][ 'moved' ] = $moved;
		}

		return $settings;
	}

	/**
	 * Check whether a settings field was moved to another page.
	 *
	 * @param  array  $setting_args  Settings field arguments.
	 */
	public function is_settings_field_moved( $setting_args ) {
		return is_array( $setting_args ) && ! empty( $setting_args[ 'moved' ][ 'url' ] );
	}

	/**
	 * Check whether the message of a setting moved to another page was dismissed.
	 *
	 * @param  string  $option_name  Option name of the setting.
	 */
	public function is_moved_setting_message_dismissed( $option_name ) {
		return FluidCheckout_AdminNotices::instance()->is_dismissed( self::MOVED_SETTING_NOTICE_PREFIX . $option_name );
	}

	/**
	 * Dismiss the message of a setting moved to another page, or show it again, through admin AJAX.
	 */
	public function ajax_set_moved_setting_message_dismissed() {
		// Bail if user does not have enough permissions
		if ( ! current_user_can( self::CAPABILITY ) ) { wp_send_json_error( null, 403 ); }

		// Check the nonce
		check_ajax_referer( 'fc_settings_set_moved_setting_message_dismissed', 'nonce' );

		// Get the option name of the setting, and whether to dismiss its message or show it again
		$option_name = isset( $_POST[ 'option_name' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'option_name' ] ) ) : '';
		$is_dismissed = isset( $_POST[ 'dismissed' ] ) && 'yes' === sanitize_text_field( wp_unslash( $_POST[ 'dismissed' ] ) );

		// Iterate settings tabs, to only change the messages of settings moved to another page
		foreach ( array_keys( $this->get_tabs() ) as $tab ) {
			// Iterate settings of the tab
			foreach ( $this->get_tab_settings( $tab ) as $setting ) {
				// Skip other settings, and settings that did not move
				if ( ! isset( $setting[ 'id' ] ) || $option_name !== $setting[ 'id' ] || ! $this->is_settings_field_moved( $setting ) ) { continue; }

				// Dismiss the message, or show it again
				FluidCheckout_AdminNotices::instance()->set_dismissed( self::MOVED_SETTING_NOTICE_PREFIX . $option_name, $is_dismissed );
				wp_send_json_success();
			}
		}

		// Send the error for settings that did not move
		wp_send_json_error();
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
		wp_register_script( 'fc-admin-settings-setup-checklist', FluidCheckout_Enqueue::instance()->get_script_url( 'js/admin/admin-settings-setup-checklist' ), array( 'fc-utils' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-admin-settings-setup-checklist', '(function(){var i=function(){if(window.fcSettingsAdmin&&fcSettingsAdmin.setupChecklist){FCAdminSettingsSetupChecklist.init(fcSettingsAdmin.setupChecklist);}};if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",i);}else{i();}})();' );
		wp_register_script( 'fc-settings-page', FluidCheckout_Enqueue::instance()->get_script_url( 'js/admin/fc-settings-page' ), array( 'fc-utils', 'fc-admin-settings-setup-checklist' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-settings-page', '(function(){var i=function(){if(window.fcSettingsAdmin&&fcSettingsAdmin.settingsPage){FCSettingsPage.init(fcSettingsAdmin.settingsPage);}};if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",i);}else{i();}})();' );
		wp_register_script( 'fc-admin-settings-tooltips', FluidCheckout_Enqueue::instance()->get_script_url( 'js/admin/admin-settings-tooltips' ), array( 'fc-utils' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-admin-settings-tooltips', '(function(){var i=function(){FCAdminSettingsTooltips.init();};if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",i);}else{i();}})();' );
		wp_register_script( 'fc-admin-settings-nav', FluidCheckout_Enqueue::instance()->get_script_url( 'js/admin/admin-settings-nav' ), array( 'fc-utils', 'fc-admin-settings-setup-checklist' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-admin-settings-nav', '(function(){var i=function(){FCAdminSettingsNav.init();};if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",i);}else{i();}})();' );
		wp_register_script( 'fc-admin-settings-colorpicker', FluidCheckout_Enqueue::instance()->get_script_url( 'js/admin/admin-settings-colorpicker' ), array( 'fc-utils', 'jquery', 'iris' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-admin-settings-colorpicker', '(function(){var i=function(){FCAdminSettingsColorPicker.init();};if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",i);}else{i();}})();' );
		wp_register_script( 'fc-admin-settings-enhanced-select', FluidCheckout_Enqueue::instance()->get_script_url( 'js/admin/admin-settings-enhanced-select' ), array( 'tomselect' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-admin-settings-enhanced-select', 'window.addEventListener("load",function(){FCAdminSettingsEnhancedSelect.init();});' );
		wp_register_script( 'fc-admin-settings-css-value', FluidCheckout_Enqueue::instance()->get_script_url( 'js/admin/admin-settings-css-value' ), array(), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-admin-settings-css-value', '(function(){var i=function(){FCAdminSettingsCssValue.init();};if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",i);}else{i();}})();' );
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
		wp_enqueue_script( 'fc-admin-settings-setup-checklist' );
		wp_enqueue_script( 'fc-settings-page' );
		wp_enqueue_script( 'fc-admin-settings-tooltips' );
		wp_enqueue_script( 'fc-admin-settings-nav' );
		wp_enqueue_script( 'fc-admin-settings-colorpicker' );
		wp_enqueue_script( 'fc-admin-settings-enhanced-select' );
		wp_enqueue_script( 'fc-admin-settings-css-value' );
	}

	/**
	 * Maybe enqueue assets.
	 *
	 * @param  string  $hook_suffix  Hook suffix for the current admin page.
	 */
	public function maybe_enqueue_assets( $_hook_suffix ) {
		// Bail if not on the settings or Dashboard page
		if ( ! $this->is_settings_page() && ! $this->is_dashboard_page() ) { return; }

		$this->enqueue_assets();
	}

	/**
	 * Add settings page script settings to `fcSettingsAdmin`.
	 *
	 * @param   array  $settings  Admin JS settings.
	 */
	public function add_js_settings_admin( $settings ) {
		// Bail if not on an admin page with the settings page layout
		if ( ! $this->is_settings_layout_page() ) { return $settings; }

		// Maybe add the setup checklist settings on the settings and Dashboard pages
		if ( $this->is_settings_page() || $this->is_dashboard_page() ) {
			$settings[ 'setupChecklist' ] = array(
				'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
				'syncTabAction'      => 'fc_setup_checklist_mark_tab',
				'nonce'              => wp_create_nonce( 'fc_setup_checklist_mark_tab' ),
				'tabToChecklistStep' => class_exists( 'FluidCheckout_Admin_SettingType_Setup' ) ? FluidCheckout_Admin_SettingType_Setup::instance()->get_checklist_tab_to_step_map() : array(),
				'i18n'               => array(
					'completed' => __( 'Completed', 'fluid-checkout' ),
				),
			);
		}

		$settings[ 'settingsPage' ] = array(
			'ajaxUrl'             => admin_url( 'admin-ajax.php' ),
			'saveAction'          => 'fc_settings_save',
			'movedMessageNonce'   => wp_create_nonce( 'fc_settings_set_moved_setting_message_dismissed' ),
			// Copy for AJAX save and floating header notices on the settings page
			'i18n'                => array(
				'saving'               => __( 'Saving...', 'fluid-checkout' ),
				'save'                 => __( 'Save settings', 'fluid-checkout' ),
				'saved'                => __( 'Your settings have been saved.', 'fluid-checkout' ),
				'saveError'            => __( 'Settings could not be saved. Please try again.', 'fluid-checkout' ),
				'dismissNotice'        => __( 'Dismiss notice', 'fluid-checkout' ), // aria-label on the floating header notice dismiss button
				'movedMessageError'    => __( 'The change could not be saved. Please try again.', 'fluid-checkout' ),
			),
		);

		return $settings;
	}

	/**
	 * Maybe output `fcSettingsAdmin` on admin pages with the settings page layout.
	 */
	public function maybe_output_settings_inline_script_admin() {
		// Bail if not on an admin page with the settings page layout
		if ( ! $this->is_settings_layout_page() ) { return; }

		FluidCheckout_Enqueue::instance()->output_settings_inline_script_admin();
	}

	/**
	 * Maybe register the license key field assets for the License Keys tab.
	 * The license key field type is provided by licensed products.
	 * Assets are registered on every settings page load because all tabs are rendered at once.
	 *
	 * @param  string  $hook_suffix  Hook suffix for the current admin page.
	 */
	public function maybe_register_license_key_assets( $_hook_suffix ) {
		// Bail if not on the settings page
		if ( ! $this->is_settings_page() ) { return; }

		// Bail if the license key field type is not available
		if ( ! class_exists( 'FluidCheckout_Admin_SettingType_LicenseKey' ) ) { return; }

		FluidCheckout_Admin_SettingType_LicenseKey::instance()->register_scripts();
	}



	/**
	 * Output the Dashboard admin page.
	 */
	public function output_dashboard_page() {
		// Bail if user does not have enough permissions
		if ( ! current_user_can( self::CAPABILITY ) ) { return; }

		$current_tab = 'dashboard';

		$this->output_layout( array(
			'title'               => __( 'Settings', 'fluid-checkout' ),
			'screen_reader_title' => __( 'Dashboard', 'fluid-checkout' ),
			'form_url'            => $this->get_dashboard_url(),
			'tabs'                => array(
				'dashboard' => $this->get_dashboard_tab(),
			),
			'nav_tabs'            => $this->get_tabs(),
			'current_tab'         => $current_tab,
		) );
	}

	/**
	 * Output the settings page.
	 * All tabs are rendered in the same form so navigation can switch panels without a page reload.
	 */
	public function output_page() {
		// Bail if user does not have enough permissions
		if ( ! current_user_can( self::CAPABILITY ) ) { return; }

		// Get current tab
		$current_tab = $this->get_current_tab();

		// Output the settings page layout
		$this->output_layout( array(
			'title'               => __( 'Settings', 'fluid-checkout' ),
			'screen_reader_title' => __( 'Settings', 'fluid-checkout' ),
			'form_url'            => $this->get_settings_url( $current_tab ),
			'tabs'                => $this->get_tabs(),
			'current_tab'         => $current_tab,
		) );
	}

	/**
	 * Output the settings page layout, with the sidebar navigation, the tab panels in one form and the page preview.
	 *
	 * @param  array  $args  Layout arguments: `title` of the sidebar, `form_url`, `tabs` (like the settings tabs, optionally with a `title` shown above their contents instead of their `label`), `current_tab`, `nav_callback` that outputs the navigation for the tabs and current tab, and `tab_content_callback` that outputs the contents of a tab. The callbacks default to those of the settings page.
	 */
	public function output_layout( $args ) {
		// Get layout arguments
		$args = wp_parse_args( $args, array(
			'title'                    => '',
			'screen_reader_title'      => '',
			'form_url'                 => '',
			'tabs'                     => array(),
			'nav_tabs'                 => array(),
			'current_tab'              => '',
			'nav_callback'             => array( $this, 'output_sidebar_nav' ),
			'tab_content_callback'     => array( $this, 'output_tab_content' ),
		) );

		// Get tabs, and whether the current tab can be saved and has the page preview
		$tabs = $args[ 'tabs' ];
		$nav_tabs = ! empty( $args[ 'nav_tabs' ] ) ? $args[ 'nav_tabs' ] : $tabs;
		$current_tab = $args[ 'current_tab' ];
		$screen_reader_title = ! empty( $args[ 'screen_reader_title' ] ) ? $args[ 'screen_reader_title' ] : $args[ 'title' ];
		$can_save = ! empty( $tabs[ $current_tab ][ 'show_save_button' ] );
		$has_preview = class_exists( 'FluidCheckout_Admin_Settings_Preview' ) && FluidCheckout_Admin_Settings_Preview::instance()->is_preview_visible_for_tab( $current_tab );
		$layout_class = 'fc-settings-layout';
		$layout_class .= $has_preview ? ' has-preview' : '';
		?>
		<div class="wrap woocommerce fc-wrap fc-settings-wrap">
			<h1 class="fc-settings-page-title screen-reader-text"><?php echo esc_html( $screen_reader_title ); ?></h1>
			<?php // Stop browsers from restoring unsaved values when going back to the page, as the page scripts start from the saved settings ?>
			<form method="post" action="<?php echo esc_url( $args[ 'form_url' ] ); ?>" id="mainform" class="fc-settings-form" enctype="multipart/form-data" autocomplete="off" data-fc-settings-form>
				<div class="<?php echo esc_attr( $layout_class ); ?>" data-fc-settings-layout>
					<?php
					/**
					 * At the start of the settings layout, before the sidebar and content columns.
					 *
					 * @param  string  $current_tab  Active settings tab slug.
					 */
					do_action( 'fc_admin_settings_layout_start', $current_tab );
					?>

					<div class="fc-settings-sidebar" data-fc-settings-sidebar>
						<div class="fc-settings-sidebar__inner">
							<div class="fc-settings-sidebar-header">
								<button
									type="button"
									class="fc-settings-sidebar-toggle"
									data-fc-settings-nav-toggle
									aria-controls="fc-settings-nav"
									aria-expanded="false"
									aria-label="<?php echo esc_attr( __( 'Open settings menu', 'fluid-checkout' ) ); ?>"
									data-label-open="<?php echo esc_attr( __( 'Open settings menu', 'fluid-checkout' ) ); ?>"
									data-label-close="<?php echo esc_attr( __( 'Close settings menu', 'fluid-checkout' ) ); ?>"
								>
									<svg class="fc-settings-sidebar-toggle__icon fc-settings-sidebar-toggle__icon--menu" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
										<path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16"/>
										<path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16"/>
										<path stroke-linecap="round" stroke-linejoin="round" d="M4 17h16"/>
									</svg>
									<svg class="fc-settings-sidebar-toggle__icon fc-settings-sidebar-toggle__icon--close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
										<path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12"/>
										<path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18"/>
									</svg>
								</button>
								<div class="fc-settings-sidebar-header__actions">
									<?php $this->output_settings_header_action_buttons( $can_save ); ?>
								</div>
							</div>
							<?php call_user_func( $args[ 'nav_callback' ], $nav_tabs, $current_tab, $args[ 'title' ] ); ?>
						</div>
					</div>

					<div class="fc-settings-content">
						<div class="fc-settings-content__scroll">
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

								// Get the title shown above the tab contents, the label unless the tab has its own title
								$tab_title = ! empty( $tab_args[ 'title' ] ) ? $tab_args[ 'title' ] : $tab_args[ 'label' ];
								?>
								<div
									class="fc-settings-tab fc-settings-tab--<?php echo esc_attr( $tab ); ?><?php echo $is_active ? ' is-active' : ''; ?>"
									data-fc-settings-tab="<?php echo esc_attr( $tab ); ?>"
									data-fc-settings-show-save="<?php echo $tab_args[ 'show_save_button' ] ? 'yes' : 'no'; ?>"
								>
									<h2 class="fc-settings-content__title"><?php echo esc_html( $tab_title ); ?></h2>

									<?php call_user_func( $args[ 'tab_content_callback' ], $tab, $tab_args ); ?>
								</div>
							<?php endforeach; ?>

							<input type="hidden" name="fc_settings_action" value="save">
							<?php wp_nonce_field( 'fc_settings_save', 'fc_settings_nonce' ); ?>
						</div>

						<div class="fc-settings-actions" data-fc-settings-submit <?php echo $can_save ? '' : 'hidden'; ?>>
							<button type="submit" class="fc-header__button fc-header__button--save fc-settings-actions__button" data-fc-settings-save data-fc-settings-shortcut="save" <?php disabled( ! $can_save ); ?>><?php echo esc_html( __( 'Save settings', 'fluid-checkout' ) ); ?><span class="fc-settings-shortcut" data-fc-settings-shortcut-label aria-hidden="true"></span></button>
							<?php if ( class_exists( 'FluidCheckout_Admin_Settings_Preview' ) ) : ?>
								<button
									type="button"
									class="fc-header__button fc-settings-actions__preview"
									data-fc-settings-preview-toggle
									data-fc-settings-shortcut="expand-preview"
									aria-controls="fc-settings-preview"
									aria-expanded="false"
								>
									<svg class="fc-settings-preview__expand-icon fc-settings-preview__expand-icon--expand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
										<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 8.25L12 4.5l3.75 3.75"/>
										<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15.75L12 19.5l3.75-3.75"/>
									</svg>
									<svg class="fc-settings-preview__expand-icon fc-settings-preview__expand-icon--collapse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
										<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5L12 8.25 15.75 4.5"/>
										<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 19.5L12 15.75 15.75 19.5"/>
									</svg>
									<span><?php echo esc_html( __( 'Preview', 'fluid-checkout' ) ); ?></span>
									<span class="fc-settings-shortcut" data-fc-settings-shortcut-label aria-hidden="true"></span>
								</button>
							<?php endif; ?>
						</div>
					</div>

					<?php
					/**
					 * After the settings content column (e.g. page preview).
					 *
					 * @param  string  $current_tab  Active settings tab slug.
					 */
					do_action( 'fc_admin_settings_after_content', $current_tab );
					?>

				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Output the contents of a settings tab: its settings fields and additional content.
	 *
	 * @param  string  $tab        Settings tab slug.
	 * @param  array   $_tab_args  Settings tab arguments, not used by the settings page.
	 */
	public function output_tab_content( $tab, $_tab_args ) {
		// Output settings fields of the tab
		FluidCheckout_Admin_Settings_Renderer::instance()->output_fields( $this->get_tab_settings( $tab ) );

		/**
		 * Output additional content for a tab of the Fluid Checkout settings page.
		 */
		do_action( 'fc_admin_settings_tab_' . $tab );
	}

	/**
	 * Markup for the settings sidebar nav link chevron icon.
	 */
	public function get_settings_nav_link_chevron_markup() {
		return '<svg class="fc-settings-nav__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>';
	}

	/**
	 * Output the sidebar navigation as accordion groups.
	 *
	 * @param  array   $tabs           Settings tabs.
	 * @param  string  $current_tab    Current settings tab slug.
	 * @param  string  $sidebar_title  Title for the settings accordion group.
	 */
	public function output_sidebar_nav( $tabs, $current_tab, $sidebar_title = '' ) {
		$show_design_accordion = $this->should_show_design_editor_sidebar_accordion();
		$is_on_design_editor_page = $this->is_design_editor_page();
		$is_pro_design_editor = FluidCheckout::instance()->is_pro_activated() && class_exists( 'FluidCheckout_PRO_Admin_DesignEditor' );
		$dashboard_accordion_open = $this->is_dashboard_page();
		$design_accordion_open = $show_design_accordion && $is_on_design_editor_page;
		$settings_accordion_open = $this->is_settings_page();
		$is_getting_started_active = $this->is_dashboard_page();
		$dashboard_label = __( 'Dashboard', 'fluid-checkout' );
		$design_accordion_label = __( 'Design', 'fluid-checkout' );
		$design_nav_item_label = __( 'PRO feature', 'fluid-checkout' );
		$design_accordion_url = $this->get_design_editor_url();
		$design_editor_current_tab = '';

		if ( $is_pro_design_editor ) {
			$pro_design_editor = FluidCheckout_PRO_Admin_DesignEditor::instance();
			$design_accordion_url = $pro_design_editor->get_design_editor_url( FluidCheckout_PRO_Admin_DesignEditor::DEFAULT_TAB );

			if ( $is_on_design_editor_page ) {
				$design_editor_current_tab = $pro_design_editor->get_current_tab();
			}
		}
		elseif ( $is_on_design_editor_page ) {
			$design_editor_current_tab = 'design_editor';
		}

		if ( '' === $sidebar_title ) {
			$sidebar_title = __( 'Settings', 'fluid-checkout' );
		}
		?>
		<nav class="fc-settings-nav-accordions" id="fc-settings-nav" data-fc-settings-nav aria-label="<?php echo esc_attr( __( 'Settings navigation', 'fluid-checkout' ) ); ?>">
			<div class="fc-settings-nav-accordion <?php echo $dashboard_accordion_open ? 'is-open' : ''; ?>" data-fc-settings-nav-accordion="dashboard">
				<a
					class="fc-settings-nav-accordion__toggle"
					href="<?php echo esc_url( $this->get_dashboard_url() ); ?>"
					aria-expanded="<?php echo $dashboard_accordion_open ? 'true' : 'false'; ?>"
					aria-controls="fc-settings-nav-dashboard-body"
				>
					<span class="fc-settings-nav-accordion__title"><?php echo esc_html( $dashboard_label ); ?></span>
					<svg class="fc-settings-nav-accordion__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
						<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
					</svg>
				</a>
				<div class="fc-settings-nav-accordion__body" id="fc-settings-nav-dashboard-body">
					<ul class="fc-settings-nav">
						<li class="fc-settings-nav__item fc-settings-nav__item--dashboard <?php echo $is_getting_started_active ? 'is-active' : ''; ?>" data-fc-settings-nav-item="dashboard">
							<a class="fc-settings-nav__link" href="<?php echo esc_url( $this->get_dashboard_url() ); ?>" <?php echo $is_getting_started_active ? 'aria-current="page"' : ''; ?>>
								<span class="fc-settings-nav__marker" aria-hidden="true">&#8985;</span>
								<span class="fc-settings-nav__label"><?php echo esc_html( __( 'Getting started', 'fluid-checkout' ) ); ?></span>
								<?php echo $this->get_settings_nav_link_chevron_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG markup. ?>
							</a>
						</li>
					</ul>
				</div>
			</div>

			<div class="fc-settings-nav-accordion <?php echo $settings_accordion_open ? 'is-open' : ''; ?>" data-fc-settings-nav-accordion="settings">
				<a
					class="fc-settings-nav-accordion__toggle"
					href="<?php echo esc_url( $this->get_settings_url( self::DEFAULT_TAB ) ); ?>"
					aria-expanded="<?php echo $settings_accordion_open ? 'true' : 'false'; ?>"
					aria-controls="fc-settings-nav-settings-body"
				>
					<span class="fc-settings-nav-accordion__title"><?php echo esc_html( $sidebar_title ); ?></span>
					<svg class="fc-settings-nav-accordion__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
						<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
					</svg>
				</a>
				<div class="fc-settings-nav-accordion__body" id="fc-settings-nav-settings-body">
					<ul class="fc-settings-nav">
						<?php foreach ( $tabs as $slug => $tab ) : ?>
							<?php if ( 'dashboard' === $slug || '' === $slug ) { continue; } ?>

							<?php if ( 'separator' === $tab[ 'type' ] ) : ?>
								<li class="fc-settings-nav__separator" role="separator" aria-hidden="true"></li>
							<?php else : ?>
								<?php $is_active = $slug === $current_tab; ?>
								<li class="fc-settings-nav__item fc-settings-nav__item--<?php echo esc_attr( $slug ); ?> <?php echo $is_active ? 'is-active' : ''; ?>" data-fc-settings-nav-item="<?php echo esc_attr( $slug ); ?>">
									<a class="fc-settings-nav__link" href="<?php echo esc_url( $this->get_settings_url( $slug ) ); ?>" data-fc-settings-nav-link="<?php echo esc_attr( $slug ); ?>" <?php echo $is_active ? 'aria-current="page"' : ''; ?>>
										<span class="fc-settings-nav__marker" aria-hidden="true">&#8985;</span>
										<span class="fc-settings-nav__label"><?php echo esc_html( $tab[ 'label' ] ); ?></span>
										<?php echo $this->get_settings_nav_link_chevron_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG markup. ?>
									</a>
								</li>
							<?php endif; ?>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>

			<?php if ( $show_design_accordion ) : ?>
			<div class="fc-settings-nav-accordion <?php echo $design_accordion_open ? 'is-open' : ''; ?>" data-fc-settings-nav-accordion="design">
				<a
					class="fc-settings-nav-accordion__toggle"
					href="<?php echo esc_url( $design_accordion_url ); ?>"
					aria-expanded="<?php echo $design_accordion_open ? 'true' : 'false'; ?>"
					aria-controls="fc-settings-nav-design-body"
				>
					<span class="fc-settings-nav-accordion__title"><?php echo esc_html( $design_accordion_label ); ?></span>
					<svg class="fc-settings-nav-accordion__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
						<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
					</svg>
				</a>
				<div class="fc-settings-nav-accordion__body" id="fc-settings-nav-design-body">
					<?php if ( $is_pro_design_editor ) : ?>
						<?php FluidCheckout_PRO_Admin_DesignEditor::instance()->output_sidebar_design_accordion_body( $design_editor_current_tab ); ?>
					<?php else : ?>
					<ul class="fc-settings-nav">
						<li class="fc-settings-nav__item fc-settings-nav__item--design-editor <?php echo $is_on_design_editor_page ? 'is-active' : ''; ?>" data-fc-settings-nav-item="design_editor">
							<a class="fc-settings-nav__link" href="<?php echo esc_url( $this->get_design_editor_url() ); ?>" data-fc-settings-nav-link="design_editor" <?php echo $is_on_design_editor_page ? 'aria-current="page"' : ''; ?>>
								<span class="fc-settings-nav__marker" aria-hidden="true">&#8985;</span>
								<span class="fc-settings-nav__label"><?php echo esc_html( $design_nav_item_label ); ?></span>
								<?php echo $this->get_settings_nav_link_chevron_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG markup. ?>
							</a>
						</li>
					</ul>
					<?php endif; ?>
				</div>
			</div>
			<?php endif; ?>
		</nav>
		<?php
	}

}

FluidCheckout_Admin_Settings_Page::instance();
