<?php
defined( 'ABSPATH' ) || exit;

/**
 * Store profiles, named sets of values saved as posts of one post type, with an active profile and a context profile overlay.
 */
abstract class FluidCheckout_ProfileStorage extends FluidCheckout {

	/**
	 * Runtime callback for the context profile hook, removed before the next one is added.
	 *
	 * Intentionally private. Only `set_context_profile()` should replace this callback.
	 *
	 * @var callable|null
	 */
	private $context_profile_callback = null;



	/**
	 * Get the post type that stores the profiles.
	 */
	abstract public function get_profile_post_type();

	/**
	 * Get the option key that holds the active profile slug.
	 */
	abstract public function get_active_profile_option();

	/**
	 * Get the object cache group for the profile values.
	 */
	abstract public function get_cache_group();

	/**
	 * Get the name of the hook that sets the context profile slug for the current request.
	 */
	abstract public function get_context_profile_hook_name();

	/**
	 * Get the context profile slug requested for the current request, which might not exist.
	 */
	abstract public function get_requested_context_profile_slug();

	/**
	 * Get the default values, which the profile values are merged into.
	 */
	abstract public function get_default_values();



	/**
	 * Get a WordPress option that holds profile storage data, such as the active profile slug.
	 *
	 * @param  string  $option   Option name.
	 * @param  mixed   $default  Default value.
	 */
	public function get_raw_option( $option, $default = false ) {
		return get_option( $option, $default );
	}

	/**
	 * Update a WordPress option that holds profile storage data.
	 *
	 * @param  string  $option    Option name.
	 * @param  mixed   $value     Option value.
	 * @param  bool    $autoload  Whether to autoload.
	 */
	public function update_raw_option( $option, $value, $autoload = false ) {
		return update_option( $option, $value, $autoload );
	}



	/**
	 * Get the active profile slug.
	 */
	public function get_active_profile_slug() {
		// Get sanitized active profile slug
		$slug = $this->get_raw_option( $this->get_active_profile_option(), '' );
		$slug = is_string( $slug ) ? sanitize_title( $slug ) : '';

		return $slug;
	}

	/**
	 * Set the active profile slug.
	 *
	 * @param  string  $slug  Profile slug.
	 */
	public function set_active_profile( $slug ) {
		// Save sanitized slug as the active profile
		$slug = sanitize_title( (string) $slug );
		$this->update_raw_option( $this->get_active_profile_option(), $slug, false );

		// Clear cached values of the previous active profile
		$this->invalidate_cache();
	}

	/**
	 * Get the context profile slug for the current request, or empty when none.
	 */
	public function get_context_profile_slug() {
		// Get sanitized context profile slug
		$slug = $this->get_requested_context_profile_slug();
		$slug = is_string( $slug ) ? sanitize_title( $slug ) : '';

		// Ignore invalid or missing context profiles
		if ( '' === $slug || null === $this->get_profile_post_id_by_slug( $slug ) ) {
			return '';
		}

		return $slug;
	}

	/**
	 * Set the context profile slug for the current request via a one-off hook callback.
	 * Prefer hooking the context profile hook from feature code instead.
	 *
	 * @param  string|null  $slug  Profile slug or null to clear.
	 */
	public function set_context_profile( $slug ) {
		// Get sanitized slug, empty to clear the context profile
		$slug = null === $slug ? '' : sanitize_title( (string) $slug );

		// Remove only the previous runtime setter so other callbacks stay registered
		if ( is_callable( $this->context_profile_callback ) ) {
			remove_filter( $this->get_context_profile_hook_name(), $this->context_profile_callback, 10 );
			$this->context_profile_callback = null;
		}

		// Overlay a profile for the rest of this request
		if ( '' !== $slug ) {
			$this->context_profile_callback = function() use ( $slug ) {
				return $slug;
			};
			add_filter( $this->get_context_profile_hook_name(), $this->context_profile_callback, 10 );
		}

		// Clear cached effective values of the previous context profile
		$this->invalidate_effective_cache();
	}



