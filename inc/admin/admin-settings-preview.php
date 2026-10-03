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
	 * __construct function.
	 */
	public function __construct() {
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Output
		add_action( 'fc_admin_settings_after_content', array( $this, 'output_preview' ), 10 );

		// Assets
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 20 );
	}



	/**
	 * Whether the preview column should be visible for a settings tab.
	 *
	 * @param  string  $tab  Settings tab slug.
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
				'label'        => __( 'Checkout', 'fluid-checkout' ),
				'requires_pro' => false,
			),
			'cart'           => array(
				'label'        => __( 'Cart', 'fluid-checkout' ),
				'requires_pro' => ! $is_pro_activated,
			),
			'order_received' => array(
				'label'        => __( 'Thank you', 'fluid-checkout' ),
				'requires_pro' => ! $is_pro_activated,
			),
			'order_pay'      => array(
				'label'        => __( 'Order pay', 'fluid-checkout' ),
				'requires_pro' => ! $is_pro_activated,
			),
		);

		/**
		 * Filter preview page tabs on the Fluid Checkout settings page.
		 *
		 * @param  array  $pages  Map of page slug => args (`label`, `requires_pro`).
		 */
		return apply_filters( 'fc_admin_settings_preview_pages', $pages );
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

		return 'checkout';
	}



	/**
	 * Register preview assets.
	 */
	public function register_assets() {
		wp_register_script( 'fc-admin-settings-preview', FluidCheckout_Enqueue::instance()->get_script_url( 'js/admin/admin-settings-preview' ), array(), null, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-admin-settings-preview', 'window.addEventListener("load",function(){FCAdminSettingsPreview.init(fcAdminSettingsPreviewSettings);});' );
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

		// EXCEPTION: Runtime values — initial tab and labels for the preview UI.
		wp_localize_script(
			'fc-admin-settings-preview',
			'fcAdminSettingsPreviewSettings',
			array(
				'hiddenTabs'             => self::HIDDEN_TABS,
				'initialTab'             => $page->get_current_tab(),
				'initialPage'            => $this->get_preview_page_for_tab( $page->get_current_tab() ),
				'previewProUrlTemplate'  => 'https://fluidcheckout.com/pricing/?mtm_campaign=upgrade-pro&mtm_kwd=settings-preview-{page}&mtm_source=lite-plugin',
				'i18n'                   => array(
					'expand'               => __( 'Expand preview', 'fluid-checkout' ),
					'collapse'             => __( 'Collapse preview', 'fluid-checkout' ),
					'preview'              => __( 'Page preview', 'fluid-checkout' ),
					/* translators: %s: preview page label, e.g. Checkout */
					'previewTitle'         => __( '%s preview', 'fluid-checkout' ),
					'previewSubtitle'      => __( 'Isolated session · fields read-only', 'fluid-checkout' ),
					/* translators: %s: HTML link to Fluid Checkout PRO pricing page */
					'previewSubtitlePro'   => __( 'Available with %s.', 'fluid-checkout' ),
					'previewProLinkLabel'  => __( 'Fluid Checkout PRO', 'fluid-checkout' ),
				),
			)
		);
	}



	/**
	 * Output the settings preview column.
	 *
	 * @param  string  $current_tab  Active settings tab slug.
	 */
	public function output_preview( $current_tab ) {
		$pages = $this->get_preview_pages();
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
				<button type="button" class="fc-settings-preview__expand" data-fc-settings-preview-expand aria-pressed="false" aria-label="<?php echo esc_attr( __( 'Expand preview', 'fluid-checkout' ) ); ?>" title="<?php echo esc_attr( __( 'Expand preview', 'fluid-checkout' ) ); ?>">
					<svg class="fc-settings-preview__expand-icon fc-settings-preview__expand-icon--expand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
						<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 8.25L4.5 12l3.75 3.75"/>
						<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 8.25L19.5 12l-3.75 3.75"/>
					</svg>
					<svg class="fc-settings-preview__expand-icon fc-settings-preview__expand-icon--collapse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
						<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 8.25L8.25 12 4.5 15.75"/>
						<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25L15.75 12 19.5 15.75"/>
					</svg>
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

				<p class="fc-settings-preview__status" data-fc-settings-preview-status><?php echo esc_html( __( 'Page preview', 'fluid-checkout' ) ); ?></p>
			</div>

			<div class="fc-settings-preview__tabs" role="tablist" aria-label="<?php echo esc_attr( __( 'Preview page', 'fluid-checkout' ) ); ?>">
				<?php foreach ( $pages as $page_slug => $page_args ) : ?>
					<?php $is_active = $page_slug === $initial_page; ?>
					<button
						type="button"
						class="fc-settings-preview__tab<?php echo $is_active ? ' is-active' : ''; ?>"
						role="tab"
						aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
						data-fc-settings-preview-page="<?php echo esc_attr( $page_slug ); ?>"
						data-requires-pro="<?php echo ! empty( $page_args[ 'requires_pro' ] ) ? 'yes' : 'no'; ?>"
					><?php echo esc_html( $page_args[ 'label' ] ); ?></button>
				<?php endforeach; ?>
			</div>

			<div class="fc-settings-preview__panel" data-fc-settings-preview-panel>
				<div class="fc-settings-preview__frame-wrap" data-fc-settings-preview-frame-wrap data-viewport="desktop">
					<p class="fc-settings-preview__dims" data-fc-settings-preview-dims aria-live="polite">&mdash;</p>
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
	 * Build a minimal placeholder document for the preview iframe.
	 *
	 * @param  string  $page_label    Label of the preview page.
	 * @param  bool    $requires_pro  Whether the page preview requires PRO.
	 * @param  string  $page_slug     Preview page slug (for PRO upgrade tracking).
	 * @return string
	 */
	private function get_placeholder_iframe_srcdoc( $page_label, $requires_pro = false, $page_slug = '' ) {
		$title = sprintf(
			/* translators: %s: preview page label, e.g. Checkout */
			__( '%s preview', 'fluid-checkout' ),
			$page_label
		);
		$subtitle = $requires_pro
			? $this->get_pro_preview_subtitle_html( $page_slug )
			: esc_html__( 'Isolated session · fields read-only', 'fluid-checkout' );

		$allowed_subtitle_html = array(
			'a' => array(
				'href'   => true,
				'target' => true,
				'rel'    => true,
			),
		);

		return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>html,body{margin:0;height:100%;font:13px/1.4 -apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#fff;color:#646970}body{display:flex;align-items:center;justify-content:center;text-align:center;padding:24px;box-sizing:border-box}strong{display:block;margin-bottom:6px;color:#1e1e1e;font-size:14px}a{color:#2271b1;text-decoration:underline}a:hover,a:focus{color:#135e96}</style></head><body><div><strong id="fc-settings-preview-title">' . esc_html( $title ) . '</strong><span id="fc-settings-preview-subtitle">' . wp_kses( $subtitle, $allowed_subtitle_html ) . '</span></div></body></html>';
	}

}

FluidCheckout_Admin_Settings_Preview::instance();
