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
		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets_fragment_refresh' ), 10 );

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'maybe_add_js_settings' ), 10 );

		// Fragments refresh
		add_action( 'wc_ajax_fc_update_fragments', array( $this, 'update_fragments' ), 10 );
	}



	/**
	 * Undo hooks.
	 */
	public function undo_hooks() {
		// Register assets
		remove_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		remove_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets_fragment_refresh' ), 10 );

		// JS settings object
		remove_filter( 'fc_js_settings', array( $this, 'maybe_add_js_settings' ), 10 );

		// Fragments refresh
		remove_action( 'wc_ajax_fc_update_fragments', array( $this, 'update_fragments' ), 10 );
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function maybe_add_js_settings( $settings ) {
		// Bail if fragments refresh is not enabled
		if ( true !== apply_filters( 'fc_enable_fragments_refresh', false ) ) { return $settings; }

		// Add settings for fragments refresh
		$settings[ 'fragmentsRefresh' ] = apply_filters( 'fc_fragments_update_settings', array(
			'updateFragmentsNonce' => wp_create_nonce( 'fc-fragments-refresh' ),
		) );

		return $settings;
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Register scripts
		wp_register_script( 'fc-fragments-update', FluidCheckout_Enqueue::instance()->get_script_url( 'js/fc-fragments-refresh' ), array( 'jquery', 'jquery-blockui', 'fc-utils' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-fragments-update', 'window.addEventListener("load",function(){FCFragmentsRefresh.init(fcSettings.fragmentsRefresh);})' );

		// Register styles
		wp_register_style( 'fc-fragments-update', FluidCheckout_Enqueue::instance()->get_style_url( 'css/fragments-update' ), array(), null );
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
	 * Maybe enqueue assets for fragments refresh if enabled.
	 */
	public function maybe_enqueue_assets_fragment_refresh() {
		// Bail if fragments refresh is not enabled
		if ( true !== apply_filters( 'fc_enable_fragments_refresh', false ) ) { return; }

		$this->enqueue_assets_fragment_refresh();
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
		// Check security
		check_ajax_referer( 'fc-fragments-refresh', 'security' );

		// Otherwise, return fragments
		wp_send_json(
			array(
				'result'    => 'success',
				'fragments' => apply_filters( 'fc_update_fragments', array() ),
			)
		);
	}

}

FluidCheckout_FragmentsRefresh::instance();
