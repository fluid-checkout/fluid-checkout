<?php
defined( 'ABSPATH' ) || exit;

/**
 * Simple product for preview requests that exists in memory only, used to top up the preview cart.
 */
class FluidCheckout_Admin_Preview_Dummy_Product extends WC_Product_Simple {

	/**
	 * Set the dummy product properties instead of reading the product from the database.
	 *
	 * @param   int  $product  Dummy product ID.
	 */
	public function __construct( $product = 0 ) {
		// Initialize product data, skipping the parent constructors that read the product from the database
		WC_Data::__construct();
		$this->data_store = WC_Data_Store::load( 'product-' . $this->get_type() );

		// Declare the same support as the simple product constructor
		$this->supports[] = 'ajax_add_to_cart';

		// Set dummy product properties
		$this->set_id( $product );
		$this->set_props( FluidCheckout_Admin_Preview_Dummy_Data::instance()->get_product_props( $this->get_id() ) );
		$this->set_object_read( true );
	}



	/**
	 * Skip saving, as dummy products exist in memory only.
	 *
	 * @return  int  The dummy product ID.
	 */
	public function save() {
		return $this->get_id();
	}

	/**
	 * Skip deleting, as dummy products exist in memory only.
	 *
	 * @param   bool  $force_delete  Whether to delete permanently.
	 *
	 * @return  bool
	 */
	public function delete( $force_delete = false ) {
		return false;
	}

}
