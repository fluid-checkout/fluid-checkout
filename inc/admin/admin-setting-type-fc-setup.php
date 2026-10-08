<?php
defined( 'ABSPATH' ) || exit;

/**
 * Setup checklist card for the Dashboard tab of the Fluid Checkout settings page.
 */
class FluidCheckout_Admin_SettingType_Setup extends FluidCheckout {

	/**
	 * Option key for completed getting-started checklist steps.
	 */
	const CHECKLIST_OPTION = 'fc_setup_checklist_completed';



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
		// Field types
		add_action( 'fc_admin_settings_render_field_fc_setup', array( $this, 'output_field' ), 10 );

		// Mark checklist steps when visiting related settings tabs
		add_action( 'admin_init', array( $this, 'maybe_mark_checklist_steps_from_current_tab' ), 20 );
	}



	/**
	 * Get the getting-started checklist steps.
	 *
	 * @return  array[]
	 */
	public function get_checklist_steps() {
		$settings_page = FluidCheckout_Admin_Settings_Page::instance();

		$steps = array(
			array(
				'id'    => 'checkout',
				/* translators: %s: Checkout settings URL. */
				'label' => sprintf( __( 'Set up layout and design on the <a href="%s">checkout options</a>.', 'fluid-checkout' ), esc_url( $settings_page->get_settings_url( 'checkout' ) ) ),
			),
			array(
				'id'    => 'integrations',
				/* translators: %s: Integrations settings URL. */
				'label' => sprintf( __( 'Check if there are any <a href="%s">integration options</a> available for other plugins you have installed.', 'fluid-checkout' ), esc_url( $settings_page->get_settings_url( 'integrations' ) ) ),
			),
			array(
				'id'    => 'tracking',
				/* translators: %s: Tools settings URL. */
				'label' => sprintf( __( 'Help us improve compatibility and measure impact. <a href="%s">Enable usage tracking</a> from the tools settings.', 'fluid-checkout' ), esc_url( $settings_page->get_settings_url( 'tools' ) ) ),
			),
		);

		/**
		 * Filter the getting-started checklist steps on the Dashboard.
		 *
		 * @param  array[]  $steps  Checklist steps with `id` and `label` keys.
		 */
		return apply_filters( 'fc_setup_checklist_steps', $steps );
	}



	/**
	 * Get completed checklist step ids.
	 *
	 * @return  string[]
	 */
	public function get_completed_steps() {
		$completed = get_option( self::CHECKLIST_OPTION, array() );

		if ( ! is_array( $completed ) ) {
			return array();
		}

		return array_values( array_filter( array_map( 'sanitize_key', $completed ) ) );
	}



	/**
	 * Whether a checklist step is completed.
	 *
	 * @param   string  $step_id  Step id.
	 * @return  bool
	 */
	public function is_step_completed( $step_id ) {
		$step_id = sanitize_key( $step_id );

		// Usage tracking is complete only when reporting is enabled
		if ( 'tracking' === $step_id ) {
			return 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_telemetry_enabled' );
		}

		return in_array( $step_id, $this->get_completed_steps(), true );
	}



	/**
	 * Mark a checklist step as completed.
	 *
	 * @param   string  $step_id  Step id.
	 */
	public function mark_step_completed( $step_id ) {
		$step_id = sanitize_key( $step_id );

		// Bail if step id is empty
		if ( empty( $step_id ) ) { return; }

		// Bail if already completed
		if ( $this->is_step_completed( $step_id ) ) { return; }

		$completed   = $this->get_completed_steps();
		$completed[] = $step_id;
		$completed   = array_values( array_unique( $completed ) );

		update_option( self::CHECKLIST_OPTION, $completed, false );
	}



	/**
	 * Maybe mark checklist steps when visiting related settings tabs.
	 */
	public function maybe_mark_checklist_steps_from_current_tab() {
		// Bail if settings page class is missing
		if ( ! class_exists( 'FluidCheckout_Admin_Settings_Page' ) ) { return; }

		$settings_page = FluidCheckout_Admin_Settings_Page::instance();

		// Bail if not on the Fluid Checkout settings page
		if ( ! $settings_page->is_settings_page() ) { return; }

		// Bail if user cannot manage settings
		if ( ! current_user_can( FluidCheckout_Admin_Settings_Page::CAPABILITY ) ) { return; }

		$tab_to_step = array(
			'checkout'     => 'checkout',
			'integrations' => 'integrations',
		);

		$current_tab = $settings_page->get_current_tab();

		// Bail if the current tab does not map to a checklist step
		if ( ! array_key_exists( $current_tab, $tab_to_step ) ) { return; }

		$this->mark_step_completed( $tab_to_step[ $current_tab ] );
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		$settings_page = FluidCheckout_Admin_Settings_Page::instance();
		$dashboard_url = $settings_page->get_settings_url( 'dashboard' );
		$steps         = $this->get_checklist_steps();
		?>
		<div class="fc-settings-card fc-settings-card--setup">
			<div class="fc-settings-card__header">
				<h3 class="fc-settings-card__title"><?php echo esc_html( __( 'Getting started', 'fluid-checkout' ) ); ?></h3>
			</div>

			<div class="fc-settings-card__inner">
				<p><?php echo wp_kses_post( __( '<strong>Great! Your checkout page is now running on Fluid Checkout.</strong>', 'fluid-checkout' ) . ' ' . __( 'Here are a few steps to get started:', 'fluid-checkout' ) ); ?></p>

				<ol class="fc-dashboard-checklist">
					<?php foreach ( $steps as $step ) : ?>
						<?php
						$step_id     = isset( $step['id'] ) ? sanitize_key( $step['id'] ) : '';
						$is_complete = ! empty( $step_id ) && $this->is_step_completed( $step_id );
						$item_class  = 'fc-dashboard-checklist__item';
						if ( $is_complete ) {
							$item_class .= ' is-completed';
						}
						?>
						<li class="<?php echo esc_attr( $item_class ); ?>">
							<span class="fc-dashboard-checklist__marker" aria-hidden="true"></span>
							<span class="fc-dashboard-checklist__label"><?php echo wp_kses_post( isset( $step['label'] ) ? $step['label'] : '' ); ?></span>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>

			<div class="fc-settings-card__footer">
				<a class="fc-settings-button fc-settings-button--primary" href="<?php echo esc_url( $dashboard_url ); ?>"><?php echo esc_html( __( 'Guided setup', 'fluid-checkout' ) ); ?></a>
			</div>
		</div>
		<?php
	}

}

FluidCheckout_Admin_SettingType_Setup::instance();
