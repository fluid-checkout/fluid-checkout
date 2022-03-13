<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: MailPoet integration.
 */
class FluidCheckout_MailPoet extends FluidCheckout {

    /**
     * __construct function.
     */
    public function __construct( ) {
        add_action( 'init', [ $this, 'hooks' ] );
    }

    /**
     * Initialize hooks.
     */
    public function hooks() {
        // Bail when no mail poet class
        if ( ! class_exists( MailPoet\Config\HooksWooCommerce::class ) ) {
            return;
        }

        $this->remove_class_action( 'woocommerce_checkout_before_terms_and_conditions', 'MailPoet\Config\HooksWooCommerce',  'extendWooCommerceCheckoutForm' );
        add_action( 'fc_checkout_contact_after_fields', [ $this, 'extend_woocommerce_checkout_form' ], 10 );
    }

    /**
     * Extend WooCommerce checkout form for checkbox
     *
     * @return html
     */
    public function extend_woocommerce_checkout_form() {
        $setting   = $this->get_settings();

        if ( empty( $setting ) ) {
            return;
        }

        if ( empty( $setting['optin_on_checkout']['enabled'] ) ) {
            return;
        }

        $inputName = 'mailpoet_woocommerce_checkout_optin';
        $checked   = false;
        
        if ( ! empty( $_POST['mailpoet_woocommerce_checkout_optin'] ) ) {
          $checked = true;
        }

        $labelString = $setting['optin_on_checkout']['message'];
        $template = apply_filters(
          'mailpoet_woocommerce_checkout_optin_template',
          $this->get_subscription_field( $inputName, $checked, $labelString ),
          $inputName,
          $checked,
          $labelString
        );
        
        echo $template;

        if ( $template ) {
            echo $this->get_subscription_presence_check_field();
        }
    }

    /**
     * Extend WooCommerce checkout form for checkbox
     *
     * @return void
     */
    private function get_settings() {
        global $wpdb;

        $get_setting = $wpdb->get_row("select * from `{$wpdb->prefix}mailpoet_settings` where `name` = 'woocommerce'");

        return isset( $get_setting->value ) ? maybe_unserialize( $get_setting->value ) : '';
    }

    /**
     * Extend WooCommerce checkout form for checkbox
     *
     * @return void
     */
    private function get_subscription_presence_check_field() {
        $field = woocommerce_form_field(
          'mailpoet_woocommerce_checkout_optin_present',
          [
            'type' => 'hidden',
            'return' => true,
          ],
          1
        );

        if ( $field ) {
          return $field;
        }

        $field = woocommerce_form_field(
          'mailpoet_woocommerce_checkout_optin_present',
          [
            'type' => 'text',
            'return' => true,
          ],
          1
        );
        return str_replace( 'type="text', 'type="hidden"', $field );
    }
    
    /**
     * Get subscription field
     * 
     * @param  string   $inputName    Field name.
     * @param  boolean  $checked      Make sure by default value checked or not.
     * @param  string   $labelString  Field label.
     *
     * @return void
     */
    private function get_subscription_field( $inputName, $checked, $labelString ) {
        return woocommerce_form_field(
            esc_attr( $inputName ),
            [
                'type'              => 'checkbox',
                'label'             => esc_html( $labelString ),
                'input_class'       => ['woocommerce-form__input', 'woocommerce-form__input-checkbox', 'input-checkbox'],
                'label_class'       => ['woocommerce-form__label', 'woocommerce-form__label-for-checkbox', 'checkbox'],
                'custom_attributes' => ['data-automation-id' => 'woo-commerce-subscription-opt-in'],
                'return'            => true,
            ],
          $checked ? '1' : '0'
        );
    }

}

FluidCheckout_MailPoet::instance(); 