	/**
	 * Get the profile values map for a slug.
	 *
	 * @param  string  $slug  Profile slug.
	 */
	public function get_profile_values( $slug ) {
		// Sanitize slug
		$slug = sanitize_title( (string) $slug );

		// Bail if slug is empty
		if ( '' === $slug ) { return array(); }

		// Get cached payload
		$cache_key = 'payload:' . $slug;
		$cached = wp_cache_get( $cache_key, $this->get_cache_group() );

		// Return cached payload when available
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		// Get profile post ID
		$post_id = $this->get_profile_post_id_by_slug( $slug );

		// Bail if profile post is missing
		if ( null === $post_id ) {
			wp_cache_set( $cache_key, array(), $this->get_cache_group() );
			return array();
		}

		// Get profile post
		$post = get_post( $post_id );

		// Bail if post is unreadable
		if ( ! $post || $this->get_profile_post_type() !== $post->post_type ) {
			wp_cache_set( $cache_key, array(), $this->get_cache_group() );
			return array();
		}

		// Get stored payload
		$raw_values = $post->post_content;

		// Unserialize profile payload without allowing object instantiation
		if ( is_string( $raw_values ) ) {
			$values = unserialize( $raw_values, array( 'allowed_classes' => false ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
		}
		// Otherwise, unserialize values that are not stored as a string
		else {
			$values = maybe_unserialize( $raw_values );
		}

		// Fail soft to empty map on invalid payload
		if ( ! is_array( $values ) ) {
			$values = array();
		}

		// Cache payload
		wp_cache_set( $cache_key, $values, $this->get_cache_group() );

		return $values;
	}

	/**
	 * Save a profile values map by slug.
	 *
	 * @param  string  $slug    Profile slug.
	 * @param  array   $values  Key => value map.
	 */
	public function save_profile_values( $slug, $values ) {
		// Sanitize slug
		$slug = sanitize_title( (string) $slug );

		// Bail if slug is empty
		if ( '' === $slug ) { return false; }

		// Get profile post ID and the serialized payload
		$values = is_array( $values ) ? $values : array();
		$post_id = $this->get_profile_post_id_by_slug( $slug );
		$post_content = serialize( $values ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Intentional for CPT post_content payload.

		// Create the profile post without the payload. `wp_insert_post()` runs `content_save_pre`, which kses and slashes would corrupt a serialized map.
		if ( null === $post_id ) {
			$post_id = wp_insert_post(
				array(
					'post_type'    => $this->get_profile_post_type(),
					'post_status'  => 'publish',
					'post_title'   => $slug,
					'post_name'    => $slug,
					'post_content' => '',
				),
				true
			);
		}

		// Bail if the profile post could not be created
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return false;
		}

		// Write payload
		$written = $this->write_profile_post_content( (int) $post_id, $post_content );

		// Bail if the payload could not be stored
		if ( ! $written ) { return false; }

		// Clear cached values of the profile
		$this->invalidate_cache_for_slug( $slug );

		// Get stored payload
		$stored = $this->get_profile_values( $slug );

		// Bail if the stored payload does not round-trip, so callers do not treat a corrupt write as saved
		if ( serialize( $stored ) !== serialize( $values ) ) { // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
			return false;
		}

		return true;
	}

	/**
	 * Write a profile payload directly, bypassing post content filters.
	 *
	 * @param  int     $post_id       Profile post ID.
	 * @param  string  $post_content  Serialized values map.
	 */
	public function write_profile_post_content( $post_id, $post_content ) {
		global $wpdb;

		// Update post content in the database
		$updated = $wpdb->update(
			$wpdb->posts,
			array(
				'post_content' => $post_content,
			),
			array(
				'ID' => $post_id,
			),
			array( '%s' ),
			array( '%d' )
		);

		// Clear cached post data
		clean_post_cache( $post_id );

		return false !== $updated;
	}



	/**
	 * Get the effective values map for the current request (context overlay on active).
	 */
	public function get_effective_values() {
		return $this->get_stored_effective_values();
	}

	/**
	 * Get the effective values map of the stored profiles for the current request (context overlay on active), cached.
	 */
	public function get_stored_effective_values() {
		// Get cached effective map for the active and context profiles
		$active_slug = $this->get_active_profile_slug();
		$context_slug = $this->get_context_profile_slug();
		$cache_key = $this->get_effective_cache_key( $active_slug, $context_slug );
		$cached = wp_cache_get( $cache_key, $this->get_cache_group() );

		// Return cached effective map when available
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		// Start from the default values
		$defaults = $this->get_default_values();
		$effective = is_array( $defaults ) ? $defaults : array();

		// Overlay active profile when available
		if ( '' !== $active_slug ) {
			// Get active profile values
			$active_values = $this->get_profile_values( $active_slug );

			// Maybe merge active profile values
			if ( ! empty( $active_values ) ) {
				$effective = array_merge( $effective, $active_values );
			}
		}

		// Overlay sparse context profile when available
		if ( '' !== $context_slug ) {
			// Get context profile values
			$context_values = $this->get_profile_values( $context_slug );

			// Maybe merge context profile values
			if ( ! empty( $context_values ) ) {
				$effective = array_merge( $effective, $context_values );
			}
		}

		// Cache effective map
		wp_cache_set( $cache_key, $effective, $this->get_cache_group() );

		return $effective;
	}

	/**
	 * Get the cache key of the effective values map for an active and a context profile.
	 *
	 * @param  string  $active_slug   Active profile slug, or empty when none.
	 * @param  string  $context_slug  Context profile slug, or empty when none.
	 */
	public function get_effective_cache_key( $active_slug, $context_slug ) {
		return 'effective:' . ( '' !== $active_slug ? $active_slug : 'none' ) . ':' . ( '' !== $context_slug ? $context_slug : 'none' );
	}



	/**
	 * Update values on the active profile (merge into existing payload).
	 *
	 * @param  array  $values  Key => value map to merge.
	 */
	public function update_active_profile_values( $values ) {
		// Make sure values are an array
		$values = is_array( $values ) ? $values : array();

		// Bail if nothing to save
		if ( empty( $values ) ) { return false; }

		// Get active profile slug
		$active_slug = $this->get_active_profile_slug();

		// Maybe create the default profile when none is active
		if ( '' === $active_slug ) {
			$active_slug = 'default';
			$this->set_active_profile( $active_slug );
		}

		// Merge new values into the active profile values
		$current = $this->get_profile_values( $active_slug );
		$merged = array_merge( is_array( $current ) ? $current : array(), $values );

		return $this->save_profile_values( $active_slug, $merged );
	}



	/**
	 * Get the profile post ID for a slug, or null when missing.
	 *
	 * @param  string  $slug  Profile slug.
	 */
	public function get_profile_post_id_by_slug( $slug ) {
		// Sanitize slug
		$slug = sanitize_title( (string) $slug );

		// Bail if slug is empty
		if ( '' === $slug ) { return null; }

		// Get profile post by slug
		$posts = get_posts(
			array(
				'name'             => $slug,
				'post_type'        => $this->get_profile_post_type(),
				'post_status'      => array( 'publish', 'private', 'draft' ),
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'suppress_filters' => true,
			)
		);

		// Bail if the profile post does not exist
		if ( empty( $posts ) ) { return null; }

		return (int) $posts[0];
	}



	/**
	 * Invalidate all profile cache entries.
	 */
	public function invalidate_cache() {
		// Flush the object cache group when supported
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			wp_cache_flush_group( $this->get_cache_group() );
			return;
		}

		// Fall back to deleting known keys for the active and context profiles
		$active_slug = $this->get_active_profile_slug();
		$context_slug = $this->get_context_profile_slug();
		$this->invalidate_cache_for_slug( $active_slug );

		// Maybe delete the context profile keys
		if ( '' !== $context_slug ) {
			$this->invalidate_cache_for_slug( $context_slug );
		}

		// Delete effective maps
		$this->invalidate_effective_cache();
	}

	/**
	 * Invalidate cache entries for a profile slug.
	 *
	 * @param  string  $slug  Profile slug.
	 */
	public function invalidate_cache_for_slug( $slug ) {
		// Sanitize slug
		$slug = sanitize_title( (string) $slug );

		// Bail if slug is empty
		if ( '' === $slug ) { return; }

		// Delete cached payload and effective maps
		wp_cache_delete( 'payload:' . $slug, $this->get_cache_group() );
		$this->invalidate_effective_cache();
	}

	/**
	 * Invalidate effective merged maps.
	 */
	public function invalidate_effective_cache() {
		// Get active profile slug
		$active_slug = $this->get_active_profile_slug();

		// Delete effective maps with and without the context profile, and the one without profiles
		wp_cache_delete( $this->get_effective_cache_key( $active_slug, '' ), $this->get_cache_group() );
		wp_cache_delete( $this->get_effective_cache_key( $active_slug, $this->get_context_profile_slug() ), $this->get_cache_group() );
		wp_cache_delete( $this->get_effective_cache_key( '', '' ), $this->get_cache_group() );
	}

}
