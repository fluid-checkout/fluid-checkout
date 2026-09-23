<?php
defined( 'ABSPATH' ) || exit;

/**
 * Registry of unlocked features for the Fluid Checkout settings page.
 * Products call `unlock( $slug )` to enable their locked settings.
 */
class FluidCheckout_Admin_Settings_Access extends FluidCheckout {

	/**
	 * Unlocked feature slugs, as keys of the array.
	 *
	 * @var array
	 */
	private $unlocked = array();



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
		// Intentionally empty, products unlock features by calling `unlock()` directly.
	}



	/**
	 * Unlock a feature slug.
	 *
	 * @param  string  $slug  Feature slug, e.g. `pro`, `address_book`.
	 */
	public function unlock( $slug ) {
		$slug = sanitize_key( $slug );

		// Bail if slug is empty
		if ( empty( $slug ) ) { return; }

		$this->unlocked[ $slug ] = true;
	}

	/**
	 * Check whether a feature slug is unlocked.
	 *
	 * @param  string  $slug  Feature slug.
	 */
	public function is_unlocked( $slug ) {
		return in_array( sanitize_key( $slug ), $this->get_unlocked(), true );
	}

	/**
	 * Get the list of unlocked feature slugs.
	 */
	public function get_unlocked() {
		/**
		 * Filter the list of unlocked feature slugs for the Fluid Checkout settings page.
		 */
		return apply_filters( 'fc_admin_settings_unlocked', array_keys( $this->unlocked ) );
	}



	/**
	 * Check whether a setting requires a feature that is unlocked.
	 *
	 * @param  array  $setting  Settings field arguments.
	 */
	public function is_setting_unlocked( $setting ) {
		// Bail if setting does not require a feature
		if ( ! is_array( $setting ) || empty( $setting[ 'requires' ] ) ) { return false; }

		return $this->is_unlocked( $setting[ 'requires' ] );
	}

	/**
	 * Check whether a setting is locked, and should not be changed or saved.
	 *
	 * @param  array  $setting  Settings field arguments.
	 */
	public function is_setting_locked( $setting ) {
		return is_array( $setting ) && array_key_exists( 'disabled', $setting ) && false !== $setting[ 'disabled' ];
	}

	/**
	 * Enable settings that require unlocked features, including their options.
	 * Settings with `locked_only` set to `true`, such as upgrade notices, are removed when their feature is unlocked.
	 *
	 * @param  array  $settings  Settings arrays, same format as WooCommerce settings.
	 */
	public function apply_to_settings( $settings ) {
		// Bail if settings are not valid
		if ( ! is_array( $settings ) ) { return $settings; }

		foreach ( $settings as $setting_key => $setting ) {
			// Skip settings which do not require an unlocked feature
			if ( ! $this->is_setting_unlocked( $setting ) ) { continue; }

			// Remove settings only displayed while the feature is locked
			if ( ! empty( $setting[ 'locked_only' ] ) ) {
				unset( $settings[ $setting_key ] );
				continue;
			}

			// Enable setting
			unset( $settings[ $setting_key ][ 'disabled' ] );

			// Skip if setting does not have options
			if ( ! isset( $setting[ 'options' ] ) || ! is_array( $setting[ 'options' ] ) ) { continue; }

			// Enable options within the setting
			foreach ( $setting[ 'options' ] as $option_key => $option ) {
				// Skip options which are not arrays
				if ( ! is_array( $option ) ) { continue; }

				unset( $settings[ $setting_key ][ 'options' ][ $option_key ][ 'disabled' ] );
			}
		}

		return $settings;
	}

	/**
	 * Check whether the submitted value for a setting selects any of its locked options.
	 *
	 * @param  array  $setting  Settings field arguments.
	 * @param  array  $data     Submitted form data.
	 */
	public function has_locked_option_value( $setting, $data ) {
		// Bail if setting does not have options
		if ( ! isset( $setting[ 'options' ] ) || ! is_array( $setting[ 'options' ] ) ) { return false; }

		// Bail if setting does not have a submitted value
		if ( empty( $setting[ 'id' ] ) || ! isset( $data[ $setting[ 'id' ] ] ) ) { return false; }

		$submitted_values = array_map( 'strval', (array) $data[ $setting[ 'id' ] ] );

		foreach ( $setting[ 'options' ] as $option_key => $option ) {
			if ( $this->is_setting_locked( $option ) && in_array( (string) $option_key, $submitted_values, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Remove locked settings so they are not saved.
	 * Settings with a submitted value for a locked option are also removed, keeping their saved values.
	 *
	 * @param  array  $settings  Settings arrays, after applying unlocked features.
	 * @param  array  $data      Submitted form data.
	 */
	public function get_saveable_settings( $settings, $data = array() ) {
		// Bail if settings are not valid
		if ( ! is_array( $settings ) ) { return array(); }

		$data = is_array( $data ) ? wp_unslash( $data ) : array();

		return array_filter( $settings, function( $setting ) use ( $data ) {
			return is_array( $setting ) && ! $this->is_setting_locked( $setting ) && ! $this->has_locked_option_value( $setting, $data );
		} );
	}

}

FluidCheckout_Admin_Settings_Access::instance();
