<?php

defined( 'ABSPATH' ) || exit;

trait TransactionalTestClassTrait {

	/**
	 * Shared setup logic for transaction start.
	 */
	public static function setUpBeforeClass() : void {
		parent::setUpBeforeClass();
		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );
	}



	/**
	 * Shared teardown logic for transaction rollback.
	 */
	public static function tearDownAfterClass() : void {
		global $wpdb;
		$wpdb->query( 'ROLLBACK' );
		parent::tearDownAfterClass();
	}
}
