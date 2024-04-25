<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Flexible Checkout Fields PRO (by WP Desk).
 */
class FluidCheckout_FlexibleCheckoutFieldsPRO extends FluidCheckout {

	/**
	 * Class name for hooks from the plugin.
	 */
	private $class_name = 'WPDesk\FCF\Pro\ConditionalLogic\ResultsProcessor';

	/**
	 * Class object for hooks from the plugin.
	 */
	private $class_object;

	/**
	 * Conditional logic results.
	 *
	 * @var array<string, array<string, boolean>>
	 */
	private $results;



	/**
	 * __construct function.
	 */
	public function __construct( ) {
		$this->hooks();
		$this->setup_conditional_logic_results();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Maybe set class object
		if ( class_exists( $this->class_name ) ) {
			// Get object
			$this->class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $this->class_name );
		}

		// Conditional logic
		$this->conditional_logic_hooks();
	}

	/**
	 * Add or remove conditional logic hooks.
	 */
	public function conditional_logic_hooks() {
		// Bail if plugin class is not available.
		if ( ! $this->class_object ) { return; }

		// Remove hooks for logic results field,
		// which might have been added to multiple hooks resulting in multiple fields.
		foreach ( ($this->class_name)::RESULTS_STORAGE_FIELD_FALLBACK_HOOKS as $hook ) {
			remove_action( $hook, array( $this->class_object, 'add_results_field' ), 10 );
		}

		// Add conditional logic hooks
		add_action( 'fc_checkout_before', array( $this->class_object, 'add_results_field' ), 10 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'prepare_checkout_fields_for_validation' ), 999999 );
		add_filter( 'woocommerce_billing_fields', array( $this, 'prepare_checkout_fields_group_for_validation' ), 999999 );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'prepare_checkout_fields_group_for_validation' ), 999999 );
	}



	/**
	 * Sets up the result storage for the class.
	 *
	 * COPIED FROM Flexible Checkout Fields PRO plugin.
	 * @see WPDesk\FCF\Pro\ConditionalLogic\ResultsProcessor::setup_results()
	 */
	public function setup_conditional_logic_results() {
		if ( isset( $_POST[ ($this->class_name)::RESULTS_STORAGE_FIELD_ID ] ) ) {
			$this->results = json_decode( urldecode( \wp_unslash( $_POST[ ($this->class_name)::RESULTS_STORAGE_FIELD_ID ] ) ), true );

			if ( json_last_error() !== JSON_ERROR_NONE ) {
				// CHANGE: Invalidate the results object but do not add another notice to the checkout error messages.
				$this->results = null;
			}
		}
	}

	/**
	 * Prepare the checkout fields for one field group for validation, based on conditional logic results.
	 * 
	 * COPIED AND ADAPTED FROM Flexible Checkout Fields PRO plugin.
	 * @see WPDesk\FCF\Pro\ConditionalLogic\ResultsProcessor::prepare_checkout_fields_for_validation()
	 */
	public function prepare_checkout_fields_group_for_validation( $fields ) {
		// Bail if no validation logic results are available.
		if ( ! is_array( $this->results ) || count( $this->results ) === 0 ) { return $fields; }

		// Iterate fields
		foreach ( $fields as $field_name => $field ) {
			// Skip fields that do not have conditional logic validations to apply
			if ( ! array_key_exists( $field_name, $this->results ) || ! is_array( $this->results[ $field_name ] ) ) { continue; } 

			// Iterate and apply field validation rules
			foreach ( $this->results[ $field_name ] as $action => $value ) {
				switch ( $action ) {
					case 'required':
						$fields[ $field_name ]['required'] = (bool) $value;
						break;
					case 'hide':
						if ( true === (bool) $value ) {
							unset( $fields[ $field_name ] );
						}
						break;
					case 'show':
						if ( false === (bool) $value ) {
							unset( $fields[ $field_name ] );
						}
						break;
				}
			}
		}

		return $fields;
	}

	/**
	 * Prepare the checkout fields for validation, based on conditional logic results.
	 * 
	 * COPIED AND ADAPTED FROM Flexible Checkout Fields PRO plugin.
	 * @see WPDesk\FCF\Pro\ConditionalLogic\ResultsProcessor::prepare_checkout_fields_for_validation()
	 */
	public function prepare_checkout_fields_for_validation( $fieldsets ) {
		// Bail if no validation logic results are available.
		if ( ! is_array( $this->results ) || count( $this->results ) === 0 ) { return $fieldsets; }

		// Iterate field groups
		foreach ( $fieldsets as $group_name => $group_fields ) {
			// Prepare fields for validation
			$fieldsets[ $group_name ] = $this->prepare_checkout_fields_group_for_validation( $group_fields );
		}

		return $fieldsets;
	}

}

FluidCheckout_FlexibleCheckoutFieldsPRO::instance(); 
