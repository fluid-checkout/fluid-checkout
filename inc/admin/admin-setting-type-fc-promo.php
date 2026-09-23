<?php
defined( 'ABSPATH' ) || exit;

/**
 * Promo card field type for the Fluid Checkout settings page.
 */
class FluidCheckout_Admin_SettingType_Promo extends FluidCheckout {

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
		add_action( 'fc_admin_settings_render_field_fc_promo', array( $this, 'output_field' ), 10 );
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		$title = isset( $value[ 'title' ] ) ? $value[ 'title' ] : '';
		$tagline = isset( $value[ 'tagline' ] ) ? $value[ 'tagline' ] : '';
		$features = isset( $value[ 'features' ] ) && is_array( $value[ 'features' ] ) ? $value[ 'features' ] : array();
		$promo_html = isset( $value[ 'promo' ] ) ? $value[ 'promo' ] : FluidCheckout_Admin::instance()->get_pro_feature_badge_html( isset( $value[ 'id' ] ) ? $value[ 'id' ] : '' );
		$learn_more_url = isset( $value[ 'learn_more_url' ] ) ? $value[ 'learn_more_url' ] : '';
		$learn_more_label = isset( $value[ 'learn_more_label' ] ) ? $value[ 'learn_more_label' ] : __( 'Learn more', 'fluid-checkout' );
		$card_id = ! empty( $value[ 'id' ] ) ? sanitize_title( $value[ 'id' ] ) . '-card' : '';
		?>
		<div class="fc-settings-card fc-settings-card--promo" <?php echo ! empty( $card_id ) ? 'id="' . esc_attr( $card_id ) . '"' : ''; ?>>
			<div class="fc-settings-card__header">
				<?php if ( ! empty( $title ) ) : ?>
					<h3 class="fc-settings-card__title"><?php echo esc_html( $title ); ?></h3>
				<?php endif; ?>
				<?php if ( ! empty( $promo_html ) ) : ?>
					<div class="fc-settings-card__promo"><?php
						echo wp_kses(
							$promo_html,
							array(
								'a' => array(
									'class'  => true,
									'href'   => true,
									'target' => true,
									'rel'    => true,
								),
								'span' => array(
									'class' => true,
								),
							)
						);
					?></div>
				<?php endif; ?>
			</div>

			<div class="fc-settings-card__inner">
				<?php if ( ! empty( $tagline ) ) : ?>
					<p class="fc-settings-promo__tagline"><?php echo esc_html( $tagline ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $features ) ) : ?>
					<ul class="fc-settings-promo__features">
						<?php foreach ( $features as $feature ) : ?>
							<li><?php echo esc_html( $feature ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $learn_more_url ) ) : ?>
				<div class="fc-settings-card__footer">
					<a class="fc-settings-button" href="<?php echo esc_url( $learn_more_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $learn_more_label ); ?></a>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

}

FluidCheckout_Admin_SettingType_Promo::instance();
