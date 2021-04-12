<?php

/**
 * Enqueue scripts and styles
 */
class FluidCheckout_Enqueue extends FluidCheckout {

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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_require_bundle' ), 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_custom_fonts' ), 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );
	}



	/**
	 * Enqueue scripts
	 */
	public function enqueue_require_bundle() {
		// Require Bundle & Polyfills
		if ( ! wp_script_is( 'require-bundle', 'registered' ) ) { wp_enqueue_script( 'require-bundle', self::$directory_url . 'js/lib/require-bundle'. self::$asset_version . '.js', NULL, NULL ); }
		if ( ! wp_script_is( 'require-polyfills', 'registered' ) ) { wp_enqueue_script( 'require-polyfills', self::$directory_url . 'js/lib/require-polyfills'. self::$asset_version . '.js', NULL, NULL ); }
	}



	/**
	 * Enqueue scripts
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'wfc-bundles', self::$directory_url . 'js/bundles'. self::$asset_version . '.js', array( 'require-bundle' ), NULL, true );
		wp_localize_script(
			'wfc-bundles',
			'wfcSettings',
			apply_filters( 'wfc_js_settings', array(
				'ver'				=> self::$version,
				'assetsVersion'     => self::$asset_version,
				'cookiePath'	    => parse_url( get_option( 'siteurl' ), PHP_URL_PATH ),
				'cookieDomain'	    => parse_url( get_option( 'siteurl' ), PHP_URL_HOST ),
				'jsPath'			=> self::$directory_url . 'js/',
				'jsLibPath'			=> self::$directory_url . 'js/lib/',
				'cssPath'			=> self::$directory_url . 'css/',
				'ajaxUrl'			=> admin_url( 'admin-ajax.php' ),
				'flyoutBlock'       => array(
					'openAnimationClass' => 'fade-in-up',
					'closeAnimationClass' => 'fade-out-down',
				),
				'collapsibleBlock'  => array(),
			) )
		);
	}



	/**
	 * Enqueue fonts
	 */
	function enqueue_custom_fonts( $hook ) {
		wp_enqueue_style( 'wfc-fonts', self::$directory_url . '/css/fonts'. self::$asset_version . '.css', array(), null );
	}

}

FluidCheckout_Enqueue::instance();
