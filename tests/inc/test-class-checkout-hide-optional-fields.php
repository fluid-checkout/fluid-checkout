<?php
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit test: optional checkout fields hidden by WooCommerce country locale.
 */
class FluidCheckout_CheckoutHideOptionalFields_Test extends TestCase {
	use TransactionalTestClassTrait;

	/**
	 * Previous customer countries, restored after each test.
	 *
	 * @var string
	 */
	protected $previous_billing_country = '';

	/**
	 * @var string
	 */
	protected $previous_shipping_country = '';

	/**
	 * Test setup.
	 */
	public function setUpInstance() {
		add_filter( 'woocommerce_is_checkout', '__return_true' );

		if ( ! WC()->customer instanceof WC_Customer ) {
			WC()->customer = new WC_Customer();
		}

		$this->previous_billing_country = WC()->customer->get_billing_country();
		$this->previous_shipping_country = WC()->customer->get_shipping_country();
	}

	/**
	 * Test teardown.
	 */
	public function tearDownInstance() {
		remove_filter( 'woocommerce_is_checkout', '__return_true' );

		if ( WC()->customer instanceof WC_Customer ) {
			WC()->customer->set_billing_country( $this->previous_billing_country );
			WC()->customer->set_shipping_country( $this->previous_shipping_country );
		}
	}

	/**
	 * Ensure field args have the keys the optional-field markup expects.
	 *
	 * @param   array  $args  Field arguments.
	 */
	protected function normalize_field_args( $args ) {
		if ( ! isset( $args['type'] ) ) {
			$args['type'] = 'text';
		}
		if ( empty( $args['class'] ) || ! is_array( $args['class'] ) ) {
			$args['class'] = array( 'form-row-wide' );
		}
		if ( ! isset( $args['label'] ) ) {
			$args['label'] = 'Field';
		}

		return $args;
	}

	/**
	 * Build field markup shaped like `woocommerce_form_field()`.
	 *
	 * @param   string  $key   Field key.
	 * @param   array   $args  Field arguments.
	 */
	protected function field_html( $key, $args ) {
		$classes = esc_attr( implode( ' ', $args['class'] ) );

		return '<p class="form-row ' . $classes . '" id="' . esc_attr( $key ) . '_field"><label>' . esc_html( $args['label'] ) . '</label><span class="woocommerce-input-wrapper"><input type="text" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="" /></span></p>';
	}

	/**
	 * Run the optional-field markup filter.
	 *
	 * @param   string  $key    Field key.
	 * @param   array   $args   Field arguments.
	 * @param   mixed   $value  Field value.
	 */
	protected function render_field( $key, $args, $value = '' ) {
		$args = $this->normalize_field_args( $args );

		return FluidCheckout_CheckoutHideOptionalFields::instance()->add_optional_form_field_link_button(
			$this->field_html( $key, $args ),
			$key,
			$args,
			$value
		);
	}

	/**
	 * Set the customer country used when field args do not already include `hidden`.
	 *
	 * @param   string  $country       Country code.
	 * @param   string  $address_type  `billing` or `shipping`.
	 */
	protected function set_customer_country( $country, $address_type = 'billing' ) {
		if ( 'shipping' === $address_type ) {
			WC()->customer->set_shipping_country( $country );
			return;
		}

		WC()->customer->set_billing_country( $country );
	}

	/**
	 * Test: locale hidden flags.
	 */
	public function test_locale_hidden_value() {
		$fields = FluidCheckout_CheckoutHideOptionalFields::instance();

		$this->assertTrue( $fields->is_locale_hidden_value( true ) );
		$this->assertTrue( $fields->is_locale_hidden_value( 1 ) );
		$this->assertTrue( $fields->is_locale_hidden_value( '1' ) );
		$this->assertTrue( $fields->is_locale_hidden_value( 'yes' ) );
		$this->assertTrue( $fields->is_locale_hidden_value( 'true' ) );
		$this->assertFalse( $fields->is_locale_hidden_value( false ) );
		$this->assertFalse( $fields->is_locale_hidden_value( 0 ) );
		$this->assertFalse( $fields->is_locale_hidden_value( '0' ) );
		$this->assertFalse( $fields->is_locale_hidden_value( '' ) );
		$this->assertFalse( $fields->is_locale_hidden_value( null ) );
	}

	/**
	 * Test: UAE postcode is hidden for billing and shipping, including when older WooCommerce omits `hidden` from field args.
	 */
	public function test_hidden_postcode_for_united_arab_emirates() {
		$fields = FluidCheckout_CheckoutHideOptionalFields::instance();

		foreach ( array( 'billing', 'shipping' ) as $address_type ) {
			$this->set_customer_country( 'AE', $address_type );

			$field_key = $address_type . '_postcode';
			$address_fields = WC()->countries->get_address_fields( 'AE', $address_type . '_' );
			$args = $this->normalize_field_args( $address_fields[ $field_key ] );

			$this->assertTrue( $fields->is_address_locale_field_hidden( $field_key, $args ) );

			$html = $this->render_field( $field_key, $args, '' );
			$this->assertStringContainsString( 'fc-expansible-form-section__toggle--' . $field_key, $html );
			$this->assertMatchesRegularExpression( '/class="[^"]*fc-expansible-form-section[^"]*fc-address-locale-hidden[^"]*"/', $html );

			unset( $args['hidden'] );
			$this->assertTrue( $fields->is_address_locale_field_hidden( $field_key, $args ) );
			$html = $this->render_field( $field_key, $args, '' );
			$this->assertMatchesRegularExpression( '/class="[^"]*fc-expansible-form-section[^"]*fc-address-locale-hidden[^"]*"/', $html );
		}
	}

