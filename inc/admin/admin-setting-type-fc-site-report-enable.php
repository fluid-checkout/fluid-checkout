<?php
defined( 'ABSPATH' ) || exit;

/**
 * Site report enable checkbox with preview action.
 */
class FluidCheckout_Admin_SettingType_SiteReportEnable extends FluidCheckout {

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
		add_action( 'woocommerce_admin_field_fc_site_report_enable', array( $this, 'output_field' ), 10 );
		add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'sanitize_option_value' ), 10, 3 );
	}



	/**
	 * Sanitize the site report enable option value on save.
	 *
	 * @param mixed $value     Sanitized option value.
	 * @param array $option    Option definition.
	 * @param mixed $raw_value Raw option value.
	 */
	public function sanitize_option_value( $value, $option, $raw_value ) {
		if ( empty( $option['type'] ) || 'fc_site_report_enable' !== $option['type'] ) {
			return $value;
		}

		return ( '1' === $raw_value || 'yes' === $raw_value ) ? 'yes' : 'no';
	}



	/**
	 * Output the setting field.
	 *
	 * @param array $value Admin settings args values.
	 */
	public function output_field( $value ) {
		$option_value     = $value['value'];
		$visibility_class = array();
		$must_disable     = $value['disabled'] ?? false;
		$checkboxgroup    = $value['checkboxgroup'] ?? '';
		$has_title        = isset( $value['title'] ) && '' !== $value['title'];
		$label_text       = ! empty( $value['desc'] ) ? wp_kses_post( $value['desc'] ) : '';
		$desc_tip_html    = '';

		if ( ! empty( $value['desc_tip'] ) && true !== $value['desc_tip'] ) {
			$desc_tip_html = '<p class="description">' . wp_kses_post( $value['desc_tip'] ) . '</p>';
		}

		if ( ! isset( $value['hide_if_checked'] ) ) {
			$value['hide_if_checked'] = false;
		}

		if ( ! isset( $value['show_if_checked'] ) ) {
			$value['show_if_checked'] = false;
		}

		if ( 'yes' === $value['hide_if_checked'] || 'yes' === $value['show_if_checked'] ) {
			$visibility_class[] = 'hidden_option';
		}

		if ( 'option' === $value['hide_if_checked'] ) {
			$visibility_class[] = 'hide_options_if_checked';
		}

		if ( 'option' === $value['show_if_checked'] ) {
			$visibility_class[] = 'show_options_if_checked';
		}

		if ( ! empty( $value['row_class'] ) ) {
			$visibility_class[] = $value['row_class'];
		}

		if ( $must_disable ) {
			$visibility_class[] = 'disabled';
		}

		$container_class = implode( ' ', $visibility_class );
		$has_tooltip     = isset( $value['tooltip'] ) && '' !== $value['tooltip'];

		if ( ! isset( $checkboxgroup ) || 'start' === $checkboxgroup ) {
			$tooltip_container_class = $has_tooltip ? 'with-tooltip' : '';
			?>
			<tr class="<?php echo esc_attr( $container_class ); ?>">
				<th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ); ?></th>
				<td class="forminp forminp-checkbox <?php echo esc_attr( $tooltip_container_class ); ?>">
					<?php if ( $has_tooltip ) : ?>
						<span class="help-tooltip"><?php echo wc_help_tip( esc_html( $value['tooltip'] ) ); ?></span>
					<?php endif; ?>
					<fieldset>
			<?php
		} else {
			?>
			<fieldset class="<?php echo esc_attr( $container_class ); ?>">
			<?php
		}

		if ( $has_title ) {
			?>
			<legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ); ?></span></legend>
			<?php
		}
		?>
		<label for="<?php echo esc_attr( $value['id'] ); ?>">
			<input
				<?php echo $must_disable ? 'disabled' : ''; ?>
				name="<?php echo esc_attr( $value['field_name'] ); ?>"
				id="<?php echo esc_attr( $value['id'] ); ?>"
				type="checkbox"
				class="<?php echo esc_attr( isset( $value['class'] ) ? $value['class'] : '' ); ?>"
				value="1"
				<?php checked( $option_value, 'yes' ); ?>
			/>
			<?php echo $label_text; // WPCS: XSS ok. ?>
		</label>
		<?php echo $desc_tip_html; // WPCS: XSS ok. ?>
		<p class="fc-site-report-enable-actions">
			<button
				type="button"
				class="button button-secondary fc-site-report-preview-button"
				aria-haspopup="dialog"
			><?php esc_html_e( 'Preview report data', 'fluid-checkout' ); ?></button>
		</p>

		<?php
		if ( ! isset( $checkboxgroup ) || 'end' === $checkboxgroup ) {
			?>
					</fieldset>
				</td>
			</tr>
			<?php
		} else {
			?>
			</fieldset>
			<?php
		}
	}

}

FluidCheckout_Admin_SettingType_SiteReportEnable::instance();
