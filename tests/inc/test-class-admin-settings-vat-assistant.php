<?php
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit test: VAT Assistant settings PRO badges.
 */
class FluidCheckout_Admin_Settings_VATAssistant_Test extends TestCase {
	use TransactionalTestClassTrait;

	/**
	 * Badge slug for the Address Book integration section title.
	 */
	const ADDRESS_BOOK_INTEGRATION_BADGE_ID = 'vat-assistant-address-book-integration';

	/**
	 * Mark Fluid Checkout PRO as an active plugin.
	 *
	 * @param   array  $plugins  Active plugins.
	 */
	public function filter_activate_pro_plugin( $plugins ) {
		$plugins[] = 'fluid-checkout-pro/fluid-checkout-pro.php';

		return $plugins;
	}

	/**
	 * Get the Address Book integration title field from the VAT Assistant settings.
	 */
	public function get_address_book_integration_title_field() {
		$settings = FluidCheckout_Settings_VATAssistant::add_settings( array(), 'vat_assistant' );

		foreach ( $settings as $setting ) {
			if ( is_array( $setting ) && isset( $setting[ 'id' ] ) && 'fc_vat_address_book_integration' === $setting[ 'id' ] && isset( $setting[ 'type' ] ) && 'title' === $setting[ 'type' ] ) {
				return $setting;
			}
		}

		return null;
	}

	/**
	 * Test: Address Book integration badge uses the shared pricing-page helper.
	 */
	public function test_address_book_integration_badge_links_to_pricing_page() {
		$this->assertTrue( class_exists( 'FluidCheckout_Settings_VATAssistant' ) );
		$this->assertTrue( class_exists( 'FluidCheckout_Admin' ) );

		$field = $this->get_address_book_integration_title_field();
		$expected = FluidCheckout_Admin::instance()->get_pro_feature_badge_html( self::ADDRESS_BOOK_INTEGRATION_BADGE_ID );

		$this->assertIsArray( $field );
		$this->assertArrayHasKey( 'promo', $field );
		$this->assertSame( $expected, $field[ 'promo' ] );
		$this->assertStringContainsString( 'class="fc-settings-promo-pill"', $field[ 'promo' ] );
		$this->assertStringContainsString( 'https://fluidcheckout.com/pricing/', $field[ 'promo' ] );
		$this->assertStringContainsString( 'mtm_campaign=upgrade-pro', $field[ 'promo' ] );
		$this->assertStringContainsString( 'mtm_kwd=pro-badge-' . self::ADDRESS_BOOK_INTEGRATION_BADGE_ID, $field[ 'promo' ] );
		$this->assertStringContainsString( 'mtm_source=lite-plugin', $field[ 'promo' ] );
		$this->assertStringNotContainsString( '<span', $field[ 'promo' ] );
	}

	/**
	 * Test: Address Book integration badge is hidden when PRO is active.
	 */
	public function test_address_book_integration_badge_hidden_when_pro_active() {
		add_filter( 'option_active_plugins', array( $this, 'filter_activate_pro_plugin' ) );

		$pro_active = FluidCheckout::instance()->is_pro_activated();
		$field = $this->get_address_book_integration_title_field();
		$badge = FluidCheckout_Admin::instance()->get_pro_feature_badge_html( self::ADDRESS_BOOK_INTEGRATION_BADGE_ID );

		remove_filter( 'option_active_plugins', array( $this, 'filter_activate_pro_plugin' ) );

		$this->assertTrue( $pro_active );
		$this->assertSame( '', $badge );
		$this->assertIsArray( $field );
		$this->assertSame( '', $field[ 'promo' ] );
	}
}
