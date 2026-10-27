<?php
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit test: Fluid Checkout admin helpers.
 */
class FluidCheckout_Admin_Test extends TestCase {
	use TransactionalTestClassTrait;

	/**
	 * Section slug used in PRO badge tracking assertions.
	 */
	const PRO_BADGE_SECTION_SLUG = 'example-settings-section';

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
	 * Mark Fluid Checkout PRO as inactive.
	 *
	 * @param   array  $plugins  Active plugins.
	 */
	public function filter_deactivate_pro_plugin( $plugins ) {
		return array_values( array_filter( $plugins, function( $plugin ) {
			return 'fluid-checkout-pro/fluid-checkout-pro.php' !== $plugin;
		} ) );
	}

	/**
	 * Test: PRO feature badge links to the pricing page when PRO is not active.
	 */
	public function test_get_pro_feature_badge_html_links_to_pricing_page_when_pro_inactive() {
		add_filter( 'option_active_plugins', array( $this, 'filter_deactivate_pro_plugin' ) );

		$this->assertFalse( FluidCheckout::instance()->is_pro_activated() );

		$html = FluidCheckout_Admin::instance()->get_pro_feature_badge_html( self::PRO_BADGE_SECTION_SLUG );

		remove_filter( 'option_active_plugins', array( $this, 'filter_deactivate_pro_plugin' ) );

		$this->assertStringContainsString( 'class="fc-settings-promo-pill"', $html );
		$this->assertStringContainsString( 'https://fluidcheckout.com/pricing/', $html );
		$this->assertStringContainsString( 'mtm_campaign=upgrade-pro', $html );
		$this->assertStringContainsString( 'mtm_kwd=pro-badge-' . self::PRO_BADGE_SECTION_SLUG, $html );
		$this->assertStringContainsString( 'mtm_source=lite-plugin', $html );
		$this->assertStringNotContainsString( '<span', $html );
	}

	/**
	 * Test: PRO feature badge is empty when PRO is active.
	 */
	public function test_get_pro_feature_badge_html_is_empty_when_pro_active() {
		add_filter( 'option_active_plugins', array( $this, 'filter_activate_pro_plugin' ) );

		$this->assertTrue( FluidCheckout::instance()->is_pro_activated() );

		$html = FluidCheckout_Admin::instance()->get_pro_feature_badge_html( self::PRO_BADGE_SECTION_SLUG );

		remove_filter( 'option_active_plugins', array( $this, 'filter_activate_pro_plugin' ) );

		$this->assertSame( '', $html );
	}
}
