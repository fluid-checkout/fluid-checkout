<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: FluentCRM Pro (by Fluent CRM).
 */
class FluidCheckout_FluentCampaignPRO extends FluidCheckout {

	/**
	 * Fluent Campaign PRO WooInit object.
	 */
	public $fluent_campaign_woo_init;

	/**
	 * Fluent Campaign PRO plugin version.
	 */
	public $fluent_campaign_plugin_version;



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
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Get plugin version
		$this->fluent_campaign_plugin_version = $this->get_plugin_version( 'fluentcampaign-pro/fluentcampaign-pro.php' );

		// Get the fluent campaign woo init objects
		$this->fluent_campaign_woo_init = $this->get_object_by_class_name_from_hooks( 'FluentCampaign\App\Services\Integrations\WooCommerce\WooInit' );

		// Subscribe box
		$this->subscribe_box_late_hooks();
	}
	
	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Subscribe box
		$this->subscribe_box_very_late_hooks();
	}

	/**
	 * Add or remove subscribe box hooks.
	 */
	public function subscribe_box_late_hooks() {
		// Bail if class or object not available
		if ( null === $this->fluent_campaign_woo_init ) { return; }

		// Versions 2.9.0+ and later
		if ( version_compare( $this->fluent_campaign_plugin_version, '2.9.0', '>=' ) ) {
			// Move subscribe box
			remove_filter( 'woocommerce_checkout_fields', array( $this->fluent_campaign_woo_init, 'addSubscribeBox' ), 1 );
			add_action( 'fc_checkout_contact_after_fields', array( $this, 'output_subscribe_box_field' ), 10 );
		}
	}

	/**
	 * Add or remove subscribe box hooks.
	 */
	public function subscribe_box_very_late_hooks() {
		// Bail if class or object not available
		if ( null === $this->fluent_campaign_woo_init ) { return; }

		// Versions up to 2.8.*
		if ( version_compare( $this->fluent_campaign_plugin_version, '2.9.0', '<' ) ) {
			// Move subscribe box
			remove_action( 'woocommerce_checkout_billing', array( $this->fluent_campaign_woo_init, 'addSubscribeBox' ), 999 );
			remove_action( 'woocommerce_before_order_notes', array( $this->fluent_campaign_woo_init, 'addSubscribeBox' ), 999 );
			add_action( 'fc_checkout_contact_after_fields', array( $this->fluent_campaign_woo_init, 'addSubscribeBox' ), 10 );
		}
	}



	/** 
	 * Maybe remove subscribe box field from the additional order fields section.
	 */
	public function output_subscribe_box_field() {
		// Bail if class not available
		if ( ! class_exists( 'FluentCrm\Framework\Support\Arr' ) ) { return; }

		// Initialize variables
		$field_key = '_fc_woo_checkout_subscribe';

		// COPIED and adapted from FluentCampaign\App\Services\Integrations\WooCommerce\WooInit::addSubscribeBox()
		$settings = fluentcrm_get_option('woo_checkout_form_subscribe_settings', []);

		if ( ! $settings || FluentCrm\Framework\Support\Arr::get( $settings, 'status' ) != 'yes' ) {
			// CHANGE: Return without value.
			return;
		}

		if ( FluentCrm\Framework\Support\Arr::get( $settings, 'show_only_new' ) == 'yes' ) {
			$contact = fluentcrm_get_current_contact();
			if ( $contact && $contact->status == 'subscribed' ) {
				// CHANGE: Return without value.
				return;
			}
		}

		$heading = FluentCrm\Framework\Support\Arr::get( $settings, 'checkbox_label' );

		// CHANGE: Use field key from variable.
		$defaultValue = WC()->checkout->get_value( $field_key );

		if ( FluentCrm\Framework\Support\Arr::get( $settings, 'auto_checked' ) == 'yes' ) {
			$defaultValue = '1';
		}

		// CHANGE: Remove code to get order fields, as they will not be used in this context

		// CHANGE: Define field args in a variable.
		$checkboxField = array(
			'type'          => 'checkbox',
			'label_class'   => 'fc_woo',
			'class'         => array('input-checkbox', 'fc_subscribe_woo'),
			'label'         => $heading,
			'checked_value' => '1',
			'default'       => $defaultValue
		);
		// END - COPIED and adapted from FluentCampaign\App\Services\Integrations\WooCommerce\WooInit::addSubscribeBox()

		// Output the field directly
		woocommerce_form_field( $field_key, $checkboxField, $defaultValue );
	}

}

FluidCheckout_FluentCampaignPRO::instance();
