<?php
defined( 'ABSPATH' ) || exit;

/**
 * Page preview column for the Fluid Checkout settings page.
 * Shown on all tabs except Dashboard and License Keys.
 */
class FluidCheckout_Admin_Settings_Preview extends FluidCheckout {

	/**
	 * Settings tabs where the preview column is hidden.
	 *
	 * @var string[]
	 */
	const HIDDEN_TABS = array( 'dashboard', 'license_keys' );

	/**
	 * Browser storage key of the preview state: preview mode, header and footer visibility, and expanded state.
	 */
	const STORAGE_KEY = 'fcAdminSettingsPreview';

	/**
	 * Smallest screen width where the preview shows as a column, as narrower screens open it as a drawer.
	 */
	const COMPACT_BREAKPOINT = 1280;

	/**
	 * Page preview width set by each viewport button. Tablet and desktop match the Fluid Checkout breakpoints.
	 */
	const VIEWPORT_WIDTHS = array( 'mobile' => 350, 'tablet' => 750, 'desktop' => 1000 );

	/**
	 * Default preview mode per settings tab, cached after the filter runs once per request.
	 *
	 * @var array|null
	 */
	private $tab_preview_modes = null;



	/**
	 * Register hooks for the settings preview column.
	 */
	public function __construct() {
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Output
		add_action( 'fc_admin_settings_layout_start', array( $this, 'output_expanded_state_script' ), 10 );
		add_action( 'fc_admin_settings_after_content', array( $this, 'output_preview' ), 10 );

		// Assets
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 20 );

