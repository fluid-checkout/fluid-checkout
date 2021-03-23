<?php

/**
 * Register premium features
 */
class FluidCheckout_PremiumFeatures extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct() {
		add_filter( 'wfc_init_features_list', array( $this, 'add_premium_features' ) );
	}



    /**
	 * Change plugin features list to include the premium features
	 * @since 1.2.0
	 */
	public function add_premium_features( $features ) {
		$_features = array_merge( $features, array(
			'account-pages'               => array( 'file' => 'inc/premium/account-pages.php', 'enable_option' => 'wfc_enable_account_pages', 'enable_default' => true ),
			'integration-google-address'  => array( 'file' => 'inc/premium/integration-google-address.php', 'enable_option' => 'wfc_enable_google_address_integration', 'enable_default' => true ),
		) );

		return $_features;
	}

}

FluidCheckout_PremiumFeatures::instance();
