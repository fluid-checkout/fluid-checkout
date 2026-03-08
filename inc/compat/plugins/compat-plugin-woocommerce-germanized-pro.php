<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Germanized for WooCommerce Pro (by Vendidero)
 */
class FluidCheckout_WooCommerceGermanizedPRO extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->hooks();
	}



	/**
	 * Check if the compatibility should be loaded.
	 *
	 * @return  bool  True if the compatibility should be loaded, false otherwise.
	 */
	public function is_compat_active() {
		// Bail if Germanized Lite not active
		if ( ! is_plugin_active( 'woocommerce-germanized/woocommerce-germanized.php' ) ) { return false; }

		// Bail if class not available or Germanized PRO not active
		if ( ! class_exists( 'WC_GZDP_Dependencies' ) || ! property_exists( 'WC_GZDP_Dependencies', 'instance' ) || ! WC_GZDP_Dependencies::instance()->loadable ) { return false; }

		return true;
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Enqueue hooks
		// Should be loaded before checking whether plugin is completely active
		// to prevent loading styles when the plugin is not active yet
		add_action( 'fc_enable_compat_plugin_style_woocommerce-germanized-pro', array( $this, 'maybe_prevent_loading_styles' ), 10 );
		add_action( 'fc_enable_compat_plugin_edit_address_style_woocommerce-germanized-pro', array( $this, 'maybe_prevent_loading_styles' ), 10 );

		// Bail if compatibility is not active
		if ( ! $this->is_compat_active() ) { return; }

		// VAT validation
		$this->vat_validation_hooks();

		// Maybe force disable multistep checkout option
		$this->maybe_disable_multistep_checkout();
	}

	/**
	 * Initialize VAT validation hooks.
	 */
	public function vat_validation_hooks() {
		// Bail if VAT helper class not available
		if ( ! class_exists( 'WC_GZDP_VAT_Helper' ) ) { return; }

		// Maybe add VAT validation hooks
		if ( 'yes' === get_option( 'woocommerce_gzdp_enable_vat_check' ) ) {
			// Substep review text
			add_filter( 'fc_substep_text_shipping_address_field_keys_skip_list' , array( $this, 'add_vat_id_field_step_review_text_skip_list' ), 10 );
			add_filter( 'fc_substep_text_billing_address_field_keys_skip_list' , array( $this, 'add_vat_id_field_step_review_text_skip_list' ), 10 );
		}
	}



	/**
	 * Maybe prevent loading styles when the plugin is not active yet.
	 */
	public function maybe_prevent_loading_styles( $is_style_enabled ) {
		// Bail if compatibility is active
		if ( $this->is_compat_active() ) { return $is_style_enabled; }

		// Prevent loading styles
		return false;
	}



	/**
	 * Maybe force disable multistep checkout option from Germanized Pro.
	 */
	public function maybe_disable_multistep_checkout() {
		$option_key = 'woocommerce_gzdp_checkout_enable';

		// Bail if option is already disabled
		if ( 'no' === FluidCheckout_Settings::instance()->get_option( $option_key ) ) { return; }

		// Disable multistep checkout option
		// Need to update the option value because it is not possible to change it via hooks
		update_option( $option_key, 'no' );
	}



	/**
	 * Add address save checkbox fields to the substep review text skip list.
	 *
	 * @param   array  $field_keys_skip_list  The list of fields to skip adding to the substep review text.
	 */
	public function add_vat_id_field_step_review_text_skip_list( $field_keys_skip_list ) {
		// Bail if not an array
		if ( ! is_array( $field_keys_skip_list ) ) { return $field_keys_skip_list; }

		$field_keys_skip_list = array_merge( $field_keys_skip_list, array(
			'shipping_vat_id',
			'billing_vat_id',
		) );

		return $field_keys_skip_list;
	}

}

FluidCheckout_WooCommerceGermanizedPRO::instance();
