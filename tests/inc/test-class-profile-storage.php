<?php
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit test: profile storage, through the settings profiles.
 */
class FluidCheckout_ProfileStorage_Test extends TestCase {
	use TransactionalTestClassTrait;

	/**
	 * Clear cached values before each test, as the transaction rollback leaves them in the object cache.
	 */
	public function setUpInstance() : void {
		wp_cache_flush();
	}

	/**
	 * Clear the context profile and cached values after each test.
	 */
	public function tearDownInstance() : void {
		FluidCheckout_Settings::instance()->set_context_profile( null );
		wp_cache_flush();
	}



	/**
	 * Test: profile values round-trip unchanged, including characters that post content filters would change.
	 */
	public function test_profile_values_round_trip() {
		$settings = FluidCheckout_Settings::instance();
		$values = array(
			'fc_phpunit_text'     => 'Quote " and <strong>tag</strong> and \\ backslash',
			'fc_phpunit_list'     => array( 'first', 'second' ),
		);

		$this->assertTrue( $settings->save_profile_values( 'phpunit-round-trip', $values ) );

		// Read the values from the database, not from the cache
		$settings->invalidate_cache();
		$this->assertSame( $values, $settings->get_profile_values( 'phpunit-round-trip' ) );
	}

	/**
	 * Test: missing profiles and empty slugs have no values, and empty slugs are not saved.
	 */
	public function test_missing_profile_has_no_values() {
		$settings = FluidCheckout_Settings::instance();

		$this->assertSame( array(), $settings->get_profile_values( 'phpunit-missing' ) );
		$this->assertSame( array(), $settings->get_profile_values( '' ) );
		$this->assertFalse( $settings->save_profile_values( '', array( 'fc_phpunit_text' => 'value' ) ) );
	}

	/**
	 * Test: the default profile becomes active when saving needs a profile and none is active.
	 */
	public function test_default_profile_set_active_when_none() {
		$settings = FluidCheckout_Settings::instance();
		$settings->set_active_profile( '' );

		$this->assertSame( 'default', $settings->get_or_set_active_profile_slug() );
		$this->assertSame( 'default', $settings->get_active_profile_slug() );
	}

	/**
	 * Test: updating the active profile merges the values, without the site-wide settings.
	 */
	public function test_update_active_profile_values_merges_without_site_wide_keys() {
		$settings = FluidCheckout_Settings::instance();
		$settings->set_active_profile( 'phpunit-active' );
		$settings->save_profile_values( 'phpunit-active', array( 'fc_phpunit_first' => 'one' ) );

		$this->assertTrue( $settings->update_active_profile_values( array( 'fc_phpunit_second' => 'two', 'fc_gaa_google_places_api_key' => 'secret' ) ) );
		$this->assertSame( array( 'fc_phpunit_first' => 'one', 'fc_phpunit_second' => 'two' ), $settings->get_profile_values( 'phpunit-active' ) );
	}

	/**
	 * Test: a context profile overlays its values on the active profile, and context profiles that do not exist are ignored.
	 */
	public function test_context_profile_overlays_active_profile() {
		$settings = FluidCheckout_Settings::instance();
		$settings->set_active_profile( 'phpunit-active' );
		$settings->save_profile_values( 'phpunit-active', array( 'fc_phpunit_first' => 'active', 'fc_phpunit_second' => 'active' ) );
		$settings->save_profile_values( 'phpunit-context', array( 'fc_phpunit_second' => 'context' ) );

		// Overlay the context profile
		$settings->set_context_profile( 'phpunit-context' );
		$values = $settings->get_stored_effective_values();
		$this->assertSame( 'active', $values[ 'fc_phpunit_first' ] );
		$this->assertSame( 'context', $values[ 'fc_phpunit_second' ] );

		// Clear the context profile
		$settings->set_context_profile( null );
		$this->assertSame( 'active', $settings->get_stored_effective_values()[ 'fc_phpunit_second' ] );

		// Ignore a context profile that does not exist
		$settings->set_context_profile( 'phpunit-missing' );
		$this->assertSame( '', $settings->get_context_profile_slug() );
		$this->assertSame( 'active', $settings->get_stored_effective_values()[ 'fc_phpunit_second' ] );
	}

	/**
	 * Test: profile payloads are only written to posts of the profile post type.
	 */
	public function test_payload_only_written_to_profile_posts() {
		$post_id = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'PHPUnit post', 'post_content' => 'Original' ) );

		$this->assertFalse( FluidCheckout_Settings::instance()->write_profile_post_content( $post_id, 'Changed' ) );
		$this->assertSame( 'Original', get_post_field( 'post_content', $post_id ) );
	}

}
