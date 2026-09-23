<?php
defined( 'ABSPATH' ) || exit;

/**
 * Render settings arrays as cards and field rows on the Fluid Checkout settings page.
 * Uses the same settings arrays and field names as the WooCommerce settings API,
 * so values can be saved with `WC_Admin_Settings::save_fields()`.
 */
class FluidCheckout_Admin_Settings_Renderer extends FluidCheckout {

	/**
	 * Field types rendered with the standard input control.
	 */
	const INPUT_FIELD_TYPES = array( 'text', 'password', 'datetime', 'datetime-local', 'date', 'month', 'time', 'week', 'number', 'email', 'url', 'tel' );

	/**
	 * Whether a settings card is currently open.
	 *
	 * @var bool
	 */
	private $is_card_open = false;

	/**
	 * Whether a field row is currently open, waiting for more fields of the same group.
	 *
	 * @var bool
	 */
	private $is_field_row_open = false;

	/**
	 * Whether the field currently being rendered opened a new field row.
	 *
	 * @var bool
	 */
	private $is_current_field_row_start = true;



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
		// Standard field types
		foreach ( self::INPUT_FIELD_TYPES as $field_type ) {
			add_action( 'fc_admin_settings_render_field_' . $field_type, array( $this, 'output_field_input' ), 10 );
		}
		add_action( 'fc_admin_settings_render_field_color', array( $this, 'output_field_color' ), 10 );
		add_action( 'fc_admin_settings_render_field_textarea', array( $this, 'output_field_textarea' ), 10 );
		add_action( 'fc_admin_settings_render_field_select', array( $this, 'output_field_select' ), 10 );
		add_action( 'fc_admin_settings_render_field_multiselect', array( $this, 'output_field_select' ), 10 );
		add_action( 'fc_admin_settings_render_field_multi_select_countries', array( $this, 'output_field_select' ), 10 );
		add_action( 'fc_admin_settings_render_field_radio', array( $this, 'output_field_radio' ), 10 );
		add_action( 'fc_admin_settings_render_field_checkbox', array( $this, 'output_field_checkbox' ), 10 );
	}



	/**
	 * Output settings fields grouped into cards.
	 * Each `title` setting opens a card, and each `sectionend` setting closes it.
	 *
	 * @param  array  $settings  Settings arrays, same format as WooCommerce settings.
	 */
	public function output_fields( $settings ) {
		// Bail if settings are not valid
		if ( ! is_array( $settings ) ) { return; }

		foreach ( $settings as $value ) {
			// Skip invalid settings
			if ( ! is_array( $value ) || ! isset( $value[ 'type' ] ) ) { continue; }

			$value = $this->normalize_field_args( $value );

			switch ( $value[ 'type' ] ) {
				case 'title':
					$this->output_card_start( $value );
					break;

				case 'sectionend':
					$this->output_card_end( $value );
					break;

				default:
					// Output field types that render their own cards
					if ( true === $value[ 'is_card' ] ) {
						$this->close_open_card();
						$this->output_field( $value );
						break;
					}

					// Maybe open a card without header for fields outside of a section
					if ( ! $this->is_card_open ) {
						$this->output_card_start( array( 'id' => '', 'title' => '', 'desc' => '' ) );
					}

					// Close field row left open by a field group without an `end` field
					if ( $this->is_field_group_start( $value ) ) {
						$this->close_open_field_row();
					}

					$this->output_field( $value );
					break;
			}
		}

		// Close cards left open by settings without `sectionend`
		$this->close_open_card();
	}



	/**
	 * Normalize field arguments with the same defaults as the WooCommerce settings API.
	 *
	 * @param  array  $value  Settings field arguments.
	 */
	public function normalize_field_args( $value ) {
		$defaults = array(
			'id'                => '',
			'title'             => isset( $value[ 'name' ] ) ? $value[ 'name' ] : '',
			'class'             => '',
			'css'               => '',
			'default'           => '',
			'desc'              => '',
			'desc_tip'          => false,
			'placeholder'       => '',
			'row_class'         => '',
			'suffix'            => '',
			'is_card'           => false,
		);
		$value = wp_parse_args( $value, $defaults );

		// Maybe set field name from field ID
		if ( ! isset( $value[ 'field_name' ] ) ) {
			$value[ 'field_name' ] = $value[ 'id' ];
		}

		// Maybe add the WooCommerce row class prefix
		if ( ! empty( $value[ 'row_class' ] ) && 0 !== strpos( $value[ 'row_class' ], 'wc-settings-row-' ) ) {
			$value[ 'row_class' ] = 'wc-settings-row-' . $value[ 'row_class' ];
		}

		// Maybe get field value from saved options
		if ( ! isset( $value[ 'value' ] ) && ! in_array( $value[ 'type' ], array( 'title', 'sectionend' ), true ) ) {
			$value[ 'value' ] = $this->get_field_value( $value );
		}

		return $value;
	}

	/**
	 * Get the saved value for a settings field, or its default value.
	 *
	 * @param  array  $value  Settings field arguments.
	 */
	public function get_field_value( $value ) {
		// Bail if field does not have an ID
		if ( empty( $value[ 'id' ] ) ) { return $value[ 'default' ]; }

		$default = $value[ 'default' ];

		// Maybe get default value from the plugin settings
		if ( '' === $default ) {
			$plugin_default = FluidCheckout_Settings::instance()->get_option_default( $value[ 'id' ] );
			$default = null !== $plugin_default ? $plugin_default : $default;
		}

		return WC_Admin_Settings::get_option( $value[ 'id' ], $default );
	}



	/**
	 * Output opening tags for a settings card.
	 *
	 * @param  array  $value  Settings `title` arguments.
	 */
	public function output_card_start( $value ) {
		$this->close_open_card();

		$card_id = ! empty( $value[ 'id' ] ) ? sanitize_title( $value[ 'id' ] ) . '-card' : '';
		$docs_html = ! empty( $value[ 'docs' ] ) ? $value[ 'docs' ] : '';
		$promo_html = ! empty( $value[ 'promo' ] ) ? $value[ 'promo' ] : '';
		$has_actions = ! empty( $docs_html ) || ! empty( $promo_html );
		$has_header = ! empty( $value[ 'title' ] ) || ! empty( $value[ 'desc' ] ) || $has_actions;
		$allowed_actions_html = array(
			'a' => array(
				'class'       => true,
				'href'        => true,
				'target'      => true,
				'rel'         => true,
				'aria-label'  => true,
				'title'       => true,
			),
			'span' => array(
				'class'       => true,
				'aria-hidden' => true,
			),
		);
		?>
		<div class="fc-settings-card" <?php echo ! empty( $card_id ) ? 'id="' . esc_attr( $card_id ) . '"' : ''; ?>>
			<?php if ( $has_header ) : ?>
				<div class="fc-settings-card__header">
					<?php if ( ! empty( $value[ 'title' ] ) ) : ?>
						<h3 class="fc-settings-card__title"><?php echo esc_html( $value[ 'title' ] ); ?></h3>
					<?php endif; ?>
					<?php if ( $has_actions ) : ?>
						<div class="fc-settings-card__actions"><?php
							if ( ! empty( $docs_html ) ) {
								echo wp_kses( $docs_html, $allowed_actions_html );
							}
							if ( ! empty( $promo_html ) ) {
								echo wp_kses( $promo_html, $allowed_actions_html );
							}
						?></div>
					<?php endif; ?>
					<?php if ( ! empty( $value[ 'desc' ] ) ) : ?>
						<div id="<?php echo esc_attr( sanitize_title( $value[ 'id' ] ) ); ?>-description" class="fc-settings-card__description"><?php echo wp_kses_post( wpautop( wptexturize( $value[ 'desc' ] ) ) ); ?></div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<div class="fc-settings-card__inner">
		<?php
		$this->is_card_open = true;

		// Maybe run the WooCommerce section start hook
		if ( ! empty( $value[ 'id' ] ) ) {
			do_action( 'woocommerce_settings_' . sanitize_title( $value[ 'id' ] ) );
		}
	}

	/**
	 * Output closing tags for a settings card.
	 *
	 * @param  array  $value  Settings `sectionend` arguments.
	 */
	public function output_card_end( $value ) {
		// Bail if no card is open
		if ( ! $this->is_card_open ) { return; }

		// Maybe run the WooCommerce section end hook
		if ( ! empty( $value[ 'id' ] ) ) {
			do_action( 'woocommerce_settings_' . sanitize_title( $value[ 'id' ] ) . '_end' );
		}

		$this->close_open_card();

		// Maybe run the WooCommerce section after hook
		if ( ! empty( $value[ 'id' ] ) ) {
			do_action( 'woocommerce_settings_' . sanitize_title( $value[ 'id' ] ) . '_after' );
		}
	}

	/**
	 * Close the currently open card, if any.
	 */
	public function close_open_card() {
		// Bail if no card is open
		if ( ! $this->is_card_open ) { return; }

		$this->close_open_field_row();
		?>
			</div>
		</div>
		<?php
		$this->is_card_open = false;
	}



	/**
	 * Output a single settings field.
	 *
	 * @param  array  $value  Settings field arguments.
	 */
	public function output_field( $value ) {
		$field_type = $value[ 'type' ];

		// Output field types registered for the Fluid Checkout settings page
		if ( has_action( 'fc_admin_settings_render_field_' . $field_type ) ) {
			/**
			 * Output a settings field of a specific type on the Fluid Checkout settings page.
			 *
			 * @param  array  $value  Settings field arguments.
			 */
			do_action( 'fc_admin_settings_render_field_' . $field_type, $value );
			return;
		}

		// Maybe output field types only registered for the WooCommerce settings pages
		if ( has_action( 'woocommerce_admin_field_' . $field_type ) ) {
			$this->output_field_legacy( $value );
			return;
		}

		// Maybe output locked fields of types provided by plugins that are not active
		if ( $this->is_field_disabled( $value ) ) {
			$this->output_field_locked_placeholder( $value );
		}
	}

	/**
	 * Output the title and description of a locked field, for field types not available on the current site.
	 *
	 * @param  array  $value  Settings field arguments.
	 */
	public function output_field_locked_placeholder( $value ) {
		$field_description = $this->get_field_description( $value );

		$this->output_field_start( $value, array( 'label_for' => false ) );
		echo $field_description[ 'description' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$this->output_field_end( $value );
	}

	/**
	 * Output a field type only registered for the WooCommerce settings pages, which outputs table rows.
	 *
	 * @param  array  $value  Settings field arguments.
	 */
	public function output_field_legacy( $value ) {
		?>
		<div class="fc-settings-field fc-settings-field--legacy">
			<table class="form-table">
				<tbody>
					<?php do_action( 'woocommerce_admin_field_' . $value[ 'type' ], $value ); ?>
				</tbody>
			</table>
		</div>
		<?php
	}



	/**
	 * Check whether the field starts a new field row.
	 * Fields in a group, with `checkboxgroup` set to empty or `end`, continue the row opened by the `start` field.
	 *
	 * @param  array  $value  Settings field arguments.
	 */
	public function is_field_group_start( $value ) {
		return ! isset( $value[ 'checkboxgroup' ] ) || 'start' === $value[ 'checkboxgroup' ];
	}

	/**
	 * Check whether the field currently being rendered opened a new field row.
	 * Only accurate after calling `output_field_start()` for the field.
	 */
	public function is_current_field_row_start() {
		return $this->is_current_field_row_start;
	}

	/**
	 * Check whether the field closes the current field row.
	 *
	 * @param  array  $value  Settings field arguments.
	 */
	public function is_field_group_end( $value ) {
		return ! isset( $value[ 'checkboxgroup' ] ) || 'end' === $value[ 'checkboxgroup' ];
	}

	/**
	 * Check whether the field is disabled.
	 *
	 * @param  array  $value  Settings field arguments.
	 */
	public function is_field_disabled( $value ) {
		return array_key_exists( 'disabled', $value ) && false !== $value[ 'disabled' ];
	}

	/**
	 * Get the field description and tooltip HTML.
	 *
	 * @param  array  $value  Settings field arguments.
	 */
	public function get_field_description( $value ) {
		// Maybe move description content into the custom info tip on the Cart settings tab
		if ( $this->uses_info_tooltips() ) {
			$value = $this->prepare_field_args_for_info_tooltips( $value );
		}

		return WC_Admin_Settings::get_field_description( $value );
	}

	/**
	 * Whether the current settings page uses custom info tip icons for field descriptions.
	 */
	public function uses_info_tooltips() {
		return class_exists( 'FluidCheckout_Admin_Settings_Page' ) && FluidCheckout_Admin_Settings_Page::instance()->is_settings_page();
	}

	/**
	 * Whether the current settings page renders checkboxes as toggle switches.
	 */
	public function uses_toggle_checkboxes() {
		return class_exists( 'FluidCheckout_Admin_Settings_Page' ) && FluidCheckout_Admin_Settings_Page::instance()->is_settings_page();
	}

	/**
	 * Clear description arguments that are shown via the custom info tip instead.
	 *
	 * @param  array  $value  Settings field arguments.
	 */
	public function prepare_field_args_for_info_tooltips( $value ) {
		$has_string_tip = ! empty( $value[ 'desc_tip' ] ) && true !== $value[ 'desc_tip' ];

		// Keep the checkbox label text next to the toggle; only suppress the WooCommerce tip
		if ( 'checkbox' === $value[ 'type' ] ) {
			$value[ 'desc_tip' ] = false;
			return $value;
		}

		// Move tip or field description into the info tip
		if ( $has_string_tip || true === $value[ 'desc_tip' ] || ! empty( $value[ 'desc' ] ) ) {
			$value[ 'desc' ] = '';
			$value[ 'desc_tip' ] = false;
		}

		return $value;
	}

	/**
	 * Get the content shown inside the custom info tip for a field.
	 *
	 * @param  array  $value  Settings field arguments.
	 */
	public function get_info_tooltip_content( $value ) {
		// Prefer an explicit tip string
		if ( ! empty( $value[ 'desc_tip' ] ) && true !== $value[ 'desc_tip' ] ) {
			return $value[ 'desc_tip' ];
		}

		// When desc_tip is true, WooCommerce uses the description as the tip
		if ( true === $value[ 'desc_tip' ] && ! empty( $value[ 'desc' ] ) ) {
			return $value[ 'desc' ];
		}

		// Move non-checkbox field descriptions into the tip
		if ( 'checkbox' !== $value[ 'type' ] && ! empty( $value[ 'desc' ] ) ) {
			return $value[ 'desc' ];
		}

		return '';
	}

	/**
	 * Get HTML for a custom info tip icon with floating tooltip content.
	 *
	 * @param  string  $content  Tooltip content HTML.
	 */
	public function get_info_tooltip_html( $content ) {
		// Bail if no content
		if ( '' === $content || null === $content ) { return ''; }

		$tooltip_id = 'fc-settings-tip-' . wp_unique_id();
		$label = __( 'More information', 'fluid-checkout' );

		return sprintf(
			'<span class="fc-settings-info-tip"><button type="button" class="fc-settings-info-tip__button" aria-expanded="false" aria-controls="%1$s" aria-label="%2$s"><span class="dashicons dashicons-info-outline" aria-hidden="true"></span></button><span id="%1$s" class="fc-settings-info-tip__content" role="tooltip">%3$s</span></span>',
			esc_attr( $tooltip_id ),
			esc_attr( $label ),
			wp_kses( $content, $this->get_info_tooltip_allowed_html() )
		);
	}

	/**
	 * Get allowed HTML tags for info tip content.
	 */
	public function get_info_tooltip_allowed_html() {
		return array(
			'a'      => array(
				'href'   => true,
				'target' => true,
				'rel'    => true,
				'class'  => true,
			),
			'br'     => array(),
			'code'   => array(),
			'em'     => array(),
			'i'      => array(),
			'li'     => array(),
			'ol'     => array(),
			'p'      => array(),
			'span'   => array(
				'class' => true,
			),
			'strong' => array(),
			'b'      => array(),
			'ul'     => array(),
		);
	}

	/**
	 * Get escaped custom attributes HTML for a field.
	 *
	 * @param  array  $value  Settings field arguments.
	 */
	public function get_custom_attributes_html( $value ) {
		$custom_attributes = array();

		// Bail if no custom attributes
		if ( empty( $value[ 'custom_attributes' ] ) || ! is_array( $value[ 'custom_attributes' ] ) ) { return ''; }

		foreach ( $value[ 'custom_attributes' ] as $attribute => $attribute_value ) {
			$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
		}

		return implode( ' ', $custom_attributes );
	}

	/**
	 * Get the visibility and state classes for a field container.
	 * Uses the same classes as the WooCommerce settings API for toggling dependent fields.
	 *
	 * @param  array  $value  Settings field arguments.
	 */
	public function get_field_container_classes( $value ) {
		$classes = array();
		$hide_if_checked = isset( $value[ 'hide_if_checked' ] ) ? $value[ 'hide_if_checked' ] : false;
		$show_if_checked = isset( $value[ 'show_if_checked' ] ) ? $value[ 'show_if_checked' ] : false;

		if ( 'yes' === $hide_if_checked || 'yes' === $show_if_checked ) {
			$classes[] = 'hidden_option';
		}
		if ( 'option' === $hide_if_checked ) {
			$classes[] = 'hide_options_if_checked';
		}
		if ( 'option' === $show_if_checked ) {
			$classes[] = 'show_options_if_checked';
		}
		if ( ! empty( $value[ 'row_class' ] ) ) {
			$classes[] = $value[ 'row_class' ];
		}
		if ( $this->is_field_disabled( $value ) ) {
			$classes[] = 'disabled';
			$classes[] = 'fc-settings-field--disabled';
		}

		return $classes;
	}



	/**
	 * Output opening tags for a field row, or for a field inside a field group.
	 *
	 * @param  array  $value  Settings field arguments.
	 * @param  array  $args   {
	 *     Optional. Field wrapper arguments.
	 *
	 *     @type  bool  $fieldset   Whether to wrap the control with a `fieldset`. Fields in a group are always wrapped. Defaults to `false`.
	 *     @type  bool  $label_for  Whether to output the title as a `label` for the field ID. Defaults to `true`.
	 *     @type  bool  $tooltip    Whether to output the tooltip next to the title. Defaults to `true`.
	 * }
	 */
	public function output_field_start( $value, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'fieldset'          => false,
			'label_for'         => true,
			'tooltip'           => true,
		) );
		$container_classes = $this->get_field_container_classes( $value );

		// Maybe continue a field group opened by a previous field
		$this->is_current_field_row_start = $this->is_field_group_start( $value ) || ! $this->is_field_row_open;
		if ( ! $this->is_current_field_row_start ) {
			echo '<fieldset class="' . esc_attr( implode( ' ', $container_classes ) ) . '">';
			return;
		}

		// Close field row left open by a field group without an `end` field
		$this->close_open_field_row();

		$field_description = $this->get_field_description( $value );
		$row_classes = array_merge( array( 'fc-settings-field', 'fc-settings-field--inline', 'fc-settings-field--' . sanitize_html_class( $value[ 'type' ] ) ), $container_classes );
		$show_wc_tooltip = $args[ 'tooltip' ] && ! $this->uses_info_tooltips();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $row_classes ) ); ?>">
			<div class="fc-settings-field__label">
				<?php if ( '' !== $value[ 'title' ] ) : ?>
					<?php if ( $args[ 'label_for' ] && ! empty( $value[ 'id' ] ) ) : ?>
						<label for="<?php echo esc_attr( $value[ 'id' ] ); ?>"><?php echo esc_html( $value[ 'title' ] ); ?></label>
					<?php else : ?>
						<span class="fc-settings-field__title"><?php echo esc_html( $value[ 'title' ] ); ?></span>
					<?php endif; ?>
				<?php endif; ?>
				<?php if ( $show_wc_tooltip ) { echo $field_description[ 'tooltip_html' ]; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<div class="fc-settings-field__control forminp forminp-<?php echo esc_attr( sanitize_title( $value[ 'type' ] ) ); ?>">
				<?php
				// Maybe output the custom info tip before the field control
				if ( $this->uses_info_tooltips() ) {
					echo $this->get_info_tooltip_html( $this->get_info_tooltip_content( $value ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
		<?php
		$this->is_field_row_open = true;

		if ( $args[ 'fieldset' ] ) {
			echo '<fieldset>';
		}
	}

	/**
	 * Output closing tags for a field row, or for a field inside a field group.
	 *
	 * @param  array  $value  Settings field arguments.
	 * @param  array  $args   Optional. Same arguments passed to `output_field_start()`.
	 */
	public function output_field_end( $value, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'fieldset'          => false,
		) );

		// Maybe close the `fieldset` element
		if ( $args[ 'fieldset' ] || ! $this->is_current_field_row_start ) {
			echo '</fieldset>';
		}

		// Bail if field row should stay open for the next fields in the group
		if ( ! $this->is_field_group_end( $value ) ) { return; }

		$this->close_open_field_row();
	}

	/**
	 * Close the currently open field row, if any.
	 */
	public function close_open_field_row() {
		// Bail if no field row is open
		if ( ! $this->is_field_row_open ) { return; }
		?>
			</div>
		</div>
		<?php
		$this->is_field_row_open = false;
	}



	/**
	 * Output an input field.
	 *
	 * @param  array  $value  Settings field arguments.
	 */
	public function output_field_input( $value ) {
		$field_description = $this->get_field_description( $value );

		$this->output_field_start( $value );
		?>
		<input
			name="<?php echo esc_attr( $value[ 'field_name' ] ); ?>"
			id="<?php echo esc_attr( $value[ 'id' ] ); ?>"
			type="<?php echo esc_attr( $value[ 'type' ] ); ?>"
			style="<?php echo esc_attr( $value[ 'css' ] ); ?>"
			value="<?php echo esc_attr( $value[ 'value' ] ); ?>"
			class="<?php echo esc_attr( $value[ 'class' ] ); ?>"
			placeholder="<?php echo esc_attr( $value[ 'placeholder' ] ); ?>"
			<?php echo $this->get_custom_attributes_html( $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php disabled( $this->is_field_disabled( $value ) ); ?>
			/><?php echo esc_html( $value[ 'suffix' ] ); ?> <?php echo $field_description[ 'description' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php
		$this->output_field_end( $value );
	}

	/**
	 * Output a color field.
	 *
	 * @param  array  $value  Settings field arguments.
	 */
	public function output_field_color( $value ) {
		$field_description = $this->get_field_description( $value );

		$this->output_field_start( $value );
		?>
		<span class="colorpickpreview" style="background: <?php echo esc_attr( $value[ 'value' ] ); ?>">&nbsp;</span>
		<input
			name="<?php echo esc_attr( $value[ 'field_name' ] ); ?>"
			id="<?php echo esc_attr( $value[ 'id' ] ); ?>"
			type="text"
			dir="ltr"
			style="<?php echo esc_attr( $value[ 'css' ] ); ?>"
			value="<?php echo esc_attr( $value[ 'value' ] ); ?>"
			class="<?php echo esc_attr( $value[ 'class' ] ); ?> colorpick"
			placeholder="<?php echo esc_attr( $value[ 'placeholder' ] ); ?>"
			autocomplete="off"
			<?php echo $this->get_custom_attributes_html( $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php disabled( $this->is_field_disabled( $value ) ); ?>
			/>&lrm; <?php echo $field_description[ 'description' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php
		$this->output_field_end( $value );
	}

	/**
	 * Output a textarea field.
	 *
	 * @param  array  $value  Settings field arguments.
	 */
	public function output_field_textarea( $value ) {
		$field_description = $this->get_field_description( $value );

		$this->output_field_start( $value );
		echo $field_description[ 'description' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
		<textarea
			name="<?php echo esc_attr( $value[ 'field_name' ] ); ?>"
			id="<?php echo esc_attr( $value[ 'id' ] ); ?>"
			style="<?php echo esc_attr( $value[ 'css' ] ); ?>"
			class="<?php echo esc_attr( $value[ 'class' ] ); ?>"
			placeholder="<?php echo esc_attr( $value[ 'placeholder' ] ); ?>"
			<?php echo $this->get_custom_attributes_html( $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php disabled( $this->is_field_disabled( $value ) ); ?>
			><?php echo esc_textarea( $value[ 'value' ] ); ?></textarea>
		<?php
		$this->output_field_end( $value );
	}

	/**
	 * Output a select or multiselect field.
	 *
	 * @param  array  $value  Settings field arguments.
	 */
	public function output_field_select( $value ) {
		$field_description = $this->get_field_description( $value );
		$is_multiselect = in_array( $value[ 'type' ], array( 'multiselect', 'multi_select_countries' ), true );
		$options = isset( $value[ 'options' ] ) && is_array( $value[ 'options' ] ) ? $value[ 'options' ] : array();

		// Use all countries as options for country fields without options, same as the WooCommerce settings API
		if ( 'multi_select_countries' === $value[ 'type' ] && empty( $options ) && function_exists( 'WC' ) ) {
			$options = WC()->countries->countries;
		}

		// Use enhanced select for country fields, same as the WooCommerce settings API
		if ( 'multi_select_countries' === $value[ 'type' ] && empty( $value[ 'class' ] ) ) {
			$value[ 'class' ] = 'wc-enhanced-select';
		}

		$this->output_field_start( $value );
		?>
		<select
			name="<?php echo esc_attr( $value[ 'field_name' ] ); ?><?php echo $is_multiselect ? '[]' : ''; ?>"
			id="<?php echo esc_attr( $value[ 'id' ] ); ?>"
			style="<?php echo esc_attr( $value[ 'css' ] ); ?>"
			class="<?php echo esc_attr( $value[ 'class' ] ); ?>"
			<?php echo $this->get_custom_attributes_html( $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo $is_multiselect ? 'multiple="multiple"' : ''; ?>
			<?php disabled( $this->is_field_disabled( $value ) ); ?>
			>
			<?php foreach ( $options as $key => $val ) : ?>
				<?php if ( is_array( $val ) ) : ?>
					<optgroup label="<?php echo esc_attr( $key ); ?>">
						<?php foreach ( $val as $option_key => $option_value ) : ?>
							<option value="<?php echo esc_attr( $option_key ); ?>" <?php $this->output_option_selected( $value[ 'value' ], $option_key ); ?>><?php echo esc_html( $option_value ); ?></option>
						<?php endforeach; ?>
					</optgroup>
				<?php else : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php $this->output_option_selected( $value[ 'value' ], $key ); ?>><?php echo esc_html( $val ); ?></option>
				<?php endif; ?>
			<?php endforeach; ?>
		</select> <?php echo $field_description[ 'description' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php
		$this->output_field_end( $value );
	}

	/**
	 * Output the `selected` attribute for a select option.
	 *
	 * @param  mixed   $option_value  Saved field value, either a string or an array for multiselect fields.
	 * @param  string  $key           Option key.
	 */
	public function output_option_selected( $option_value, $key ) {
		if ( is_array( $option_value ) ) {
			selected( in_array( (string) $key, $option_value, true ), true );
		}
		else {
			selected( $option_value, (string) $key );
		}
	}

	/**
	 * Output a radio field.
	 *
	 * @param  array  $value  Settings field arguments.
	 */
	public function output_field_radio( $value ) {
		$field_description = $this->get_field_description( $value );
		$options = isset( $value[ 'options' ] ) && is_array( $value[ 'options' ] ) ? $value[ 'options' ] : array();

		$this->output_field_start( $value, array( 'fieldset' => true, 'label_for' => false ) );
		echo $field_description[ 'description' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
		<ul>
			<?php foreach ( $options as $key => $val ) : ?>
				<li>
					<label><input
						name="<?php echo esc_attr( $value[ 'field_name' ] ); ?>"
						value="<?php echo esc_attr( $key ); ?>"
						type="radio"
						style="<?php echo esc_attr( $value[ 'css' ] ); ?>"
						class="<?php echo esc_attr( $value[ 'class' ] ); ?>"
						<?php echo $this->get_custom_attributes_html( $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php checked( (string) $key, (string) $value[ 'value' ] ); ?>
						<?php disabled( $this->is_field_disabled( $value ) ); ?>
						/> <?php echo esc_html( $val ); ?></label>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
		$this->output_field_end( $value, array( 'fieldset' => true ) );
	}

	/**
	 * Output a checkbox field.
	 *
	 * @param  array  $value  Settings field arguments.
	 */
	public function output_field_checkbox( $value ) {
		$field_description = $this->get_field_description( $value );
		$has_title = '' !== $value[ 'title' ];
		$has_legend = isset( $value[ 'legend' ] ) && '' !== $value[ 'legend' ];
		$is_disabled = $this->is_field_disabled( $value );
		$use_toggle = $this->uses_toggle_checkboxes();

		$this->output_field_start( $value, array( 'fieldset' => true, 'label_for' => false, 'tooltip' => false ) );
		?>
		<?php if ( $has_title || $has_legend ) : ?>
			<legend class="<?php echo $has_legend ? '' : 'screen-reader-text'; ?>"><span><?php echo esc_html( $has_legend ? $value[ 'legend' ] : $value[ 'title' ] ); ?></span></legend>
		<?php endif; ?>
		<?php if ( $use_toggle ) : ?>
			<span class="fc-settings-switch<?php echo $is_disabled ? ' fc-settings-switch--disabled' : ''; ?>">
				<input
					name="<?php echo esc_attr( $value[ 'field_name' ] ); ?>"
					id="<?php echo esc_attr( $value[ 'id' ] ); ?>"
					type="checkbox"
					class="fc-settings-toggle fc-settings-toggle--round <?php echo esc_attr( $value[ 'class' ] ); ?>"
					value="1"
					<?php checked( $value[ 'value' ], 'yes' ); ?>
					<?php echo $this->get_custom_attributes_html( $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php disabled( $is_disabled ); ?>
				/>
				<label for="<?php echo esc_attr( $value[ 'id' ] ); ?>"></label>
			</span>
			<?php if ( ! empty( $field_description[ 'description' ] ) ) : ?>
				<label class="fc-settings-switch__text" for="<?php echo esc_attr( $value[ 'id' ] ); ?>"><?php echo $field_description[ 'description' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
			<?php endif; ?>
		<?php else : ?>
			<label for="<?php echo esc_attr( $value[ 'id' ] ); ?>">
				<input
					name="<?php echo esc_attr( $value[ 'field_name' ] ); ?>"
					id="<?php echo esc_attr( $value[ 'id' ] ); ?>"
					type="checkbox"
					class="<?php echo esc_attr( $value[ 'class' ] ); ?>"
					value="1"
					<?php checked( $value[ 'value' ], 'yes' ); ?>
					<?php echo $this->get_custom_attributes_html( $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php disabled( $is_disabled ); ?>
				/> <?php echo $field_description[ 'description' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</label> <?php echo $field_description[ 'tooltip_html' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>
		<?php
		$this->output_field_end( $value, array( 'fieldset' => true ) );
	}

}

FluidCheckout_Admin_Settings_Renderer::instance();
