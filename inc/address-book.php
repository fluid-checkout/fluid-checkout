<?php

/**
 * Address book feature
 */
class FluidCheckout_AddressBook extends FluidCheckout {

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
		
	}

}

FluidCheckout_AddressBook::instance();
