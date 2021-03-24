<?php

/**
 * Register premium features
 */
class FluidCheckout_PremiumFeatures extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct() {
		// Add premium features
		add_filter( 'wfc_init_features_list', array( $this, 'add_premium_features' ) );

		// Enqueue scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );
	}



    /**
	 * Change plugin features list to include the premium features
	 * @since 1.2.0
	 */
	public function add_premium_features( $features ) {
		$_features = array_merge( $features, array(
			'account-pages'               => array( 'file' => 'inc/premium/account-pages.php', 'enable_option' => 'wfc_enable_account_pages', 'enable_default' => true ),
			'address-book'                => array( 'file' => 'inc/premium/address-book.php', 'enable_option' => 'wfc_enable_address_book', 'enable_default' => true ),
			'integration-google-address'  => array( 'file' => 'inc/premium/integration-google-address.php', 'enable_option' => 'wfc_enable_google_address_integration', 'enable_default' => true ),
			'checkout-order-received'     => array( 'file' => 'inc/premium/checkout-order-received.php', 'enable_option' => 'wfc_enable_order_received', 'enable_default' => true ),
		) );

		return $_features;
	}



	/**
	 * Enqueue scripts
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'wfc-bundles-premium', self::$directory_url . 'js/bundles-premium'. self::$asset_version . '.js', array( 'require-bundle', 'wfc-bundles' ), NULL, true );
	}

}

FluidCheckout_PremiumFeatures::instance();
