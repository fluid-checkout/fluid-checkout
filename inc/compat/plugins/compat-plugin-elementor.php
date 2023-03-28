<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Elementor (by Elementor).
 */
class FluidCheckout_Elementor extends FluidCheckout {

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
		// Force register steps
		add_filter( 'fc_force_register_steps', array( $this, 'change_force_register_steps' ), 10 );
	}



	/**
	 * Whether to force register checkout steps.
	 * 
	 * @param   bool  $force_register_steps  The widgets manager.
	 */
	public function change_force_register_steps( $force_register_steps ) {
		// Elementor request
		if ( ! empty( $_REQUEST['action'] ) && 'elementor' === $_REQUEST['action'] ) { // phpcs:ignore
			$force_register_steps = true;
		}

		// Elementor ajax request
		if ( ! empty( $_REQUEST['action'] ) && 'elementor_ajax' === $_REQUEST['action'] ) { // phpcs:ignore
			$force_register_steps = true;
		}

		return $force_register_steps;
	}

}

FluidCheckout_Elementor::instance(); 
