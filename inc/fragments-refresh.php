<?php
defined( 'ABSPATH' ) || exit;

/**
 * Fragments update for pages that do not have native WooCommerce functions to update fragments.
 */
class FluidCheckout_FragmentsRefresh extends FluidCheckout {

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
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Bail if fragments refresh is not enabled
		if ( true !== apply_filters( 'fc_enable_fragments_refresh', false ) ) { return; }

		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Fragments refresh
		add_action( 'wc_ajax_fc_update_fragments', array( $this, 'update_fragments' ), 10 );
	}



	/**
	 * Undo hooks.
	 */
	public function undo_hooks() {
		// Register assets
		remove_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// JS settings object
		remove_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Fragments refresh
		remove_action( 'wc_ajax_fc_update_fragments', array( $this, 'update_fragments' ), 10 );
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {

		$settings[ 'fragmentsRefresh' ] = apply_filters( 'fc_fragments_update_settings', array(
			'updateFragmentsNonce' => wp_create_nonce( 'fc-fragments-refresh' ),
		) );

		return $settings;
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Maybe load RTL file
		$rtl_suffix = is_rtl() ? '-rtl' : '';

		// Register scripts
		wp_register_script( 'fc-fragments-update', self::$directory_url . 'js/fc-fragments-refresh'. self::$asset_version . '.js', array( 'jquery', 'jquery-blockui', 'fc-utils' ), NULL );
		wp_add_inline_script( 'fc-fragments-update', 'window.addEventListener("load",function(){FCFragmentsRefresh.init(fcSettings.fragmentsRefresh);})' );

		// Register styles
		wp_register_style( 'fc-fragments-update', self::$directory_url . 'css/fragments-update' . $rtl_suffix . self::$asset_version . '.css', array(), null );
	}



	/**
	 * Enqueue assets for fragments refresh.
	 */
	public function enqueue_assets_fragment_refresh() {
		// Scripts
		wp_enqueue_script( 'fc-fragments-update' );

		// Styles
		wp_enqueue_style( 'fc-fragments-update' );
	}

	/**
	 * Dequeue assets for fragments refresh.
	 */
	public function dequeue_assets_fragment_refresh() {
		// Scripts
		wp_dequeue_script( 'fc-fragments-update' );

		// Styles
		wp_dequeue_style( 'fc-fragments-update' );
	}



	/**
	 * AJAX Get update cart fragments.
	 */
	public function update_fragments() {
		check_ajax_referer( 'fc-fragments-refresh', 'security' );

		wp_send_json(
			array(
				'result'    => 'success',
				'fragments' => apply_filters( 'fc_update_fragments', array() ),
			)
		);
	}

}

FluidCheckout_FragmentsRefresh::instance();
