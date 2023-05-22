<?php
defined( 'ABSPATH' ) || exit;

/**
 * Manage the design templates.
 */
class FluidCheckout_DesignTemplates extends FluidCheckout {

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



	/**
	 * Undo hooks.
	 */
	public function undo_hooks() {
          // TODO: REMOVE HOOKS
	}



     /**
	 * Get the design template option arguments.
	 *
	 * @return  array  Design templates arguments.
	 */
	public function get_design_template_options() {
		return array(
			'classic'     => array( 'label' => __( 'Classic', 'fluid-checkout' ) ),
			'modern'      => array( 'label' => __( 'Modern', 'fluid-checkout' ), 'disabled' => true ),
			'minimalist'  => array( 'label' => __( 'Minimalist', 'fluid-checkout' ), 'disabled' => true ),
		);
	}

	/**
	 * Return the list of values accepted for design templates.
	 *
	 * @return  array  List of values accepted for design templates.
	 */
	public function get_allowed_design_templates() {
		return array_keys( $this->get_design_template_options() );
	}

}

FluidCheckout_DesignTemplates::instance();