		// Admin JS settings (settings page already dumps `fcSettingsAdmin`)
		add_filter( 'fc_js_settings_admin', array( $this, 'add_js_settings_admin' ), 10 );
	}



	/**
	 * Whether the preview column should be visible for a settings tab.
	 *
	 * @param   string  $tab  Settings tab slug.
	 *
	 * @return  bool
	 */
	public function is_preview_visible_for_tab( $tab ) {
		return ! in_array( $tab, self::HIDDEN_TABS, true );
	}

	/**
	 * Get preview page definitions for the toolbar tabs.
	 *
	 * @return array[]
	 */
	public function get_preview_pages() {
		$is_pro_activated = FluidCheckout::instance()->is_pro_activated();

		$pages = array(
			'checkout'       => array(
				'label'             => __( 'Checkout', 'fluid-checkout' ),
				'requires_pro'      => false,
				'url'               => wc_get_checkout_url(),
			),
			'cart'           => array(
				'label'             => __( 'Cart', 'fluid-checkout' ),
				'requires_pro'      => ! $is_pro_activated,
				'enable_setting'    => 'fc_pro_enable_cart_page',
				'url'               => wc_get_cart_url(),
			),
			'order_received' => array(
				'label'             => __( 'Thank you', 'fluid-checkout' ),
				'requires_pro'      => ! $is_pro_activated,
				'enable_setting'    => 'fc_pro_enable_order_received',
				'url'               => FluidCheckout_Admin_Preview_Dummy_Data::instance()->get_order_received_url(),
			),
			'order_pay'      => array(
				'label'             => __( 'Order pay', 'fluid-checkout' ),
				'requires_pro'      => ! $is_pro_activated,
				'enable_setting'    => 'fc_pro_enable_order_pay',
				'url'               => FluidCheckout_Admin_Preview_Dummy_Data::instance()->get_order_pay_url(),
			),
		);

		/**
		 * Filters preview page tabs on the Fluid Checkout settings page.
		 * Each page is an array with the keys `label`, `requires_pro`, `url` of the frontend page to preview, and optionally `enable_setting`, the setting that enables the page on the settings tab with the same slug.
		 *
		 * @since  5.0.0
		 *
		 * @param  array  $pages  Preview page definitions by page slug.
		 *
		 * @return array  Preview page definitions.
		 */
		return apply_filters( 'fc_admin_settings_preview_pages', $pages );
	}

	/**
	 * Get the preview pane configuration for a settings tab.
	 *
	 * @param  string  $tab  Settings tab slug.
	 * @return array         Preview pane configuration.
	 */
	public function get_preview_config( $tab ) {
		// Define default configuration
		$defaults = array(
			'default_page'           => 'checkout',
			'show_page_tabs'         => true,
			'status_text'            => '',
			// Settings that only change the page styles, refreshed without reloading the preview
			'style_settings'         => array(
				'fc_checkout_secondary_column_background_color',
				'fc_checkout_header_background_color',
				'fc_checkout_page_background_color',
				'fc_checkout_footer_background_color',
				'fc_checkout_order_review_highlight_color',
			),
		);

		/**
		 * Filter the preview pane configuration on the Fluid Checkout settings page.
		 *
		 * @since  5.0.0
		 *
		 * @param  array   $config  Preview pane configuration.
		 * @param  string  $tab     Settings tab slug.
		 *
		 * @return array  Preview pane configuration.
		 */
		$config = apply_filters( 'fc_admin_settings_preview_config', $defaults, $tab );

		// Keep the default values that filters removed
		return wp_parse_args( $config, $defaults );
	}

	/**
	 * Map a settings tab slug to the matching preview page slug.
	 *
	 * @param  string  $tab  Settings tab slug.
	 * @return string        Preview page slug.
	 */
	public function get_preview_page_for_tab( $tab ) {
		$pages = $this->get_preview_pages();

		// Use the settings tab when it matches a preview page
		if ( isset( $pages[ $tab ] ) ) {
			return $tab;
		}

		// Otherwise, use the default preview page
		$config = $this->get_preview_config( $tab );
		return $config[ 'default_page' ];
	}

	/**
	 * Get the preview URL of a preview page, which loads the page in the preview iframe.
	 *
	 * @param  array  $page_args  Preview page arguments.
	 * @return string             Preview URL, or an empty string when the page cannot be previewed.
	 */
	public function get_preview_page_url( $page_args ) {
		// Bail if the page requires PRO or has no frontend page to preview
		if ( ! empty( $page_args[ 'requires_pro' ] ) || empty( $page_args[ 'url' ] ) ) { return ''; }

		return FluidCheckout_Admin_Preview::instance()->get_preview_url( $page_args[ 'url' ] );
	}



	/**
	 * Get the default preview mode per Fluid Checkout settings tab.
	 *
	 * @return array Preview mode by settings tab slug. Keys are tab slugs; values are `guest` or `logged_in`.
	 */
	public function get_tab_preview_modes() {
		// Bail if tab preview modes were already resolved
		if ( null !== $this->tab_preview_modes ) {
			return $this->tab_preview_modes;
		}

		/**
		 * Filter the default preview mode per Fluid Checkout settings tab.
		 *
		 * @return array Preview mode by settings tab slug. Keys are tab slugs; values are `guest` or `logged_in`.
		 */
		$this->tab_preview_modes = apply_filters( 'fc_admin_settings_preview_tab_modes', array() );

		return $this->tab_preview_modes;
	}



	/**
	 * Register preview assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'fc-admin-settings-preview', FluidCheckout_Enqueue::instance()->get_script_url( 'js/admin/admin-settings-preview' ), array( 'jquery', 'fc-utils', 'fc-admin-settings-enhanced-select' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-admin-settings-preview', 'window.addEventListener("load",function(){if(window.fcSettingsAdmin&&fcSettingsAdmin.settingsPreview){FCAdminSettingsPreview.init(fcSettingsAdmin.settingsPreview);}});' );
	}

	/**
	 * Maybe enqueue preview assets on the Fluid Checkout settings page.
	 *
	 * @param  string  $_hook_suffix  Hook suffix for the current admin page.
	 */
	public function maybe_enqueue_assets( $_hook_suffix ) {
		// Bail if settings page class is not available
		if ( ! class_exists( 'FluidCheckout_Admin_Settings_Page' ) ) { return; }

		$page = FluidCheckout_Admin_Settings_Page::instance();

		// Bail if not on the Fluid Checkout settings page
		if ( ! $page->is_settings_page() ) { return; }

		wp_enqueue_script( 'fc-admin-settings-preview' );
	}

	/**
	 * Add preview script settings to `fcSettingsAdmin` on the settings page.
	 *
	 * @param   array  $settings  Admin JS settings.
	 */
	public function add_js_settings_admin( $settings ) {
		// Bail if settings page class is not available
		if ( ! class_exists( 'FluidCheckout_Admin_Settings_Page' ) ) { return $settings; }

		$page = FluidCheckout_Admin_Settings_Page::instance();

		// Bail if not on the Fluid Checkout settings page
		if ( ! $page->is_settings_page() ) { return $settings; }

		// Get preview pane configuration
		$config = $this->get_preview_config( $page->get_current_tab() );

		$settings[ 'settingsPreview' ] = array(
			'hiddenTabs'         => self::HIDDEN_TABS,
			'initialTab'         => $page->get_current_tab(),
			'initialPage'        => $this->get_preview_page_for_tab( $page->get_current_tab() ),
			'placeholderSrcdocs' => $this->get_placeholder_iframe_srcdocs(),
			'defaultPage'        => $config[ 'default_page' ],
			'modeQueryArg'       => FluidCheckout_Admin_Preview::MODE_QUERY_ARG,
			'guestMode'          => FluidCheckout_Admin_Preview::MODE_GUEST,
			'loggedInMode'       => FluidCheckout_Admin_Preview::MODE_LOGGED_IN,
			'tabPreviewModes'    => $this->get_tab_preview_modes(),
			'storageKey'         => self::STORAGE_KEY,
			'compactBreakpoint'  => self::COMPACT_BREAKPOINT,
			'viewportWidths'     => self::VIEWPORT_WIDTHS,
			'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
			'draftAction'        => FluidCheckout_Admin_Preview::DRAFT_AJAX_ACTION,
			'draftNonce'         => wp_create_nonce( FluidCheckout_Admin_Preview::DRAFT_AJAX_ACTION ),
			'previewToken'       => FluidCheckout_Admin_Preview::instance()->get_token(),
			'styleSettings'      => $config[ 'style_settings' ],
			'i18n'               => array(
				'expand'           => __( 'Expand preview', 'fluid-checkout' ),
				'collapse'         => __( 'Collapse preview', 'fluid-checkout' ),
				'showPreview'      => __( 'Preview', 'fluid-checkout' ),
				'showHeaderFooter' => __( 'Show header and footer', 'fluid-checkout' ),
				'hideHeaderFooter' => __( 'Hide header and footer', 'fluid-checkout' ),
			),
		);

		return $settings;
	}



	/**
	 * Output the inline script that restores the expanded preview saved in the browser, before the settings layout renders.
	 * The preview script runs once the page has loaded, so restoring it there would show the collapsed preview first.
	 *
	 * @param  string  $current_tab  Active settings tab slug.
	 */
	public function output_expanded_state_script( $current_tab ) {
		// Bail if the preview is hidden for the settings tab
		if ( ! $this->is_preview_visible_for_tab( $current_tab ) ) { return; }
		?>
		<script>
		( function() {
			var layout = document.querySelector( '[data-fc-settings-layout]' );
			var state;

			// Get the preview state saved in the browser, as browser storage can be blocked or unavailable
			try {
				state = JSON.parse( window.localStorage.getItem( <?php echo wp_json_encode( self::STORAGE_KEY ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded constant. ?> ) ) || {};
			}
			// Bail when browser storage is not available
			catch ( error ) {
				return;
			}

			// Maybe expand the preview, except for the drawer of the compact layout
			if ( layout && true === state.expanded && ! window.matchMedia( '(max-width: <?php echo absint( self::COMPACT_BREAKPOINT - 1 ); ?>px)' ).matches ) {
				layout.classList.add( 'is-preview-expanded' );
			}
		} )();
		</script>
		<?php
	}

	/**
	 * Output the inline script that restores the preview mode and header and footer visibility saved in the browser, right after their controls.
	 * The preview script runs once the page has loaded, so restoring them there would show the default values first.
	 */
	public function output_controls_state_script( $current_tab ) {
		$tab_preview_modes = $this->get_tab_preview_modes();
		$tab_preview_mode = isset( $tab_preview_modes[ $current_tab ] ) ? $tab_preview_modes[ $current_tab ] : '';
		?>
		<script>
		( function() {
			var modeField = document.querySelector( '[data-fc-settings-preview-mode-field]' );
			var modeInput;
			var headerFooterButton = document.querySelector( '[data-fc-settings-preview-header-footer]' );
			var hideHeaderFooterLabel = <?php echo wp_json_encode( __( 'Hide header and footer', 'fluid-checkout' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded string. ?>;
			var tabPreviewMode = <?php echo wp_json_encode( $tab_preview_mode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded string. ?>;
			var state;
			var previewMode;

			// Get the preview state saved in the browser, as browser storage can be blocked or unavailable
			try {
				state = JSON.parse( window.localStorage.getItem( <?php echo wp_json_encode( self::STORAGE_KEY ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded constant. ?> ) ) || {};
			}
			// Bail when browser storage is not available
			catch ( error ) {
				return;
			}

			// Prefer the preview mode configured for this settings tab, then the saved mode
			previewMode = tabPreviewMode || state.mode || '';

			// Maybe select the preview mode, keeping guest mode for values the control does not have
			if ( modeField && previewMode ) {
				modeInput = modeField.querySelector( '[data-fc-settings-preview-mode][value="' + previewMode + '"]' );

				if ( modeInput ) {
					modeInput.checked = true;

					// Maybe highlight the mode field when the tab overrides the saved preview mode
					if ( tabPreviewMode && tabPreviewMode !== state.mode ) {
						modeField.classList.add( 'is-auto-changed' );
						window.setTimeout( function() {
							modeField.classList.remove( 'is-auto-changed' );
						}, 3000 );
					}
				}
			}

			// Maybe show the header and footer toggle as pressed
			if ( headerFooterButton && true === state.headerFooter ) {
				headerFooterButton.setAttribute( 'aria-pressed', 'true' );
				headerFooterButton.setAttribute( 'aria-label', hideHeaderFooterLabel );
				headerFooterButton.setAttribute( 'title', hideHeaderFooterLabel );
			}
		} )();
		</script>
		<?php
	}

	/**
	 * Output the settings preview column.
	 *
	 * @param  string  $current_tab  Active settings tab slug.
	 */
	public function output_preview( $current_tab ) {
		$pages = $this->get_preview_pages();
		$config = $this->get_preview_config( $current_tab );
		$initial_page = $this->get_preview_page_for_tab( $current_tab );
		$is_visible = $this->is_preview_visible_for_tab( $current_tab );
		$initial_requires_pro = ! empty( $pages[ $initial_page ][ 'requires_pro' ] );
		$iframe_srcdoc = $this->get_placeholder_iframe_srcdoc( $pages[ $initial_page ][ 'label' ], $initial_requires_pro, $initial_page );
		?>
		<aside
			class="fc-settings-preview"
			id="fc-settings-preview"
			aria-label="<?php echo esc_attr( __( 'Page preview', 'fluid-checkout' ) ); ?>"
			data-fc-settings-preview
			<?php echo $is_visible ? '' : 'hidden'; ?>
		>
			<div class="fc-settings-preview__toolbar">
				<div class="fc-settings-preview__controls">
					<button type="button" class="fc-settings-preview__expand" data-fc-settings-preview-expand data-fc-settings-shortcut="expand-preview" aria-pressed="false" aria-label="<?php echo esc_attr( __( 'Expand preview', 'fluid-checkout' ) ); ?>" title="<?php echo esc_attr( __( 'Expand preview', 'fluid-checkout' ) ); ?>">
						<svg class="fc-settings-preview__expand-icon fc-settings-preview__expand-icon--expand fc-settings-preview__expand-icon--horizontal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
							<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 8.25L4.5 12l3.75 3.75"/>
							<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 8.25L19.5 12l-3.75 3.75"/>
						</svg>
						<svg class="fc-settings-preview__expand-icon fc-settings-preview__expand-icon--collapse fc-settings-preview__expand-icon--horizontal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
							<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 8.25L8.25 12 4.5 15.75"/>
							<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25L15.75 12 19.5 15.75"/>
						</svg>
						<svg class="fc-settings-preview__expand-icon fc-settings-preview__expand-icon--expand fc-settings-preview__expand-icon--vertical" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
							<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 8.25L12 4.5l3.75 3.75"/>
							<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15.75L12 19.5l3.75-3.75"/>
						</svg>
						<svg class="fc-settings-preview__expand-icon fc-settings-preview__expand-icon--collapse fc-settings-preview__expand-icon--vertical" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
							<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5L12 8.25 15.75 4.5"/>
							<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 19.5L12 15.75 15.75 19.5"/>
						</svg>
						<span class="fc-settings-shortcut" data-fc-settings-shortcut-label aria-hidden="true"></span>
					</button>

					<div class="fc-settings-sectioned-buttons fc-settings-preview__viewports" role="radiogroup" aria-label="<?php echo esc_attr( __( 'Preview viewport', 'fluid-checkout' ) ); ?>">
						<label class="fc-settings-sectioned-buttons__option" title="<?php echo esc_attr( __( 'Mobile', 'fluid-checkout' ) ); ?>">
							<input type="radio" name="fc-settings-preview-viewport" value="mobile">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
								<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5h3m-6 0h9A1.5 1.5 0 0118 3v18a1.5 1.5 0 01-1.5 1.5h-9A1.5 1.5 0 016 21V3A1.5 1.5 0 017.5 1.5z"/>
								<path stroke-linecap="round" stroke-linejoin="round" d="M10 19h4"/>
							</svg>
							<span class="screen-reader-text"><?php echo esc_html( __( 'Mobile', 'fluid-checkout' ) ); ?></span>
						</label>
						<label class="fc-settings-sectioned-buttons__option" title="<?php echo esc_attr( __( 'Tablet', 'fluid-checkout' ) ); ?>">
							<input type="radio" name="fc-settings-preview-viewport" value="tablet">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
								<path stroke-linecap="round" stroke-linejoin="round" d="M5.25 2.25h13.5A1.5 1.5 0 0120.25 3.75v16.5a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5V3.75a1.5 1.5 0 011.5-1.5z"/>
								<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5h3"/>
							</svg>
							<span class="screen-reader-text"><?php echo esc_html( __( 'Tablet', 'fluid-checkout' ) ); ?></span>
						</label>
						<label class="fc-settings-sectioned-buttons__option" title="<?php echo esc_attr( __( 'Desktop', 'fluid-checkout' ) ); ?>">
							<input type="radio" name="fc-settings-preview-viewport" value="desktop" checked>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
								<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.5h16.5A1.5 1.5 0 0121.75 6v9a1.5 1.5 0 01-1.5 1.5H3.75A1.5 1.5 0 012.25 15V6A1.5 1.5 0 013.75 4.5z"/>
								<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 19.5h7.5M12 16.5v3"/>
							</svg>
							<span class="screen-reader-text"><?php echo esc_html( __( 'Desktop', 'fluid-checkout' ) ); ?></span>
						</label>
					</div>

					<div class="fc-settings-sectioned-buttons fc-settings-preview__width" role="group" aria-label="<?php echo esc_attr( __( 'Preview width', 'fluid-checkout' ) ); ?>">
						<button
							type="button"
							class="fc-settings-sectioned-buttons__option fc-settings-preview__width-button"
							data-fc-settings-preview-width-decrease
							title="<?php echo esc_attr( __( 'Decrease width', 'fluid-checkout' ) ); ?>"
							aria-label="<?php echo esc_attr( __( 'Decrease width', 'fluid-checkout' ) ); ?>"
						>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
								<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/>
							</svg>
						</button>
						<label class="fc-settings-preview__width-value" title="<?php echo esc_attr( __( 'Preview width in pixels', 'fluid-checkout' ) ); ?>">
							<input type="text" inputmode="numeric" autocomplete="off" value="<?php echo esc_attr( self::VIEWPORT_WIDTHS[ 'desktop' ] ); ?>" data-fc-settings-preview-width aria-label="<?php echo esc_attr( __( 'Preview width in pixels', 'fluid-checkout' ) ); ?>">
							<span aria-hidden="true">px</span>
						</label>
						<button
							type="button"
							class="fc-settings-sectioned-buttons__option fc-settings-preview__width-button"
							data-fc-settings-preview-width-increase
							title="<?php echo esc_attr( __( 'Increase width', 'fluid-checkout' ) ); ?>"
							aria-label="<?php echo esc_attr( __( 'Increase width', 'fluid-checkout' ) ); ?>"
						>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
								<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
							</svg>
						</button>
					</div>

					<div class="fc-settings-preview__frame-options">
						<button type="button" class="fc-settings-preview__header-footer" data-fc-settings-preview-header-footer aria-pressed="false" aria-label="<?php echo esc_attr( __( 'Show header and footer', 'fluid-checkout' ) ); ?>" title="<?php echo esc_attr( __( 'Show header and footer', 'fluid-checkout' ) ); ?>">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
								<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75h16.5a1.5 1.5 0 011.5 1.5v13.5a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5V5.25a1.5 1.5 0 011.5-1.5z"/>
								<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 15.75h19.5"/>
							</svg>
						</button>

						<div class="fc-settings-sectioned-buttons fc-settings-preview__mode" role="radiogroup" aria-label="<?php echo esc_attr( __( 'Preview as', 'fluid-checkout' ) ); ?>" data-fc-settings-preview-mode-field>
							<label class="fc-settings-sectioned-buttons__option" title="<?php echo esc_attr( __( 'Guest', 'fluid-checkout' ) ); ?>">
								<input type="radio" name="fc-settings-preview-mode" value="<?php echo esc_attr( FluidCheckout_Admin_Preview::MODE_GUEST ); ?>" data-fc-settings-preview-mode checked>
								<span><?php echo esc_html( __( 'Guest', 'fluid-checkout' ) ); ?></span>
							</label>
							<label class="fc-settings-sectioned-buttons__option" title="<?php echo esc_attr( __( 'Logged in', 'fluid-checkout' ) ); ?>">
								<input type="radio" name="fc-settings-preview-mode" value="<?php echo esc_attr( FluidCheckout_Admin_Preview::MODE_LOGGED_IN ); ?>" data-fc-settings-preview-mode>
								<span><?php echo esc_html( __( 'Logged in', 'fluid-checkout' ) ); ?></span>
							</label>
						</div>

						<?php $this->output_controls_state_script( $current_tab ); ?>
					</div>
				</div>

				<?php if ( ! empty( $config[ 'status_text' ] ) ) : ?>
					<p class="fc-settings-preview__status"><?php echo esc_html( $config[ 'status_text' ] ); ?></p>
				<?php endif; ?>
			</div>

			<div class="fc-settings-preview__tabs" role="tablist" aria-label="<?php echo esc_attr( __( 'Preview page', 'fluid-checkout' ) ); ?>" <?php echo $config[ 'show_page_tabs' ] ? '' : 'hidden'; ?>>
				<?php foreach ( $pages as $page_slug => $page_args ) : ?>
					<?php $is_active = $page_slug === $initial_page; ?>
					<button
						type="button"
						class="fc-settings-preview__tab<?php echo $is_active ? ' is-active' : ''; ?>"
						role="tab"
						aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
						data-fc-settings-preview-page="<?php echo esc_attr( $page_slug ); ?>"
						data-fc-settings-preview-url="<?php echo esc_url( $this->get_preview_page_url( $page_args ) ); ?>"
						data-fc-settings-preview-enable-setting="<?php echo esc_attr( isset( $page_args[ 'enable_setting' ] ) ? $page_args[ 'enable_setting' ] : '' ); ?>"
					><?php echo esc_html( $page_args[ 'label' ] ); ?></button>
				<?php endforeach; ?>
			</div>

			<div class="fc-settings-preview__panel" data-fc-settings-preview-panel>
				<div class="fc-settings-preview__frame-wrap" data-fc-settings-preview-frame-wrap>
					<p class="fc-settings-preview__dims" data-fc-settings-preview-dims aria-live="polite">&mdash;</p>
					<button type="button" class="fc-settings-preview__zoom-hint" data-fc-settings-preview-zoom-hint hidden><?php echo esc_html( __( 'Expand preview for a closer look', 'fluid-checkout' ) ); ?></button>
					<span class="spinner fc-settings-preview__spinner"></span>
					<iframe
						class="fc-settings-preview__frame"
						data-fc-settings-preview-frame
						title="<?php echo esc_attr( __( 'Page preview', 'fluid-checkout' ) ); ?>"
						srcdoc="<?php echo esc_attr( $iframe_srcdoc ); ?>"
					></iframe>
				</div>
			</div>
		</aside>
		<?php
	}

	/**
	 * Get the Fluid Checkout PRO upgrade URL for the preview placeholder.
	 *
	 * @param  string  $page_slug  Preview page slug.
	 * @return string
	 */
	private function get_pro_preview_upgrade_url( $page_slug = '' ) {
		$page_slug = str_replace( '_', '-', sanitize_title( $page_slug ) );
		$mtm_kwd = ! empty( $page_slug ) ? 'settings-preview-' . $page_slug : 'settings-preview';

		return add_query_arg(
			array(
				'mtm_campaign' => 'upgrade-pro',
				'mtm_kwd'      => $mtm_kwd,
				'mtm_source'   => 'lite-plugin',
			),
			'https://fluidcheckout.com/pricing/'
		);
	}

	/**
	 * Get the PRO unlock subtitle HTML for the preview placeholder.
	 *
	 * @param  string  $page_slug  Preview page slug.
	 * @return string
	 */
	private function get_pro_preview_subtitle_html( $page_slug = '' ) {
		$link = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( $this->get_pro_preview_upgrade_url( $page_slug ) ),
			esc_html__( 'Fluid Checkout PRO', 'fluid-checkout' )
		);

		return sprintf(
			/* translators: %s: HTML link to Fluid Checkout PRO pricing page */
			__( 'Available with %s.', 'fluid-checkout' ),
			$link
		);
	}

	/**
	 * Get the subtitle HTML for the preview placeholder of a disabled page, with a link to the setting that enables it.
	 *
	 * @param  string  $page_slug       Preview page slug, also the slug of the settings tab with the setting.
	 * @param  string  $enable_setting  Setting that enables the page.
	 * @return string
	 */
	public function get_disabled_preview_subtitle_html( $page_slug, $enable_setting ) {
		// Get link to the setting
		$link = sprintf(
			'<a href="%1$s" target="_parent">%2$s</a>',
			esc_url( FluidCheckout_Admin_Settings_Page::instance()->get_settings_url( $page_slug ) . '#' . $enable_setting ),
			esc_html__( 'Turn them on', 'fluid-checkout' )
		);

		return sprintf(
			/* translators: %s: HTML link to the setting that enables the optimizations of the page */
			__( 'Optimizations for this page are turned off. %s to preview the page.', 'fluid-checkout' ),
			$link
		);
	}

	/**
	 * Get the placeholder documents shown in the preview iframe for pages that cannot be previewed.
	 *
	 * @return array  Placeholder documents by preview page slug.
	 */
	public function get_placeholder_iframe_srcdocs() {
		// Define placeholder documents
		$placeholders = array();

		// Iterate preview pages
		foreach ( $this->get_preview_pages() as $page_slug => $page_args ) {
			$placeholders[ $page_slug ] = $this->get_placeholder_iframe_srcdoc( $page_args[ 'label' ], ! empty( $page_args[ 'requires_pro' ] ), $page_slug, isset( $page_args[ 'enable_setting' ] ) ? $page_args[ 'enable_setting' ] : '' );
		}

		return $placeholders;
	}

	/**
	 * Build a minimal placeholder document for the preview iframe.
	 *
	 * @param  string  $page_label      Label of the preview page.
	 * @param  bool    $requires_pro    Whether the page preview requires PRO.
	 * @param  string  $page_slug       Preview page slug (for PRO upgrade tracking).
	 * @param  string  $enable_setting  Setting that enables the page, for the placeholder shown while it is disabled.
	 * @return string
	 */
	private function get_placeholder_iframe_srcdoc( $page_label, $requires_pro = false, $page_slug = '', $enable_setting = '' ) {
		$title = sprintf(
			/* translators: %s: preview page label, e.g. Checkout */
			__( '%s preview', 'fluid-checkout' ),
			$page_label
		);

		// Maybe link to PRO, for pages that require it
		if ( $requires_pro ) {
			$subtitle = $this->get_pro_preview_subtitle_html( $page_slug );
		}
		// Maybe link to the setting that enables the page, for disabled pages
		elseif ( ! empty( $enable_setting ) ) {
			$subtitle = $this->get_disabled_preview_subtitle_html( $page_slug, $enable_setting );
		}
		// Otherwise, describe the preview
		else {
			$subtitle = esc_html__( 'Isolated session · nothing is saved', 'fluid-checkout' );
		}

		$allowed_subtitle_html = array(
			'a' => array(
				'href'   => true,
				'target' => true,
				'rel'    => true,
			),
		);

		return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>html,body{margin:0;height:100%;font:13px/1.4 -apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#fff;color:#646970}body{display:flex;align-items:center;justify-content:center;text-align:center;padding:24px;box-sizing:border-box}div{zoom:calc(1/var(--fc-settings-preview-scale,1))}strong{display:block;margin-bottom:6px;color:#1e1e1e;font-size:14px}a{color:#2271b1;text-decoration:underline}a:hover,a:focus{color:#135e96}</style></head><body><div><strong id="fc-settings-preview-title">' . esc_html( $title ) . '</strong><span id="fc-settings-preview-subtitle">' . wp_kses( $subtitle, $allowed_subtitle_html ) . '</span></div></body></html>';
	}

}

FluidCheckout_Admin_Settings_Preview::instance();
