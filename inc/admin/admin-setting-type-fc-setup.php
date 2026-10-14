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
				'label' => sprintf( __( 'Check if there are any <a href="%s">integration options</a> available for the theme or other plugins you have installed.', 'fluid-checkout' ), esc_url( $settings_page->get_settings_url( 'integrations' ) ) ),
			),
			array(
				'id'    => 'tracking',
				/* translators: %s: Tools settings URL. */
				'label' => sprintf( __( 'Help us improve compatibility and measure impact. <a href="%s">Enable usage tracking</a> from the tools settings.', 'fluid-checkout' ), esc_url( $settings_page->get_settings_url( 'tools' ) ) ),
			),
			array(
				'id'         => 'optimized_pages',
				/* translators: %1$s: Cart settings URL. %2$s: Thank you settings URL. %3$s: Order pay settings URL. */
				'label'      => sprintf( __( 'Enable optimized <a href="%1$s">cart</a>, <a href="%2$s">thank you</a> and <a href="%3$s">order pay</a> pages.', 'fluid-checkout' ), esc_url( $settings_page->get_settings_url( 'cart' ) ), esc_url( $settings_page->get_settings_url( 'order_received' ) ), esc_url( $settings_page->get_settings_url( 'order_pay' ) ) ),
				'badge'      => 'setup-optimized-pages',
				'requires_pro' => true,
			),
			array(
				'id'         => 'address_autocomplete',
				/* translators: %s: Address autocomplete settings URL. */
				'label'      => sprintf( __( 'Enable <a href="%s">address autocomplete</a> from Google Maps.', 'fluid-checkout' ), esc_url( $settings_page->get_settings_url( 'address_autocomplete' ) ) ),
				'badge'      => 'setup-address-autocomplete',
				'requires_pro' => true,
			),
			array(
				'id'         => 'address_book',
				/* translators: %s: Address book settings URL. */
				'label'      => sprintf( __( 'Enable multiple saved addresses with <a href="%s">address book</a>.', 'fluid-checkout' ), esc_url( $settings_page->get_settings_url( 'address_book' ) ) ),
				'badge'      => 'setup-address-book',
				'requires_pro' => true,
			),
		);

		/**
		 * Filter the getting-started checklist steps on the Dashboard.
		 *
		 * @param  array[]  $steps  Checklist steps with `id`, `label`, optional `badge`, and optional `requires_pro` keys.
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

		// PRO feature steps: only complete while PRO is active, even if options stay enabled in the database
		if ( in_array( $step_id, array( 'optimized_pages', 'address_autocomplete', 'address_book' ), true ) ) {
			// Bail if PRO is not activated — keep the step number visible
			if ( ! FluidCheckout::instance()->is_pro_activated() ) { return false; }

			$settings = FluidCheckout_Settings::instance();

			if ( 'optimized_pages' === $step_id ) {
				return 'yes' === $settings->get_option( 'fc_pro_enable_cart_page' )
					&& 'yes' === $settings->get_option( 'fc_pro_enable_order_received' )
					&& 'yes' === $settings->get_option( 'fc_pro_enable_order_pay' );
			}

			if ( 'address_autocomplete' === $step_id ) {
				return $this->is_address_autocomplete_step_complete( $settings );
			}

			if ( 'address_book' === $step_id ) {
				return 'yes' === $settings->get_option( 'fc_pro_enable_address_book' );
			}
		}

		return in_array( $step_id, $this->get_completed_steps(), true );
	}



	/**
	 * Whether the address autocomplete checklist step is complete.
	 * Brasil API alone is enough. Google Maps requires the feature enabled and a successfully tested API key.
	 *
	 * @param   FluidCheckout_Settings  $settings  Settings instance.
	 * @return  bool
	 */
	public function is_address_autocomplete_step_complete( $settings ) {
		// Brasil API does not need a Google API key
		if ( 'yes' === $settings->get_option( 'fc_gaa_enabled_brasil_api' ) ) {
			return true;
		}

		// Bail if Google address autocomplete is not enabled
		if ( 'yes' !== $settings->get_option( 'fc_gaa_enabled' ) ) { return false; }

		$api_key = $settings->get_option( 'fc_gaa_google_places_api_key' );

		// Bail if the Google API key is empty
		if ( empty( $api_key ) ) { return false; }

		$validated_hash = $settings->get_option( 'fc_gaa_google_places_api_key_validated_hash' );

		// Bail if the saved key has not been tested successfully
		if ( empty( $validated_hash ) ) { return false; }

		return hash_equals( (string) $validated_hash, wp_hash( (string) $api_key ) );
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

						$badge_html = '';
						if ( ! empty( $step['badge'] ) && class_exists( 'FluidCheckout_Admin' ) ) {
							$badge_html = FluidCheckout_Admin::instance()->get_pro_feature_badge_html( $step['badge'] );
						}
						?>
						<li class="<?php echo esc_attr( $item_class ); ?>">
							<span class="fc-dashboard-checklist__marker" aria-hidden="true"></span>
							<span class="fc-dashboard-checklist__label"><?php echo wp_kses_post( isset( $step['label'] ) ? $step['label'] : '' ); ?></span>
							<?php if ( ! empty( $badge_html ) ) : ?>
								<span class="fc-dashboard-checklist__promo"><?php echo wp_kses_post( $badge_html ); ?></span>
							<?php endif; ?>
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
