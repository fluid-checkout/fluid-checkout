<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Blocksy (by CreativeThemes).
 */
class FluidCheckout_ThemeCompat_Blocksy extends FluidCheckout {

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
		// Settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10 );

		// Enqueue
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// Dark mode
		add_filter( 'fc_enable_dark_mode_styles', array( $this, 'maybe_force_enable_dark_mode' ), 10 );

		// Actions
		add_action( 'wc_ajax_fc_switch_color_mode', array( $this, 'switch_color_mode' ), 10 );

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings ) {

		// Add new settings
		$settings_new = array(
			array(
				'title' => __( 'Theme Blocksy', 'fluid-checkout' ),
				'type'  => 'title',
				'id'    => 'fc_integrations_theme_blocksy_options',
			),

			array(
				'title'           => __( 'Color switch', 'fluid-checkout' ),
				'desc'            => __( 'Force FC elements to follow color mode settings from the Blocksy theme.', 'fluid-checkout' ),
				'id'              => 'fc_compat_theme_blocksy_force_color_mode_switch',
				'type'            => 'checkbox',
				'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_compat_theme_blocksy_force_color_mode_switch' ),
				'autoload'        => false,
			),

			array(
				'type' => 'sectionend',
				'id'    => 'fc_integrations_theme_blocksy_options',
			),
		);

		$settings = array_merge( $settings, $settings_new );

		return $settings;
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'fc-compat-blocksy-color-mode-switch', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/themes/blocksy/color-mode-switch' ), array( 'jquery' ), NULL, true );
		wp_add_inline_script( 'fc-compat-blocksy-color-mode-switch', 'window.addEventListener("load",function(){BlocksyColorModeSwitch.init(fcSettings.BlocksyColorModeSwitch);})' );
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets() {
		// Scripts
		// Color mode switcher
		wp_enqueue_script( 'fc-compat-blocksy-color-mode-switch' );
	}

	/**
	 * Maybe enqueue assets.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not at checkout
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return; }

		// Bail when auto switch is disabled in the plugin settings
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_compat_theme_blocksy_force_color_mode_switch' ) ) { return; }

		// Get active extensions from Blocksy
		$active_extenstions = get_option('blocksy_active_extensions');

		// Bail if Color Switch extension is not active
		if ( ! is_array( $active_extenstions ) || ! in_array( 'color-mode-switch', $active_extenstions ) ) { return; }

		$this->enqueue_assets();
	}



	/**
	 * Maybe enable dark mode based on theme's "Colour Switch" status.
	 * 
	 * @param  bool  $is_dark_mode  Whether it is dark mode or not.
	 */
	public function maybe_force_enable_dark_mode( $is_dark_mode ) {
		// Bail when auto switch is disabled in the plugin settings
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_compat_theme_blocksy_force_color_mode_switch' ) ) { return $is_dark_mode; }

		// Get active extensions from Blocksy
		$active_extenstions = get_option('blocksy_active_extensions');

		// Bail if Color Switch extension is not active
		if ( ! is_array( $active_extenstions ) || ! in_array( 'color-mode-switch', $active_extenstions ) ) { return $is_dark_mode; }

		$is_dark_mode = false;

		// Bail if cookie is not available
		if ( ! isset( $_COOKIE['blocksy_current_theme'] ) ) { return $is_dark_mode; }

		if ( 'dark' === $_COOKIE['blocksy_current_theme'] ) {
			$is_dark_mode = true;
		}

		return $is_dark_mode;
	}



	/**
	 * AJAX Switch color mode
	 */
	public function switch_color_mode() {
		check_ajax_referer( 'fc-switch-color-mode', 'security' );

		$color_mode = sanitize_text_field( $_REQUEST['color_mode'] );
		$variables = array();

		// Bail if color mode is not defined
		if ( empty( $color_mode ) ) { wp_send_json( array( 'result' => 'error' ) ); }

		if ( 'dark' === $color_mode ) {
			// Get CSS variables
			$variables = FluidCheckout_DesignTemplates::instance()->get_css_variables_dark_mode();
		} 
		else {
			// Get CSS variables
			$variables = $this->get_css_variables_light_mode();
		}
		
		wp_send_json(
			array(
				'result'         => ! empty( $variables ) ? 'success' : 'error',
				'variables'      => $variables,
			)
		);
		
	}



	/**
	 * Get the CSS variables for light mode.
	 */
	public function get_css_variables_light_mode() {
		return array(
			'--fluidcheckout--color--black'             => '#000',
			'--fluidcheckout--color--darker-grey'       => '#1E212B',
			'--fluidcheckout--color--dark-grey'         => '#535156',
			'--fluidcheckout--color--grey'              => '#7b7575',
			'--fluidcheckout--color--light-grey'        => '#d8d8d8',
			'--fluidcheckout--color--lighter-grey'      => '#f3f3f3',
			'--fluidcheckout--color--white'             => '#fff',

			'--fluidcheckout--color--success'           => '#007a3d',
			'--fluidcheckout--color--error'             => '#cc1818',
			'--fluidcheckout--color--alert'             => '#c95000',
			'--fluidcheckout--color--info'              => '#1f01b9',

			'--fluidcheckout--shadow-color--darker'     => 'var(--fluidcheckout--color--light-grey)',
			'--fluidcheckout--shadow-color--dark'       => 'var(--fluidcheckout--color--lighter-grey)',
		);
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {
		$settings[ 'BlocksyColorModeSwitch' ] = array(
			'switchColorModeNonce' => wp_create_nonce( 'fc-switch-color-mode' ),
		);

		return $settings;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '40px',
				'--fluidcheckout--field--padding-left' => '12px',
				'--fluidcheckout--field--border-radius' => '3px',
				'--fluidcheckout--field--background-color--accent' => 'var(--theme-palette-color-1)',

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing--select' => '20px',
				'--fluidcheckout--validation-check--horizontal-spacing--select-alt' => '32px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Blocksy::instance();
