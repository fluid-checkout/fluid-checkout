<?php
defined( 'ABSPATH' ) || exit;

/**
 * Checkout admin options.
 */
class FluidCheckout_Admin_SettingType_LayoutSelector extends FluidCheckout {

	/**
	 * Field option styles accumulated during field rendering.
	 *
	 * @var string
	 */
	private $field_styles = '';

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
		add_action( 'woocommerce_admin_field_fc_layout_selector', array( $this, 'output_field' ), 10 );
	}



	/**
	 * Accumulate styles from field options.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function collect_option_styles( $value ) {
		// Iterate over options and accumulate styles
		foreach ( $value[ 'options' ] as $key => $val ) {
			$option_image_url = apply_filters( 'fc_layout_selector_option_image_url', FluidCheckout::$directory_url . 'images/admin/fc-layout-'. esc_attr( $key ) .'.png', $key, $val );
			$this->field_styles .= '.forminp-fc_layout_selector .fc-checkout-layout__option[value="' . esc_attr( $key ) . '"]:after { background-image: url( ' . esc_url( $option_image_url ) . ' ); }' . "\n";
		}
	}

	/**
	 * Get accumulated field option styles.
	 *
	 * @return string  Accumulated field styles.
	 */
	public function get_collected_option_styles() {
		return $this->field_styles;
	}

	/**
	 * Clear accumulated field option styles.
	 */
	public function clear_collected_option_styles() {
		$this->field_styles = '';
	}



	/**
	 * Output opening tags for the field wrapper elements.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field_wrapper_start_tag( $value ) {
		// Description handling.
		$field_description = WC_Admin_Settings::get_field_description( $value );
		$tooltip_html      = $field_description[ 'tooltip_html' ];
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value[ 'id' ] ); ?>"><?php echo esc_html( $value[ 'title' ] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value[ 'type' ] ) ); ?>">
				<fieldset>
				<?php
	}

	/**
	 * Output closing tags for the field wrapper elements.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field_wrapper_end_tag( $value ) {
		// Description handling.
		$field_description = WC_Admin_Settings::get_field_description( $value );
		$description       = $field_description[ 'description' ];

		// Output accumulated field styles and reset for the next fieldgroup
		$option_styles = $this->get_collected_option_styles();
		$this->clear_collected_option_styles();
		?>
				</fieldset>
				<?php echo $description; // WPCS: XSS ok. ?>
				<style>
					<?php echo $option_styles; // WPCS: XSS ok. ?>
				</style>
			</td>
		</tr>
		<?php
	}



	/**
	 * Output the setting field.
	 *
	 * @param   array  $value  Admin settings args values.
	 */
	public function output_field( $value ) {
		// Accumulate field option styles
		$this->collect_option_styles( $value );

		// Custom attribute handling.
		$custom_attributes_esc = array();
		if ( ! empty( $value[ 'custom_attributes' ] ) && is_array( $value[ 'custom_attributes' ] ) ) {
			foreach ( $value[ 'custom_attributes' ] as $attribute => $attribute_value ) {
				$custom_attributes_esc[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		$option_value = $value[ 'value' ];

		// Maybe add opening tags for the field wrapper elements
		if ( empty( $value[ 'fc_layout_group' ] ) || 'start' === $value[ 'fc_layout_group' ] ) {
			$this->output_field_wrapper_start_tag( $value );
		}
		?>

		<?php // Output available options ?>
		<ul>
			<?php foreach ( $value['options'] as $key => $args ) : ?>
				<li>
					<label <?php echo array_key_exists( 'disabled', $args ) && false !== $args[ 'disabled' ] ? 'class="disabled"' : ''; ?>><input
						name="<?php echo esc_attr( $value[ 'id' ] ); ?>"
						value="<?php echo esc_attr( $key ); ?>"
						type="radio"
						style="<?php echo esc_attr( $value[ 'css' ] ); ?>"
						class="<?php echo esc_attr( $value[ 'class' ] ); ?>"
						<?php echo implode( ' ', $custom_attributes_esc ); // WPCS: XSS ok. ?>
						<?php checked( $key, $option_value ); ?>
						<?php echo array_key_exists( 'disabled', $args ) && false !== $args[ 'disabled' ] ? 'disabled' : ''; ?>
						/> <?php echo esc_html( $args[ 'label' ] ); ?></label>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php
		// Maybe add closing tags for the field wrapper elements
		if ( empty( $value[ 'fc_layout_group' ] ) || 'end' === $value[ 'fc_layout_group' ] ) {
			$this->output_field_wrapper_end_tag( $value );
		}
	}

}

FluidCheckout_Admin_SettingType_LayoutSelector::instance();
