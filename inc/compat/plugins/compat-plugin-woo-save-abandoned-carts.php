<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: CartBounty - Save and recover abandoned carts for WooCommerce (by Streamline.lv).
 */
class FluidCheckout_WooSaveAbandonedCarts extends FluidCheckout {

	/**
	 * Plugin class names.
	 */
	public $public_class_name = 'CartBounty_Public';
	public $admin_class_name  = 'CartBounty_Admin';


	/**
	 * Data prefix.
	 */
	public $prefix = 'cartbounty_';


	/**
	 * Script name.
	 */
	public $script_name = 'cartbounty';

	/**
	 * Script file path.
	 */
	public $script_file_path = 'js/compat/plugins/woo-save-abandoned-carts/cartbounty-public';



	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->maybe_set_pro_class_properties();
		$this->hooks();
	}

	/**
	 * Maybe set class properties for the CartBounty PRO plugin.
	 */
	public function maybe_set_pro_class_properties() {
		// Bail if class for CartBounty Lite was found, as it is the default plugin class name
		if ( class_exists( 'CartBounty_Public' ) && class_exists( 'CartBounty_Admin' ) ) { return; }

		// Otherwise, set the variables for the PRO plugin
		$this->public_class_name = 'CartBounty_Pro_Public';
		$this->admin_class_name  = 'CartBounty_Pro_Admin';
		$this->prefix            = 'cartbounty_pro_';
		$this->script_name       = 'cartbounty-pro';
		$this->script_file_path  = 'js/compat/plugins/woo-save-abandoned-carts-pro/cartbounty-pro-public';
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Bail if plugin class is not available
		if ( ! class_exists( $this->public_class_name ) ) { return; }

		// Get class object
		$class_object = $this->get_object_by_class_name_from_hooks( $this->public_class_name );

		// Bail if class object is not found in hooks
		if ( ! $class_object ) { return; }

		// Change the way input data is recovered from CartBounty to avoid conflicts with a similar feature from Fluid Checkout 
		remove_filter( 'wp', array( $class_object, 'restore_input_data' ), 10 );
		add_filter( 'wp', array( $this, 'maybe_restore_abandoned_cart_values_to_session' ), 10 );

		// Replace assets
		add_action( 'wp_enqueue_scripts', array( $this, 'replace_assets' ), 5 );
	}



	/**
	 * Maybe restore abandoned cart values to session.
	 */
	public function maybe_restore_abandoned_cart_values_to_session() {
		// Bail if not on checkout page or fragment
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Get class objects
		$public_class_object = $this->get_object_by_class_name_from_hooks( $this->public_class_name );
		$admin_class_object  = $this->get_object_by_class_name_from_hooks( $this->admin_class_name );

		// Bail if class objects are not found in hooks
		if ( ! $public_class_object || ! $admin_class_object ) { return; }

		// Bail if required methods are not found in the class objects
		if ( ! method_exists( $public_class_object, 'get_saved_cart' ) || ! method_exists( $admin_class_object, 'get_cart_location' ) ) { return; }

		// Get saved (abandoned) cart object
		$saved_cart = $public_class_object->get_saved_cart();

		// Bail if no saved cart found
		if ( ! $saved_cart ) { return; }

		// Get billing fields from the saved cart object
		$location_data = $admin_class_object->get_cart_location( $saved_cart->location );

		// Map the session data values to the saved cart values
		$billing_fields = array(
			'billing_first_name' => $saved_cart->name,
			'billing_last_name' => $saved_cart->surname,
			'billing_email' => $saved_cart->email,
			'billing_phone' => $saved_cart->phone,
			'billing_country' => $location_data[ 'country' ],
			'billing_city' => $location_data[ 'city' ],
			'billing_postcode' => $location_data[ 'postcode' ],
		);

		// Get the rest of the checkout fields
		$other_fields = maybe_unserialize( $saved_cart->other_fields );
		if ( ! is_array( $other_fields ) ) {
			$other_fields = array();
		}

		// Combine fields
		$checkout_fields = array_merge( $billing_fields, $other_fields );

		// Loop through the fields, and maybe set the session values
		foreach ( $checkout_fields as $key => $value ) {
			// Remove plugin prefix from the field keys
			$key = str_replace( $this->prefix, '', $key );

			// Set the field value to the session
			if ( null !== $value ) {
				FluidCheckout_Steps::instance()->set_checkout_field_value_to_session( $key, esc_html( $value ) );
			}
		}
	}



	/**
	 * Replace plugin assets.
	 */
	public function replace_assets() {
		// Enqueue the script with the same handle but different file path
		wp_enqueue_script( $this->script_name, FluidCheckout_Enqueue::instance()->get_script_url( $this->script_file_path ), array( 'jquery' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}

}

FluidCheckout_WooSaveAbandonedCarts::instance();
