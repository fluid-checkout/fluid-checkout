<?php
defined( 'ABSPATH' ) || exit;

/**
 * Design editor admin page without PRO, with the promo card of the Design editor in the settings page layout.
 */
class FluidCheckout_Admin_DesignEditorPromo extends FluidCheckout {

	/**
	 * Design editor page slug, the same as the Design editor page of PRO, so the page opens the Design editor once PRO is active.
	 */
	const PAGE_SLUG = 'fluid-checkout-design-editor';

	/**
	 * Slug of the only tab of the page.
	 */
	const TAB = 'design_editor';

	/**
	 * Design editor page hook suffix, set when registering the admin menu.
	 *
	 * @var string
	 */
	private $page_hook_suffix = '';



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
		add_action( 'admin_menu', array( $this, 'register_menu' ), 20 ); // After the Fluid Checkout menu, so the item comes after Settings

		// Admin body class
		add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ), 10 );

		// Admin page header
		add_action( 'admin_notices', array( $this, 'maybe_output_admin_header' ), 1 ); // Before other admin notices, so the page header comes first

		// Settings page layout
		add_filter( 'fc_is_admin_settings_layout_page', array( $this, 'maybe_set_is_settings_layout_page' ), 10 );

		// Enqueue assets after shared library scripts are registered
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 20 );
	}



	/**
	 * Register the Design editor item in the Fluid Checkout admin menu, after the Settings item.
	 */
	public function register_menu() {
		$this->page_hook_suffix = add_submenu_page( FluidCheckout_Admin_Settings_Page::PAGE_SLUG, __( 'Design editor', 'fluid-checkout' ), __( 'Design editor', 'fluid-checkout' ), FluidCheckout_Admin_Settings_Page::CAPABILITY, self::PAGE_SLUG, array( $this, 'output_page' ) );
	}



	/**
	 * Check whether the current request is for the Design editor page.
	 */
	public function is_design_editor_page() {
		// Bail if not in the admin
		if ( ! is_admin() ) { return false; }

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET[ 'page' ] ) && self::PAGE_SLUG === sanitize_text_field( wp_unslash( $_GET[ 'page' ] ) );
	}

	/**
	 * Add the settings page body class on the Design editor page, so it gets the settings page styles.
	 *
	 * @param  string  $classes  Space-separated list of admin body classes.
	 */
	public function add_admin_body_class( $classes ) {
		// Bail if not on the Design editor page
		if ( ! $this->is_design_editor_page() ) { return $classes; }

		return $classes . ' fc-settings-page';
	}

	/**
	 * Maybe output the Fluid Checkout admin page header on the Design editor page.
	 */
	public function maybe_output_admin_header() {
		// Bail if not on the Design editor page
		if ( ! $this->is_design_editor_page() ) { return; }

		FluidCheckout_Admin_Settings_Page::instance()->output_admin_header( false );
	}

	/**
	 * Maybe set the Design editor page as an admin page with the settings page layout, so its promo card gets the script of its activate button.
	 *
	 * @param  bool  $is_settings_layout_page  Whether the current request is for an admin page with the settings page layout.
	 */
	public function maybe_set_is_settings_layout_page( $is_settings_layout_page ) {
		// Bail if not on the Design editor page
		if ( ! $this->is_design_editor_page() ) { return $is_settings_layout_page; }

		return true;
	}



	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets() {
		// Settings page assets
		FluidCheckout_Admin_Settings_Page::instance()->enqueue_assets();
	}

	/**
	 * Maybe enqueue assets on the Design editor page.
	 *
	 * @param  string  $hook_suffix  Hook suffix for the current admin page.
	 */
	public function maybe_enqueue_assets( $hook_suffix ) {
		// Bail if not on the Design editor page
		if ( $this->page_hook_suffix !== $hook_suffix ) { return; }

		$this->enqueue_assets();
	}



	/**
	 * Get the promotional settings card of the Design editor page.
	 */
	public function get_promo_settings() {
		return array(
			array(
				'title'                => __( 'Checkout design customization', 'fluid-checkout' ),
				'type'                 => 'fc_promo',
				'id'                   => 'fc_pro_design_editor_promo',
				'is_card'              => true,
				'promo'                => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'design-editor-promo' ),
				'tagline'              => __( 'Make your checkout pages match your brand, without custom CSS.', 'fluid-checkout' ),
				'features'             => array(
					__( 'Change the style of the header, footer, steps, sections, order summary, cart items, coupons, buttons and form fields.', 'fluid-checkout' ),
					__( 'Set separate colors for light and dark mode.', 'fluid-checkout' ),
					__( 'Preview your changes on the checkout, cart, thank you and order pay pages before saving.', 'fluid-checkout' ),
				),
				'learn_more_url'       => 'https://fluidcheckout.com/pricing/?mtm_campaign=upgrade-pro&mtm_kwd=design-editor-promo-learn-more&mtm_source=lite-plugin',
				'learn_more_label'     => __( 'Learn more', 'fluid-checkout' ),
				'plugin_file'          => 'fluid-checkout-pro/fluid-checkout-pro.php',
				'plugin_slug'          => 'fluid-checkout-pro',
				'purchase_url'         => 'https://fluidcheckout.com/pricing/?mtm_campaign=upgrade-pro&mtm_kwd=design-editor-promo-purchase&mtm_source=lite-plugin',
				'purchase_price'       => '129 EUR',
			),
		);
	}



	/**
	 * Output the Design editor page in the settings page layout.
	 */
	public function output_page() {
		// Bail if user does not have enough permissions
		if ( ! current_user_can( FluidCheckout_Admin_Settings_Page::CAPABILITY ) ) { return; }

		// Output the page in the settings page layout
		FluidCheckout_Admin_Settings_Page::instance()->output_layout( array(
			'title'                    => __( 'Settings', 'fluid-checkout' ),
			'form_url'                 => add_query_arg( 'page', self::PAGE_SLUG, admin_url( 'admin.php' ) ),
			'tabs'                     => array(
				self::TAB    => array( 'type' => 'tab', 'label' => __( 'Design editor', 'fluid-checkout' ), 'show_save_button' => false ),
			),
			'current_tab'              => self::TAB,
			'nav_callback'             => array( $this, 'output_sidebar_nav' ),
			'tab_content_callback'     => array( $this, 'output_tab_content' ),
		) );
	}

	/**
	 * Output the navigation of the settings page in the sidebar, so the settings tabs are a click away.
	 */
	public function output_sidebar_nav() {
		FluidCheckout_Admin_Settings_Page::instance()->output_sidebar_nav( FluidCheckout_Admin_Settings_Page::instance()->get_tabs(), self::TAB );
	}

	/**
	 * Output the promo card of the Design editor as the contents of the page.
	 */
	public function output_tab_content() {
		FluidCheckout_Admin_Settings_Renderer::instance()->output_fields( $this->get_promo_settings() );
	}

}

FluidCheckout_Admin_DesignEditorPromo::instance();
