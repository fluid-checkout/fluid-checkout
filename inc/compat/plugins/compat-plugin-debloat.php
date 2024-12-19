<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Debloat (by asadkn).
 */
class FluidCheckout_Digits extends FluidCheckout {

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
		// CSS optimizations
		add_filter( 'debloat/optimize_css_excludes', array( $this, 'add_css_excludes' ), 10 );
	}



	/**
	 * Add CSS excludes.
	 */
	public function add_css_excludes( $exclude ) {
		// Custom fonts
		$exclude[] = FluidCheckout_Enqueue::instance()->get_style_url( 'css/fonts' );

		return $exclude;
	}

}

FluidCheckout_Digits::instance();
