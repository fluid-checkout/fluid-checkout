<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Wordfence Security (by Wordfence).
 */
class FluidCheckout_Wordfence extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct( ) {
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// Disable AJAX login feature since Wordfence has its own
		add_filter( 'fc_enable_checkout_ajax_login', '__return_false', 10 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_replace_plugin_scripts' ), 5 );
	}



	/**
	 * Replace plugin scripts with modified version.
	 */
	public function maybe_replace_plugin_scripts() {
		// Bail if not at checkout
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Plugin's script
		wp_register_script( 'wordfence-ls-login', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/wordfence/login' ), array( 'jquery' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}

}

FluidCheckout_Wordfence::instance();