	/**
	 * Test: required postcode fields in the US and GB are not collapsed behind an "Add ..." link.
	 */
	public function test_required_postcode_for_us_and_gb_has_no_add_link() {
		foreach ( array( 'US', 'GB' ) as $country ) {
			$this->set_customer_country( $country );

			$address_fields = WC()->countries->get_address_fields( $country, 'billing_' );
			$args = $this->normalize_field_args( $address_fields['billing_postcode'] );
			$html = $this->render_field( 'billing_postcode', $args, '' );

			$this->assertTrue( ! empty( $args['required'] ), $country . ' postcode should be required.' );
			$this->assertStringNotContainsString( 'fc-expansible-form-section', $html );
			$this->assertStringNotContainsString( 'fc-address-locale-hidden', $html );
		}
	}

	/**
	 * Test: an optional visible field keeps the "Add ..." link.
	 *
	 * Ireland's Eircode is optional on WooCommerce 9 and required from WooCommerce 10, so the
	 * assertion follows the locale WooCommerce actually provides. Bangladesh postcode stays optional.
	 */
	public function test_optional_visible_fields_keep_add_link() {
		$fields = FluidCheckout_CheckoutHideOptionalFields::instance();

		$company_args = array(
			'type'     => 'text',
			'label'    => 'Company',
			'required' => false,
			'hidden'   => false,
			'class'    => array( 'form-row-wide' ),
		);
		$company_html = $this->render_field( 'billing_company', $company_args, '' );
		$this->assertStringContainsString( 'fc-expansible-form-section__toggle--billing_company', $company_html );
		$this->assertStringNotContainsString( 'fc-address-locale-hidden', $company_html );

		$filled_company_html = $this->render_field( 'billing_company', $company_args, 'Fluid Checkout' );
		$this->assertStringNotContainsString( 'fc-expansible-form-section', $filled_company_html );

		$this->set_customer_country( 'BD' );
		$bangladesh = $this->normalize_field_args( WC()->countries->get_address_fields( 'BD', 'billing_' )['billing_postcode'] );
		$this->assertFalse( $fields->is_address_locale_field_hidden( 'billing_postcode', $bangladesh ) );
		$this->assertTrue( empty( $bangladesh['required'] ) );
		$bangladesh_html = $this->render_field( 'billing_postcode', $bangladesh, '' );
		$this->assertStringContainsString( 'fc-expansible-form-section__toggle--billing_postcode', $bangladesh_html );
		$this->assertStringNotContainsString( 'fc-address-locale-hidden', $bangladesh_html );

		$this->set_customer_country( 'IE' );
		$ireland = $this->normalize_field_args( WC()->countries->get_address_fields( 'IE', 'billing_' )['billing_postcode'] );
		$ireland_html = $this->render_field( 'billing_postcode', $ireland, '' );
		$this->assertFalse( $fields->is_address_locale_field_hidden( 'billing_postcode', $ireland ) );

		if ( ! empty( $ireland['required'] ) ) {
			$this->assertStringNotContainsString( 'fc-expansible-form-section', $ireland_html );
		} else {
			$this->assertStringContainsString( 'fc-expansible-form-section__toggle--billing_postcode', $ireland_html );
			$this->assertStringNotContainsString( 'fc-address-locale-hidden', $ireland_html );
		}
	}

	/**
	 * Test: any locale-hidden address field is covered, not only postcode. State stays out of the "Add ..." link.
	 */
	public function test_locale_hidden_city_and_state() {
		$city_html = $this->render_field(
			'billing_city',
			array(
				'type'     => 'text',
				'label'    => 'Town / City',
				'required' => false,
				'hidden'   => true,
				'class'    => array( 'form-row-wide' ),
			),
			''
		);
		$this->assertStringContainsString( 'fc-expansible-form-section__toggle--billing_city', $city_html );
		$this->assertMatchesRegularExpression( '/class="[^"]*fc-expansible-form-section[^"]*fc-address-locale-hidden[^"]*"/', $city_html );

		$state_html = $this->render_field(
			'billing_state',
			array(
				'type'     => 'state',
				'label'    => 'State',
				'required' => false,
				'hidden'   => true,
				'class'    => array( 'form-row-wide' ),
			),
			''
		);
		$this->assertStringNotContainsString( 'fc-expansible-form-section', $state_html );
		$this->assertStringContainsString( 'fc-address-locale-hidden', $state_html );
	}

	/**
	 * Test: a saved value on a locale-hidden field does not show an "Add ..." link.
	 * The section stays in the markup so a later country change can show the link again.
	 */
	public function test_locale_hidden_field_with_value_keeps_hidden_section() {
		$html = $this->render_field(
			'billing_postcode',
			array(
				'type'     => 'text',
				'label'    => 'Postcode',
				'required' => false,
				'hidden'   => true,
				'class'    => array( 'form-row-wide' ),
			),
			'12345'
		);

		$this->assertStringContainsString( 'fc-expansible-form-section__toggle--billing_postcode', $html );
		$this->assertMatchesRegularExpression( '/class="[^"]*fc-expansible-form-section[^"]*fc-address-locale-hidden[^"]*"/', $html );
	}
}
