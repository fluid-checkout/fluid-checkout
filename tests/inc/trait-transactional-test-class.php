<?php

defined( 'ABSPATH' ) || exit;

trait TransactionalTestClassTrait {

	/**
	 * Start a database transaction.
	 */
	public static function start_database_transaction() : void {
		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );
	}

	/**
	 * Roll back a database transaction.
	 */
	public static function rollback_database_transaction() : void {
		global $wpdb;
		$wpdb->query( 'ROLLBACK' );
	}

	/**
	 * Shared setup logic for transaction start.
	 */
	public function setUp() : void {
		parent::setUp();
		self::start_database_transaction();

		// Maybe call the instance custom setUp method.
		if ( method_exists( $this, 'setUpInstance' ) ) {
			$this->setUpInstance();
		}
	}

	/**
	 * Shared teardown logic for transaction rollback.
	 */
	public function tearDown() : void {
		// Maybe call the instance custom tearDown method.
		if ( method_exists( $this, 'tearDownInstance' ) ) {
			$this->tearDownInstance();
		}

		self::rollback_database_transaction();
		parent::tearDown();
	}
}
