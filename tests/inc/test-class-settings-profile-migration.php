<?php
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit test: first settings profile preserves non-`fc_` values from other plugins.
 */
class FluidCheckout_Settings_Profile_Migration_Test extends TestCase {
	use TransactionalTestClassTrait;

	/**
	 * Test: migration seeds `fc_*` defaults and keeps existing WooCommerce option values.
	 */
	public function test_first_profile_preserves_non_fc_option_values() {
		$settings = FluidCheckout_Settings::instance();
		$option   = 'woocommerce_enable_shipping_calc';

		$this->reset_settings_profiles_migration_state( $settings );

		// WooCommerce value that differs from the Fluid Checkout code default (`yes`)
		$this->set_raw_option_row( $option, 'no' );

		// Managed non-`fc_*` key with no stored row must not be seeded from FC defaults
		$this->delete_raw_option_row( 'woocommerce_enable_checkout_login_reminder' );

		$settings->invalidate_cache();
		$settings->maybe_migrate_legacy_options();

		$this->assertSame( 'yes', $settings->get_raw_option( FluidCheckout_Settings::MIGRATION_FLAG_OPTION, 'no' ) );

		$profile = $settings->get_profile_values( 'default' );

		$this->assertArrayHasKey( $option, $profile );
		$this->assertSame( 'no', $profile[ $option ] );
		$this->assertArrayNotHasKey( 'woocommerce_enable_checkout_login_reminder', $profile );
		$this->assertArrayHasKey( 'fc_design_template', $profile );
		$this->assertSame( 'no', $settings->get_option( $option ) );
		$this->assertSame( 'no', get_option( $option ) );
	}

	/**
	 * Test: deactivation sync writes WooCommerce overrides only, not managed `fc_*` keys.
	 */
	public function test_deactivation_sync_skips_fc_managed_option_keys() {
		$settings = FluidCheckout_Settings::instance();
		$woo_key  = 'woocommerce_enable_guest_checkout';
		$fc_key   = 'fc_design_template';

		$this->reset_settings_profiles_migration_state( $settings );

		$this->delete_raw_option_row( $woo_key );
		$this->set_raw_option_row( $fc_key, 'boxed' );

		$this->assertTrue(
			$settings->save_profile_values(
				'default',
				array(
					$woo_key => 'yes',
					$fc_key  => 'split',
				)
			)
		);
		$settings->update_raw_option( FluidCheckout_Settings::ACTIVE_PROFILE_OPTION, 'default', false );
		$settings->invalidate_cache();

		$settings->sync_active_profile_to_raw_options();

		$this->assertSame( 'yes', $settings->get_raw_option( $woo_key, null ) );
		$this->assertSame( 'boxed', $settings->get_raw_option( $fc_key, null ) );
	}



	/**
	 * Clear migration flags, active profile pointer, and existing profile posts.
	 *
	 * @param  FluidCheckout_Settings  $settings  Settings repository.
	 */
	private function reset_settings_profiles_migration_state( $settings ) {
		global $wpdb;

		$this->delete_raw_option_row( FluidCheckout_Settings::MIGRATION_FLAG_OPTION );
		$this->delete_raw_option_row( FluidCheckout_Settings::ACTIVE_PROFILE_OPTION );
		$this->delete_raw_option_row( FluidCheckout_Settings::MIGRATION_TIME_OPTION );
		$this->delete_raw_option_row( FluidCheckout_Settings::CLEANUP_NOTICE_OPTION );

		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
				FluidCheckout_Settings::PROFILE_POST_TYPE
			)
		);

		// Iterate existing profile posts and remove them from the database and object cache
		foreach ( $post_ids as $post_id ) {
			$post_id = (int) $post_id;
			$wpdb->delete( $wpdb->posts, array( 'ID' => $post_id ), array( '%d' ) );
			$wpdb->delete( $wpdb->postmeta, array( 'post_id' => $post_id ), array( '%d' ) );
			clean_post_cache( $post_id );
		}

		wp_cache_set( 'last_changed', microtime(), 'posts' );
		$settings->invalidate_cache();
	}

	/**
	 * Write an options-table row and clear option object caches.
	 *
	 * @param  string  $option  Option name.
	 * @param  string  $value   Option value.
	 */
	private function set_raw_option_row( $option, $value ) {
		global $wpdb;

		$wpdb->delete( $wpdb->options, array( 'option_name' => $option ), array( '%s' ) );
		$wpdb->insert(
			$wpdb->options,
			array(
				'option_name'  => $option,
				'option_value' => $value,
				'autoload'     => 'no',
			),
			array( '%s', '%s', '%s' )
		);

		$this->flush_option_caches( $option );
	}

	/**
	 * Delete an options-table row and clear option object caches.
	 *
	 * @param  string  $option  Option name.
	 */
	private function delete_raw_option_row( $option ) {
		global $wpdb;

		$wpdb->delete( $wpdb->options, array( 'option_name' => $option ), array( '%s' ) );
		$this->flush_option_caches( $option );
	}

	/**
	 * Clear WordPress option caches for one key.
	 *
	 * @param  string  $option  Option name.
	 */
	private function flush_option_caches( $option ) {
		wp_cache_delete( $option, 'options' );
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}
}
