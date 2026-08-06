<?php
defined( 'ABSPATH' ) || exit;

/**
 * Trait for cleaning up WordPress options created during a test.
 */
trait OptionsTestClassTrait {

	/**
	 * Option keys touched during the current test.
	 *
	 * @var array
	 */
	protected $tracked_option_keys = array();

	/**
	 * Shared setup logic.
	 */
	public function setUp() : void {
		parent::setUp();
		$this->tracked_option_keys = array();

		// Maybe call the instance custom setUp method
		if ( method_exists( $this, 'setUpInstance' ) ) {
			$this->setUpInstance();
		}
	}

	/**
	 * Shared teardown logic.
	 */
	public function tearDown() : void {
		// Maybe call the instance custom tearDown method
		if ( method_exists( $this, 'tearDownInstance' ) ) {
			$this->tearDownInstance();
		}

		$this->cleanup_tracked_options();
		parent::tearDown();
	}

	/**
	 * Track an option key for cleanup after the test.
	 *
	 * @param  string  $option  Option name.
	 */
	protected function track_option( $option ) {
		$this->tracked_option_keys[] = $option;
	}

	/**
	 * Update an option and track it for cleanup.
	 *
	 * @param  string  $option  Option name.
	 * @param  mixed   $value   Option value.
	 */
	protected function set_tracked_option( $option, $value ) {
		$this->track_option( $option );
		update_option( $option, $value );
	}

	/**
	 * Delete all tracked options and flush caches.
	 */
	protected function cleanup_tracked_options() {
		foreach ( array_unique( $this->tracked_option_keys ) as $option ) {
			delete_option( $option );
		}

		$this->tracked_option_keys = array();
		wp_cache_flush();
	}

}
