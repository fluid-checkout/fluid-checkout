# Changelog

All notable changes to this project will be documented in this file and the plugin's readme.txt file.

To avoid duplicate work, changes are first added to the [plugin's readme.txt file](https://github.com/fluid-checkout/fluid-checkout/blob/main/readme.txt), then after a few iterations, they are moved to this file.

The format is based on the [WordPress plugin readme file standard](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Given a version number MAJOR.MINOR.PATCH, increments are made to:

- MAJOR version when incompatible API changes are introduced,
- MINOR version when new functionality is added in a backwards compatible manner, and
- PATCH version when backwards compatible bug and security fixes are made.

Additional labels for beta builds are available as extensions to the MAJOR.MINOR.PATCH format (ie. 1.5.0-beta-1).


# CHANGES

[See latest changes in the plugin's readme.txt](https://github.com/fluid-checkout/fluid-checkout/blob/main/readme.txt)

= 3.2.4 - 2024-09-15 =

* Bump tested up to WooCommerce 9.3.1
* Fixed: Error on validation script causing Google Address Autocomplete to stop working.

= 3.2.3 - 2024-09-12 =

* Bump tested up to WordPress 6.6.2 and WooCommerce 9.2.3
* Added: New option to ignore additional required fields for Express Checkout payments, for the PRO feature.
* Added: Compatibility with theme Porto.
* Added: Compatibility with plugin Packlink PRO Shipping.
* Added: Compatibility with plugin VerifyPass.
* Added: Compatibility with plugin Loyalty Program for WooCommerce (for Advanced Coupons) – by Rymera Web Co.
* Improved: Add initial delay to show instant validation error message while typing in the field for the first time. Most notably on the email fields.
* Improved: Always show email typo suggestions after the error messages to avoid it jumping back and forth.
* Improved: Accessibility for form field instant validation error messages. Announce error messages as part of the field description.
* Fixed: Javascript error when trying to copy field values into the other address section in some cases.
* Fixed: Billing fields getting emptied or copying values from shipping when using the Local Pickup feature from Fluid Checkout PRO.
* Fixed: Fix styles for product images on the order summary at first load when a `picture` or `div.thumbnail` element is used.
* Fixed: Shipping address section closing unexpectedly in some cases.
* Fixed: PHP warning message when trying to run database migration processes in some cases.
* Reverted: Compatibility with plugin Smartlink Product Designer. Fix product image sizes on cart and checkout page. Fixed by changing the default styles for the product images with alternative HTML.

= 3.2.2 - 2024-08-22 =

* Bump tested up to WordPress 6.6.1 and WooCommerce 9.2.1
* Added: Translation to Czeck (Czeck Republic).
* Added: Compatibility with plugin GLS Shipping for WooCommerce.
* Added: Compatibility with plugin WP Armour Extended - Honeypot Anti Spam.
* Added: Compatibility with plugin Biteship for WooCommerce.
* Added: Option to choose number of cross sell items to show on the cart page, available with Fluid Checkout PRO.
* Improved: Compatibility with plugin WooCommerce Stripe Gateway. Set style for the Stripe Checkout fields for the New Stripe Checkout experience.
* Improved: Compatibility with plugin Mailchimp for WooCommerce. Display newsletter checkbox right below the email field.
* Improved: Sort contact step fields by priority, and show billing and shipping phone fields on the same row when both are required and set to be display on the contact step.
* Improved: How compatibility with WooCommerce features is declared.
* Improved: Move the checkout script settings into its own property in the `fcSettings` object, optimizing the use of memory on the browser.
* Improved: Improves the way compatibility classes are loaded to use less memory space.
* Fixed: Compatibility with the Order Attribution feature from WooCommerce versions 9.2.0+.
* Fixed: Compatibility with plugin WooCommerce Payments. Fix wrongly displaying error message "missing payment method" in some specific configurations.
* Fixed: Compatibility with plugin Rvvup for WooCommerce. Hide place order button when PayPal payment method is selected.
* Fixed: Compatibility with plugin Smartlink Product Designer. Fix product image sizes on cart and checkout page.
* Fixed: Do not set focus to password field when first visiting the checkout page if account creation is required.
* Fixed: Copy address fields values to the other address group on the frontend when "same as address" checkbox is checked for better compatibility with some plugins.
* Fixed: Do not apply database migrations when activating the plugin on a multisite installation.
* Fixed: Untranslatable strings originated from WooCommerce.
* Fixed: Layout for the coupon code apply button for some themes.

= 3.2.1 - 2024-08-05 =

* Fixed: Compatibility with plugin Germanized. Fix voucher coupons displayed with amount zero on the coupon code substep.
* Fixed: Fatal error on compatibility with the order attribution feature from WooCommerce on older versions of WooCommerce.

= 3.2.0 - 2024-08-02 =

* Bump tested up to WordPress 6.6.1 and WooCommerce 9.1.4
* Added: Admin notice about changes to next major version and customization migration guide.
* Added: Compatibility with the Order Attribution feature from WooCommerce.
* Added: Compatibility with plugin Avada Builder.
* Added: Compatibility with plugin Mondial Relay - WordPress.
* Added: Compatibility with plugin WooCommerce Carrier Agents.
* Improved: Compatibility with theme Avada.
* Improved: Compatibility with theme Ocean WP.
* Improved: Compatibility with theme Hello Elementor.
* Improved: Compatibility with plugin Email Template Customizer for WooCommerce (by VillaTheme). Do not show duplicate phone numbers when generating email messages with this plugin.
* Improved: Moved template files used to display customer addresses on email notifications from Fluid Checkout PRO to Lite.
* Improved: Add accessibility label to hidden checkbox "Ship to different address" to overcome issue reported by automated accessibility validation tools.
* Fixed: Compatibility issues with plugin WooPayments (WooCommerce Payments). Avoid setting address data to same as billing or shipping when processing express payments such as Apple Pay and Google Pay.
* Fixed: Prevent showing duplicate billing and shipping phone number values on email notifications.
* Fixed: Make string translatable in compatibility class with Hungarian Pickup Points plugin.
* Fixed: Layout for login form fields in the popup login form for some themes.
* Fixed: Same addresses being used for express payments when processing order from other pages other than the checkout page.
* Fixed: Redirection to cart page when trying to access the checkout page with an empty cart, and checkout page is using block-based checkout form.

= 3.1.11 - 2024-07-03 =

* Bump tested up to WooCommerce 9.0.2
* Added: Compatibility with theme Konte.
* Added: Compatibility with plugin Qode Framework.
* Added: Compatibility with plugin SuperFaktúra WooCommerce.
* Fixed: Max width for the container element on the checkout page when using the plugin's container classes.
* Fixed: Also trigger checkout update for text fields with class `update_totals_on_change`.
* Fixed: Shipping methods list not expanding correctly and overlapping with other elements.
* Fixed: Fatal errors with some 3rd-party plugins when trying to retrieve customer address data from the checkout session too early.
* Fixed: Do not use cache for customer address data values as this might affect how other plugins work with the data.

= 3.1.10 - 2024-06-20 =

* Bump tested up to WooCommerce 9.0.1
* Added: Compatibility with theme Kenta.
* Added: Compatibility with theme Ettore.
* Added: Compatibility with theme Fennik.
* Added: Compatibility with plugin Ettore Core.
* Fixed: Compatibility with plugin Fluent CRM PRO.
* Fixed: Compatibility with plugin Klaviyo. Always show the SMS compliance notice below the checkbox field, and move the checkbox automatically to the contact step if the billing phone field is also displayed in the contact step.
* Fixed: Retrieve customer address data from the checkout session data when available.
* Fixed: Layout issues for some elements with class `woocommerce` that are displayed outside the scope of Fluid Checkout.
* Fixed: Remove duplicate values shown in the substep review text for fields only present in the current address section when that address is set as "Same as <shipping/billing> address".

= 3.1.9 - 2024-06-11 =

* Bump tested up to WordPress 6.5.4 and WooCommerce 8.9.3
* Added: New option to prevent automatic selection of the first shipping method available, forcing customer to manually select the shipping method for each new order.
* Added: Compatibility with theme Beaver Builder Theme.
* Added: Compatibility with theme SiteOrigin Corp.
* Added: Compatibility with plugin Omniva Shipping.
* Added: Compatibility with plugin Advanced Coupons for WooCommerce.
* Improved: Compatibility with theme Zota.
* Improved: Compatibility with theme OnAir2.
* Improved: Compatibility with theme Blocksy. Support for the theme's color modes dark/light.
* Improved: Compatibility with plugin Elementor PRO. Automatically disable checkout page template when using the custom order received page created with Elementor PRO.
* Improved: Layout of the shipping methods and position of shipping methods description.
* Improved: Add hooks for displaying the shipping method logo images for compatible shipping plugins.
* Fixed: Compatibility with plugin Fluent CRM PRO. Fix fatal error when trying to add the subscribe box on the checkout page when using the plugin Fluent CRM PRO version 2.9.0+.
* Fixed: Handle checkout fields with indexed multiple values to an `array` when parsing posted data.
* Fixed: Checkout page template for distraction free header and footer should not be applied to the order pay and order received pages.
* Fixed: Redirect to the cart page when visiting the checkout with an empty cart when using the WooCommerce block-based checkout form.
* Fixed: Order received page showing blank in some cases when using the WooCommerce block-based checkout form.
* Fixed: Page layout issues when using full site editor (FSE).
* Fixed: Not adding the checkbox field label wrapper element for some themes.
* Fixed: Only apply changes of the shipping address from the cart shipping calculator when using that function, and not when other plugins trigger the action hook used for that.
* Fixed: Layout of product quantity label on the order summary at checkout for some themes.
* Fixed: Inner elements overflowing the order summary borders.
* Fixed: Display in the substep review text the fields only present in the current address section when that address is set as "Same as <shipping/billing> address".

= 3.1.8 - 2024-04-25 =

* Bump tested up to WordPress 6.5.2 and WooCommerce 8.8.2
* Added: Compatibility with theme Hub.
* Added: Compatibility with theme Salient.
* Added: Compatibility with theme Savoy.
* Added: Compatibility with theme The Gem.
* Added: Compatibility with plugin Flexible Checkout Fields PRO.
* Added: Compatibility with plugin MyParcel.
* Improved: Change wording for the company name field option in the plugin settings to be clear what it is related to.
* Improved: Automatically apply database migrations on first plugin installation, showing the message for database migration available only when updating the plugin.
* Fixed: Compatibility with plugin MailChimp for WooCommerce. Fix fatal error on checkout page when connection to Mailchimp is not completely set up.
* Fixed: Translations not being loaded correctly for language variations on WordPress 6.5+.

= 3.1.7 - 2024-03-27 =

* Bump tested up to WooCommerce 8.7.0
* Added: Compatibility with plugin Acowebs Woocommerce Dynamic Pricing by Acowebs.
* Added: Compatibility with plugin Acowebs Woocommerce Dynamic Pricing PRO by Acowebs.
* Added: Compatibility with plugin MkRapel Regiones y Ciudades de Chile para WC.
* Added: Compatibility with plugin WebToffee PayPal Express Checkout Payment Gateway for WooCommerce.
* Added: Partial compatibility with plugin PayPlus Payment Gateway.
* Improved: Compatibility with various themes.
* Improved: Add delay before triggering update of the checkout fragments when the browser tab visibility changes.
* Improved: Partial compatibility with plugin States, Cities, and Places for WooCommerce. Fix update to city fields, and add support for TomSelect dropdown components.
* Fixed: Missing styles for RTL languages, instead use the main file when the RTL file does not exist.
* Fixed: Set default limit for number of options on TomSelect dropdown fields to `999999`, previous was set to default of `50` options.
* Fixed: Order Pay and Order Received pages are displayed empty when the theme does not support the block editor.
* Fixed: Spacing around form fields on some themes.

= 3.1.6 - 2024-03-07 =

* Added: Compatibility with theme Aperitif.
* Added: Compatibility with theme Amphibious.
* Added: Compatibility with plugin Breakdance.
* Added: Compatibility with plugin MailChimp for WooCommerce.
* Added: Filter hook `fc_formatted_address_replacements_custom_field_keys` to allow developers to set formatted address replacements for custom fields.
* Improved: Show progress indication on the shipping methods and other sections when processing checkout updates.
* Improved: Compatibility with plugin Checkout Field Editor PRO by Themehigh. Add custom address fields from the plugin settings to formatted address replacements.
* Improved: Update billing or shipping address data on checkout when changed on the customer account profile. Does not apply when using the Address Book add-on.
* Improved: Enqueue fragments update assets on the page whenever that feature is enabled.
* Fixed: Compatibility with theme Phlox. Checkout elements not clickable because of position for Phlox page background element.
* Fixed: Compatibility with plugin SEUR Oficial. Only show information about the selected pickup point when the shipping method selected is SEUR 2Shop (pickup point).
* Fixed: JS error on checkout coupon code script when jQuery BlockUI is missing.
* Fixed: Only load Mailcheck script on the checkout page. Fixed the error "fcSettings is not defined" on other pages.
* Fixed: Convert form field classes to array before trying to merge them to add custom classes.
* Fixed: Keep optional field expanded when replacing fragments on some pages if the field has the focus and is cleared.

= 3.1.5 - 2024-02-21 =

* Bump tested up to WooCommerce 8.6.1
* Added: Compatibility with theme Kosi.
* Added: Compatibility with theme Pressmart.
* Added: Compatibility with theme BeTheme.
* Added: Compatibility with theme Iona.
* Added: Compatibility with plugin The Bluehost Plugin.
* Added: Compatibility with plugin SEUR Oficial.
* Added: Compatibility with plugin Nets Easy for WooCommerce by Krokedil (a.k.a Dibs Payments).
* Added: Compatibility with plugin Svea Checkout for WooCommerce by The Generation AB.
* Added: Partial compatibility with plugin States, Cities, and Places for WooCommerce. Trigger select events when appropriate.
* Added: Filter hook `fc_enable_checkout_email_mailcheck` allow developers to enable/disable the email field typo fix suggestions feature.
* Improved: Compatibility with plugin Germanized. Prevent Germanized from adding extra product thumbnails on the checkout page.
* Improved: Refactor scroll and focus functions moving them to the FCUtils script, making it available to the entire application.
* Improved: Add experiemental feature to replace `select2` fields with `TomSelect` enhanced select fields component. Fixes issues with quirky Select2 behaviors.
* Fixed: Compatibility with plugin Klarna Payments. Redirect after successful payment not working.
* Fixed: Compatibility with plugin FluentCRM PRO. Remove duplicate checkbox in the order notes section, only show it in the contact section.
* Fixed: Order pay page contents not being displayed in some cases.
* Fixed: Keep `select2` field open after replacing section which contains it, and keep focus on `select2` fields after updating fragments or selecting a different country.
* Fixed: Start checkout with "billing same as shipping" checked for registered customers when saved address data are the same for shipping and billing, and the option is enabled in the plugin settings.
* Fixed: Issue with content wider than screen on mobile when using certain themes.
* Fixed: Only use default checked state for the account creation checkbox when a value is not defined.

= 3.1.4 - 2024-02-02 =

* Bump tested up to WordPress 6.4.3 and WooCommerce 8.5.2
* Improved: Add process to automatically generate the installable zip file when creating a new version.
* Fixed: Merged changes from the original `checkout.js` file from the WooCommerce code into our modified copy.
* Fixed: Criteria for conditional function of cart page or fragments request.
* Fixed: Set to show shipping phone field values on the order admin order edit page.
* Fixed: Compatibility with plugins Klarna Checkout, Dintero Checkout and Payson Checkout by Krokedil. Fix layout of the checkout page template when one of these payment methods are selected.

= 3.1.3 - 2024-01-23 =

* Bump tested up to WooCommerce 8.5.1
* Added: Automatically replace the WooCommerce Checkout block with the shortcode-based form.
* Added: Admin notice for when using the Divi Builder checkout layout, which is not compatible with Fluid Checkout.
* Added: Compatibility with theme Cartsy.
* Added: Compatibility with theme Smart Home.
* Added: Compatibility with plugin Shipping Zones by Drawing for WooCommerce.
* Added: Compatibility with plugin Shipping Zones by Drawing Premium for WooCommerce.
* Improved: Compatibility with plugin Colissimo shipping methods for WooCommerce. Fix styles for the Colissimo Relay pickup button by setting the class `button` to it.
* Improved: Compatibility with 3rd-party plugins by restoring the checkbox "Shipping to a different address", but make it visually hidden.
* Improved: Added actions `fc_before_substep_fields_<substep_id>` and `fc_after_substep_fields_<substep_id>` to allow developers to output content to the substep fields section at those positions.
* Fixed: Compatibility with plugin MailerLite. Fix multiple AJAX requests being triggered by the MailerLite plugin and move checkbox field to expected positions.
* Fixed: Compatibility issues causing layout to break on the shipping method and payment methods options in some cases.
* Fixed: Shipping method inline validation not being triggered when there are no shipping methods available.

= 3.1.2 - 2024-01-06 =

* Fixed: Default value for the checkbox "Same as shipping/billing address" based on the plugin settings when first accessing the checkout page.

= 3.1.1 - 2024-01-05 =

IMPORTANT: This update fixes issues introduced with version 3.1.0 which may cause the payment section to keep loading indefinitely or the completed steps to not close properly when advancing to next steps.

* Fixed: Steps not closing to show review text when advancing to next step on multi-step mode.
* Fixed: Support for copying shipping from billing when first checking the option "Same as billing address" at checkout (PRO).
* Fixed: Moved shortcode wrappers setup to later on the request lifecycle to avoid PHP warnings when some functions of WooCommerce are used early, usually related to cart data initialization.
* Fixed: Changed the way `select2` fields are replaced when updating checkout fragments.

= 3.1.0 - 2024-01-03 =

* Added: Support for new PRO options for which position to show the billing address section on the checkout page, including before shipping and forced to same as shipping address.
* Added: Support for block themes using the Full Site Editor (FSE) mode.
* Added: Compatibility with plugin WooCommerce NL Postcode Checker by WP Overnight.
* Fixed: Check whether JS settings object is available before trying to use it in the `address-i18n` script.
* Fixed: Do not ask user before leaving the page if a redirect is needed after a successful payment is taken with some payment gateways.
* Fixed: PHP error on compatibility with plugin Klarna Checkout for WooCommerce.

= 3.0.7 - 2023-12-14 =

* Bump tested up to WordPress 6.4.2 and WooCommerce 8.4.0
* Added: EU-VAT Assistant to the list of add-ons on the plugin settings dashboard.
* Added: Compatibility with theme Goya.
* Improved: Added filter `fc_billing_same_as_shipping_field_value` to allow developers to change the field values copied from shipping address to billing address.
* Fixed: Force text color for form fields on shipping and billing address sections when section is highlighted.
* Fixed: Stretched payment method icons on mobile when custom styles are set by other plugins.
* Fixed: Ensure use of captured JS events, even when event propagation has been stop in some cases.
* Fixed: Maybe collapse substep edit section when step is complete when changing substep visibility. Fixes missing local pickup (PRO feature) address when switching shipping methods.
* Fixed: Remove extra text "Shipping" added by some themes to the shipping costs value column on the order summary.
* Fixed: Also register styles on admin page requests, but do not automtically enqueue them.

= 3.0.6 - 2023-11-15 =

* Bump tested up to WordPress 6.4.1 and WooCommerce 8.2.2
* Improved: Compatibility with theme Woodmart. Remove extra free shipping bar section from the billing section, displaying it only at the top of the checkout page.
* Improved: Make option "Display the 'Add' link buttons in lowercase" independent from other optional field options and clarify that it is also used for coupon code fields.
* Fixed: Compatibility with plugin Elementor PRO. Show navigation menus above the checkout progress bar and order summary.
* Fixed: Compatibility with plugin Brevo for WooCommerce (formerly Sendinblue).
* Fixed: Prevent fatal error when trying to load admin notices for DB migrations in some rare cases.
* Fixed: Call `wp_cache_flush()` directly when saving settings to avoid passing any parameters with wrong type or values.
* Fixed: Missing script dependency `jquery-blockui` for the checkout script file causing Javascript errors when dependencies are not loaded by other components.

= 3.0.5 - 2023-11-10 =

* Added: Compatibility with theme Gizmos.
* Added: Compatibility with theme Botiga.
* Added: Compatibility with plugin Botiga PRO.
* Added: Compatibility with plugin WooCommerce CobrosYA.com.
* Added: Compatibility with plugin Kadence Shop Kit (WooCommerce extras).
* Added: Compatibility with plugin DPD Baltic Shipping.
* Added: Compatibility with plugin "LP Express" Shipping Method for WooCommerce.
* Improved: Added filter `fc_checkout_address_i18n_override_locale_attributes` and `fc_checkout_address_i18n_override_locale_required_attribute` to allow overriding checkout field attributes that are locale dependent.
* Improved: Added action hooks `fc_order_summary_cart_item_totals_before` and `fc_order_summary_cart_item_totals_after` to display custom elements near the cart item total price in the order summary on the checkout page.
* Fixed: Cart item product total price alignment on the order summary in some cases.
* Fixed: Alignment for the add coupon code link when displayed on the order summary.
* Fixed: Fix values for billing phone field visibility settings to match accepted values from WooCommerce.
* Fixed: Compatibility with plugin Brazilian Market, check if phone fields are enabled before trying to use them, and update scripts with latest changes from original plugin.

= 3.0.4 - 2023-09-27 =

* Bump tested up to WordPress 6.3.1 and WooCommerce 8.1.1
* Added: Compatibility with plugin Dintero Checkout for WooCommerce by Krokedil.
* Added: New option to highlight the order totals row in the order summary on the checkout page.
* Added: New filter `fc_expansible_section_toggle_label_{$key}_add_optional_text` to allow removing the text "(optional)" from specific optional fields.
* Added: New filters `fc_order_summary_shipping_package_name` and `fc_order_summary_shipping_package_price_html` to allow changing the label and price for shipping charges on the order summary.
* Added: Fragments update script that can be used by add-ons on pages that don't use native WooCommerce functions to update fragments.
* Improved: Show localized price `0,00` (zero) as shipping charge price on the order summary when shipping method chosen does not have associated costs, instead of showing the shipping method name.
* Improved: Compatibility with theme OceanWP: fix container class when using the theme header, and disable conflicting theme features.
* Improved: Compatibility with plugin Mercado Pago payments for WooCommerce: set width to payment elements to avoid them overflowing the available space.
* Improved: Use only the cart total value for the cart link on header for mobile view, instead of getting also taxes and info from other plugins.
* Fixed: Styles for the shipping method items with classic and other design templates.
* Fixed: Styles for the latest payment method list items when the payment box is not present.
* Deprecated: Renamed function `FluidCheckout_CheckoutPageTemplate::get_hide_site_header_footer_at_checkout`, use `FluidCheckout_CheckoutPageTemplate::is_distraction_free_header_footer_checkout` instead.

= 3.0.3 - 2023-09-13 =

* Bump tested up to WooCommerce 8.1
* Added: New option to set visibility for the Shipping Company field as Required, Optional or Hidden (removed).
* Added: Compatibility with theme Artemis.
* Added: Compatibility with theme XStore.
* Added: Compatibility with plugin Storefront Powerpack.
* Added: Compatibility with plugin TI WooCommerce Wishlist Premium.
* Added: Partial compatibility with plugin Digits OTP, because changes to Digits plugin are needed for full compatibility.
* Improved: Compatibility with theme ZK Nito: add integration option to enable/disable extra shipping email and phone fields added by the theme.
* Improved: Compatibility with theme Riode: fix container class when using the theme header.
* Improved: Added inline validation for required checkboxes.
* Improved: Added text "(optional)" to the create account checkbox label when registration is not required.
* Improved: Display shipping package name, contents and destination on shipping method substep review text when order has multiple shipping packages.
* Improved: Pass `$substep_id` parameters to the filter `fc_no_substep_review_text_notice` so developers can change it for specific substeps.
* Improved: Position for expansible optional fields when displayed as a second column with class `form-row-last`.
* Fixed: Missing SVG logo when using distraction free checkout header.
* Fixed: Wrong address used for tax calculation in rare cases when shipping address was different than billing, but only billing address was required for the current cart items.

= 3.0.2 - 2023-08-24 =

* Bump tested up to WordPress 6.3 and WooCommerce 8.0.2
* Added: Compatibility with theme ZK Nito.
* Added: Compatibility with plugin Tilopay.
* Added: Compatibility with plugin Hungarian Pickup Points & Shipping Labels for WooCommerce (by Viszt Péter).
* Improved: Compatibility with theme Woodmart. Disable theme checkout options by default.
* Improved: Avoid triggering payment method field validation when updating checkout fragments.
* Improved: Handle name fields as a single line for displaying on the substep review text.
* Improved: Added filter hooks `fc_apply_address_1_field_description` and `fc_apply_address_2_field_description` to stop changing the address fields description and placeholder.
* Improved: Handle new custom arguments `optional_expand_link_label` and `optional_expand_link_lowercase` for checkout fields array to customize how optional field "+ Add <field>" links are displayed.
* Fixed: PHP warnings when setting shipping address from the shipping calculator on the cart page.
* Fixed: Show password toggle buttons on popup login form not working.
* Fixed: Comparison for checkout fields `required` attribute to accept type casting of non-boolean values.
* Fixed: Compatibility with plugin Checkout Field Editor PRO by ThemeHigh causing checkout process to validate conditional fields as required when fields are not available.
* Fixed: Remove validation icon from coupon field.
* Fixed: Force show coupon code related messages on some edge cases.

= 3.0.1 - 2023-08-04 =

* Improved: Compatibility with theme Astra and companion plugin Astra PRO.
* Improved: Compatibility with theme Woodmart.
* Improved: Add more CSS variables for changing the look of buttons.
* Fixed: Duplicate phone field data displayed on order received pages.

= 3.0.0 - 2023-07-18 =

* Bump tested up to WooCommerce 7.9
* Added: New feature to select design template for the checkout page, more design template options are available with [Fluid Checkout PRO](https://fluidcheckout.com/pricing/).
* Added: PRO settings on the plugin settings page so users can easily discover PRO features.
* Added: Compatibility with plugin BRT Fermopoint by BRT.
* Improved: Compatibility with plugin Payment Plugins for PayPal. Only change the state for checkout updates when the `click` event is triggered.
* Improved: Reorganized plugins settings in sections that are easier to understand and set up.
* Improved: Refactor settings to use centralized class with default values.
* Improved: Refactor CSS to use CSS variables for customization.
* Fixed: Auto selecting and overwriting text of email fields while typing.

= 2.5.2 - 2023-06-30 =

* Bump tested up to WordPress 6.2.2 and WooCommerce 7.8.1
* Added: Translation to Greek (Greece).
* Added: Compatibility with plugin Extra Product Options & Add-Ons for WooCommerce by ThemeComplete.
* Added: Compatibility with plugin EU/UK VAT for WooCommerce by WPFactory.
* Improved: Compatibility with theme Kadence. Use theme container class when using the theme's header and footer.
* Fixed: Position for payment method logos.
* Fixed: Losing focus while typing on address fields in some cases.
* Fixed: Compatibility with plugin German Market. Place order button missing on mobile in some cases.

= 2.5.1 - 2023-05-31 =

* Added: Compatibility with plugin GP Premium by GeneratePress.
* Improved: Compatibility with theme Enfold.
* Fixed: Password visibility button not showing when the form section is replaced via checkout fragments.

= 2.5.0 - 2023-05-29 =

IMPORTANT FIX: Critical error introduced in v2.4.0 where functionality was broken with some themes.

* Bump tested up to WooCommerce 7.7.0
* Added: Add instant validation for Brazilian documents fields CPF and CNPJ.
* Improved: Added CSS variables for many aspects of the design including: colors, borders, some sizing and spacing aspects.
* Improved: Compatibility with theme Electro. Support for all pre-defined theme colors, custom theme color and dark mode.
* Improved: Compatibility with plugin Brazilian Market v3.8.0 or newer. Use new Brazilian documents validation.
* Improved: Compatibility with plugin Mercado Pago payments for WooCommerce.
* Improved: Compatibility with plugin PayPal Brasil para WooCommerce.
* Improved: Compatibility with Delivery & Pickup Date Time for WooCommerce - by CodeRockz, avoid PHP warning messages when that plugin settings are not saved yet.
* Improved: Output JS settings object directly to the page head element without being associated with enqueued scripts.
* Improved: Prevent replacing the payment methods section while updating checkout fragments when user switches application or hide the browser app on their devices.
* Improved: Disable default checkout validation from WooCommerce when validation from Fluid Checkout is enabled.
* Fixed: Compatibility with plugin Oxygen Builder.
* Fixed: Compatibility with theme Minimog, missing dependencies on cart page breaking functionality.
* Fixed: Missing script dependencies breaking functionality on some themes.
* Fixed: Contact step being defined as incomplete when account registration is required but user is already logged in.
* Fixed: Unblock place order button if an unexpected error happens while trying to complete a purchase.

= 2.4.0 - 2023-04-21 =

* Bump tested up to WooCommerce 7.6.0
* Improved: Refactored scripts to reduce duplicate code of utility functions and variables.
* Improved: Removed dependency on the library RequireBundle to load scripts and styles.
* Improved: Revert to execute scripts on `load` instead of page `DOMContentLoaded` for better compatibility. Most notably with Cloudflare Rocket Loader.
* Improved: Compatibility with Delivery & Pickup Date Time for WooCommerce - by CodeRockz, avoid PHP warning messages when that plugin settings are not saved yet.
* Improved: Update file checkout.js with latest changes to the original file on the WooCommerce plugin.
* Fixed: Do not display "+ Add" link buttons for optional fields that are also hidden from the page.
* Fixed: Checkout steps script preventing ENTER key to execute some actions when inside some checkout fields.

= 2.3.4 - 2023-04-06 =

* Bump tested up to WordPress 6.2 and WooCommerce 7.5.1
* Added: New option to only show checkout sidebar widgets when viewing the last step of checkout on mobile devices.
* Added: Compatibility with plugin Woo Additional Terms by MyPreview.
* Added: Compatibility with plugin Woo Additional Terms PRO by MyPreview.
* Added: Compatibility with plugin YITH WooCommerce Wishlist by YITH.
* Improved: Compatibility with theme Divi, load checkout page preview when editing the page with the Divi Builder editor.
* Improved: Compatibility with theme Flatsome, correctly recover field focus on desktop devices when updating checkout fragments if the theme feature Float Labels is activated -- this does not work well for mobile devices due to browser limitations.
* Improved: Compatibility with plugin Payment Plugins for Stripe WooCommerce, fixing the position for the Stripe Link logo.
* Improved: Compatibility with plugin Elementor, display checkout steps when editing the checkout page on Elementor.
* Improved: Added new JS events `fc_checkout_fragments_replace_before` and `fc_checkout_fragments_replace_after` to allow external scripts to run processes before and after replacing checkout fragments.
* Improved: Added new filter `fc_coupon_code_field_description` to change or add a description below the coupon code field.
* Fixed: Duplicated MailCheck suggestion message.
* Fixed: Form loading indicator getting stuck after updating the checkout.js file to use native `fetch` function instead of jQuery Ajax.
* Fixed: Order summary getting sticky state on mobile when it should not, causing other elements to be overlapping and hidden.
* Fixed: Logic for the filter `fc_force_register_steps`.
* Fixed: Shipping step not working in some circunstances.
* Fixed: Fatal error when trying to determine if a checkout step should be rendered too early.

= 2.3.3 - 2023-03-22 =

IMPORTANT FIX: Critical error with the checkout steps count when shipping is not needed for the order, and the multi-step layout is used.

* Improved: Remove option to set billing address to same as shipping when the shipping address is not usable for billing.
* Improved: Try to expand or collapse fields after changing country on address sections.
* Fixed: Shipping address and shipping method not updating correctly on the checkout page when address is changed from the shipping calculator on the cart page.
* Fixed: Error on Brazilian Market plugin scripts introduced in previous versions of Fluid Checkout Lite.
* Fixed: Critical error with the checkout steps count when shipping is not needed for the order, and the multi-step layout is used.
* Fixed: Fatal error when trying to update checkout parts and the email field has an invalid email value.

= 2.3.2 - 2023-03-13 =

IMPORTANT FIX: Compatibility with plugin Payment Plugins for PayPal WooCommerce not working properly on mobile devices.

* Bump tested up to WooCommerce 7.5
* Improved: Add a safe location for translation files at `wp-content/languages/fluid-checkout/`.
* Improved: Support for language variations to use the main language translation. (Ie.: `es_AR` will use `es_ES` for the translation).
* Improved: Set constants `WOOCOMMERCE_CART` and `WOOCOMMERCE_CHECKOUT` when processing cart and checkout fragment requests respectively. This should fix compatibilty with some plugins.
* Improved: Update file checkout.js with latest changes to the original file on the WooCommerce plugin.
* Improved: Add security check to dismiss admin notice links and fix related PHP 8.1 deprecated notices.
* Fixed: Multiple issues when trying to customize template files.
* Fixed: Critical issue on compatibility with Payment Plugins for PayPal WooCommerce (version 1.0.25+) where the PayPal buttons and secure popup did not work properly on mobile devices, and sometimes also not on desktop devices.

= 2.3.1 - 2023-02-28 =

* Added: Translation to Polish (Poland).
* Added: New filter `fc_is_checkout_page_or_fragment` to set the current request as a checkout request in some cases.
* Added: Compatibility with plugin YITH WooCommerce Uploads Premium.
* Fixed: Compatibility with plugin Sendinblue - WooCommerce Email Marketing versions 3.0.0+.
* Fixed: Display notice to enter complete shipping address to see shipping methods available, instead of an error message from the start.
* Fixed: Login link when matching account is detected while trying to place an order with the PayPal plugin from Webtoffee.
* Fixed: Additional notes field not visible when previously hidden using the Elementor PRO Checkout widget.
* Fixed: Fatal error trying to save changes to the checkout page when using Elementor editor.

= 2.3.0 - 2023-01-27 =

POSSIBLY BREAKING CHANGES - Some template files were moved, which can cause customizations to those files to stop working. See documentation on [how to customize template files](https://fluidcheckout.com/docs/how-to-customize-template-files/) and fix possible issues with your customizations.

* Bump tested up to WooCommerce 7.4.0
* Added: Compatibility with theme Martfury.
* Added: Compatibility with plugin Klarna Checkout for WooCommerce by Krokedil.
* Added: Compatibility with plugin Klarna Payments for WooCommerce by Krokedil.
* Added: Compatibility with plugin PaysonCheckout for WooCommerce by Krokedil.
* Added: Functions `undo_hooks` to feature files to allow undoing hook changes in some rare cases.
* Improved: Persist checked state for create account checkbox and use Collapsible Block script to show/hide the account fields section.
* Improved: Add loading indicator on the place order button, and other buttons and input fields.
* Improved: Prevent starting "update checkout" requests while processing place order submit.
* Improved: Separate styles for checkout layout and checkout steps into different files, allowing to load them independently.
* Improved: Moved template files to a better structure, making it consistent with the PRO plugin structure and easier to understand. See documentation on [how to customize template files](https://fluidcheckout.com/docs/how-to-customize-template-files/)  and fix possible issues with your customizations.
* Improved: Changed the way plugin feature files are registered.
* Removed: Filter `fc_init_features_list` as it has no valid use case.
* Deprecated: FluidCheckout::locate_template, use FluidCheckout_Steps::locate_template instead.
* Deprecated: FluidCheckout_Steps::get_hide_site_header_footer_at_checkout, use FluidCheckout_CheckoutPageTemplate::get_hide_site_header_footer_at_checkout instead.
* Fixed: Set contact step as incomplete when create account checkbox is checked and required fields do not have a value.
* Fixed: Missing login form styles on some themes.
* Fixed: Only load modifield WooCommerce script files on the affected pages.
* Fixed: Compatibility with plugin Hezarfen causing pages to stop processing.

= 2.2.2 - 2023-01-12 =

* Added: Compatibility with theme Qi.
* Improved: Compatibility with theme Razzi.
* Improved: Refactor validation check icon styles to make it reusable for theme compatibility styles.
* Fixed: Initially set page content area width to 100%.
* Fixed: Position for the terms and conditions checkbox in relation to the label text.
* Fixed: Error while determining the next step when shipping is not needed for the order.
* Fixed: Fatal error at checkout page when using WooCommerce versions prior to 7.1.0.

= 2.2.1 - 2023-01-03 =

* Bump tested up to WooCommerce 7.2.2
* Added: Compatibility with theme PeakShops.
* Added: Compatibility with plugin Hezarfen for WooCommerce.
* Added: Compatibility with plugin Elementor PRO. Replace the custom checkout widget from Elementor PRO with Fluid Checkout.
* Improved: Moved remove default WooCommerce hooks later at `init` hook for better compatibility with various plugins.
* Fixed: Compatibility with plugin Payment Plugins for Stripe WooCommerce. Fixed iDeal bank dropdown field being cut off, and set its background color to white to make it stand out.
* Fixed: Do not cut off elements overflowing the payment and order summary elements' boundaries.
* Fixed: Modal styles not being loaded on all pages that use it.
* Fixed: Prevent fatal errors when trying to merge field class arguments using the checkout fields class.

= 2.2.0 - 2022-12-12 =

* Bump tested up to WooCommerce 7.2.0
* Added: Compatibility with plugin Payment Plugins for PayPal WooCommerce - by Payment Plugins.
* Added: Display the checkout page with cart items errors message, instead of a message to return to cart.
* Improved: Remove duplicate order summary section, which was causing compatibility issues with many plugins.
* Improved: Disable the "Log in" link button while loading the scripts.
* Improved: Execute script on `DOMContentLoaded` instead of page `load` event to enable interactive elements earlier.
* Improved: Update customized template files with latest changes in WooCommerce 7.2.
* Improved: Disable the "Log in" link button while loading the scripts which enable it to open the login popup section.
* Improved: Compatibility with theme Woodmart when setting a background color for the order summary section.
* Fixed: Hide login modal and other flyout elements while loading the page.
* Fixed: Fix required fields marker for accessibility. Stop adding `required` attribute to required fields as this sometimes breaks form validation.
* Fixed: Fatal error when using other plugins or themes that calls the template file `checkout/form-shipping.php` directly.

= 2.1.0 - 2022-12-05 =

* Added: Support for new PRO feature to edit cart items at checkout.
* Added: New filter `fc_pro_checkout_review_order_table_classes` to add additional classes to the order summary table.
* Added: Compatibility with theme Minimog.
* Added: Order summary will now display the product unit price below the product name.
* Improved: Compatibility with theme Divi.
* Improved: Compatibility with theme Orchid Store.
* Improved: Compatibility with theme Woostify.
* Improved: Refactor use of class `fc-fragment-always-replace` to force replacing checkout page fragments.
* Improved: Refactor styles for shipping methods pricing labels to automatically align to the center vertically when more elements are displayed.
* Improved: Check if template file exists in the override path before trying to use it.

= 2.0.9 - 2022-11-21 =

* Bump tested up to WordPress 6.1.1 and WooCommerce 7.1.0
* Added: Compatibility with the upcoming feature WooCommerce HPOS (High Performance Order Storage).
* Added: Compatibility with theme Astra PRO (Astra PRO add-on plugin).
* Added: Compatibility with plugin PayPal Brasil para WooCommerce.
* Added: Compatibility with plugin Woocommerce UPS Israel Domestic Printing Plugin.
* Added: Translation to German Formal (Sie). It is a copy of the German (Germany) translation which is already translated as German Formal (Sie).
* Improved: Compatibility with theme Astra.
* Improved: Compatibility with plugin Delivery & Pickup Date Time for WooCommerce (by CodeRockz). Refactor compatibility class to use checkout steps class directly.
* Fixed: Order summary table styles for better compatibility with various themes.
* Fixed: Always redirect back to checkout page after login when customer is logging in from the checkout page or login link button from the checkout page.

= 2.0.8 - 2022-10-28 =

* Bump tested up to WordPress 6.0.3 and WooCommerce 7.0.0
* Improved: Translations for Dutch, French, German, Italian, Spanish and Portuguese Brazil.
* Improved: Compatibility with theme Woodmart, fixing the styles for the Woodmart checkout steps section on the checkout page when using the plugin's header.
* Improved: Compatibility with theme Blocksy, fixing payment method logos stretched and checkboxes missing checked state styles.
* Improved: Remove checkout field validation classes ending with `-field` when clearing checkout field validation state.
* Removed: Admin notice about features moved to the PRO version.
* Fixed: Position of optional fields to always start a new row in the checkout form, making it easier to scan and find optional fields and fixes layout issues.
* Fixed: Returning invalid variable when trying to show login link on checkout error message for user already registered.
* Fixed: Billing phone required message being displayed when billing phone is displayed in the "Contact" step and "Billing same as shipping address" checkbox is checked and the shipping phone field is empty.

= 2.0.7 - 2022-09-13 =

* Bump tested up to WordPress 6.0.2 and WooCommerce 6.8.2
* Added: Compatibility with theme Understrap.
* Added: Compatibility with plugin Checkout Field Editor PRO by Themehigh, only basic features. For advanced features, you'll need Fluid Checkout PRO.
* Added: Translation to French (France).
* Improved: Translation to German (Germany).
* Improved: Original text in English US has been professionally revised for grammar mistakes and typos.
* Improved: Compatibility with plugin "Stripe For WooCommerce" by Payment Plugins.
* Improved: Compatibility with plugin PayPal Payments version 1.9.2+.
* Improved: Compatibility with theme Flatsome.
* Fixed: Order summary footer display styles on some themes.
* Fixed: Prevent copying shipping address to billing address when shipping address is not available in some cases.

= 2.0.6 - 2022-08-19 =

* Added: New filter `fc_step_title_<substep_id>` to allow changing the titles of each step and the corresponding labels for the "Proceed to <step>" buttons.
* Improved: Compatibility with theme Avada.
* Fixed: Order summary background color and spacing styles.
* Fixed: Mobile order summary "greyed out" when clicking the cart link on the site header in some themes.
* Fixed: Prevent checkout form submit, save substep, or try to advance to next step when pressing `ENTER` on some form fields. Instead, validate the field currently in focus.
* Fixed: Layout of form fields broken for some themes on small screens.
* Fixed: Set scroll position to the top of the last completed step after moving to the next step on mobile.
* Fixed: Set scroll position to the top of the substep when saving changes to it.
* Fixed: Stop closing keyboard on mobile devices while updating checkout fragments.

= 2.0.5 - 2022-08-13 =

* Bump tested up to WooCommerce 6.8
* Added: Payment method review text, in preparation for some features of the PRO version.
* Added: New action hook `fc_pro_checkout_review_order_after_coupon_code` for adding elements in the order summary after the coupon code, in preparation for some features of the PRO version.
* Improved: Added coupon code field section element and reference to element used to add or remove coupons, in preparation for some features of the PRO version.
* Improved: Compatibility with plugin Checkout Field Editor for WooCommerce by Themehigh (free version). Custom email fields now will suggest corrections for typos.
* Improved: Compatibility with theme Flatsome. When using floating labels on form fields, they should now appear inside the fields as expected after refreshing the page or when the checkout form is updated.
* Improved: Better performance while loading plugin and theme compatibility classes on websites with a big database. Replaced use of options (from database) with filter hooks.
* Improved: Changed the way the current step is determined, and defaults to the last step if all steps are already completed.
* Improved: Changed login button label on global WooCommerce login form template to be consistent across checkout.
* Improved: Use separate fragments for the order summary table on the checkout steps and sidebar sections.
* Fixed: Refocus on focused collapsible section toggle elements when updating the checkout fragments.
* Fixed: Checkout coupon code scripts from WooCommerce not being completely replaced when using coupon code features from the plugin.

= 2.0.4 - 2022-08-02 =

Using the Germanized plugin? Please read the details for the changes to compatibility with Germanized below.

* Added: New options for displaying the place order section.
* Added: New options to define visibility and section where to display the billing phone field.
* Added: New filter `fc_checkout_is_valid_phone_number` to allow customizing the checks for phone field validation.
* Improved: Compatibility with plugin Germanized. Removed hidden options to move the checkboxes and place order button. The position of those elements set by Germanized are now respected. Read more about why Germanized moves those elements here: https://vendidero.de/dokument/umsetzung-der-button-loesung-im-woocommerce-checkout
* Improved: Compatibility with plugin German Market. Checkboxes are now displayed before the order products when that option is enabled.
* Improved: Compatibility with PayPal Payments when using Germanized or German Market plugins.
* Improved: Filter hooks `fc_shipping_method_option_label_markup`, `fc_shipping_method_option_description_markup` and `fc_shipping_method_option_price_markup` now pass the `$method` parameter with the current shipping method being filtered.
* Removed: Deprecated option `fc_enable_checkout_place_order_sidebar`, which was replaced by the new options for displaying the place order section.
* Fixed: PHP Warning from Fluid Checkout after updating any plugin.

= 2.0.3 - 2022-07-22 =

* Bump tested up to WordPress 6.0.1 and WooCommerce 6.7
* Added: New filter `fc_checkout_header_cart_link_label_html` to allow customizing the cart link on the site header for mobile view.
* Added: New filter `fc_customer_meta_data_clear_fields_order_processed` to allow clearing customer meta fields when completing an order.
* Added: Compatibility with plugin Germanized PRO. Add notice for when the multistep checkout feature from the Germanized PRO plugin is enabled.
* Improved: Compatibility with theme Flatsome. Restore floating labels functionality.
* Improved: Replaced text "Sign in" with "Log in" to make it consistent with other parts of the plugin and WooCommerce.
* Improved: Change email field description to "Order number and receipt will be sent to this email address." and make it easier to change it through the new filter hook `fc_checkout_email_field_description`.
* Fixed: Show account creation notice also when guest checkout is disabled.
* Fixed: Mobile order summary "greyed out" when clicking the cart link on the site header in some themes.
* Fixed: Empty billing address fields and set default country and state entering a new address.
* Fixed: Country and State being replaced with default values when using Firefox and refreshing the page. This happened because Firefox tries to preserve user type information between requests.
* Fixed: Error on `select2` scripts when updating checkout causing usability and accessibility issues.
* Fixed: Compatibility with Germanized where the orders would not be processed for new customers in some cases.

= 2.0.2 - 2022-07-12 =

* Improved: Compatibility with plugin Sendinblue - WooCommerce Email Marketing.
* Improved: Compatibility with plugin Oxygen.
* Improved: Compatibility with theme Woodmart. You can now choose to display the Woodmart checkout steps section when using the Fluid Checkout header and footer.
* Improved: Add text "(optional)" to link buttons for optional fields.
* Improved: Styles for the create account section when account creation is mandatory.
* Fixed: Spacing around checkout widgets below the place order button.
* Fixed: Spacing around Fluid Checkout page content element.

= 2.0.1 - 2022-07-04 =

* Added: Compatibility with plugin Sendinblue - WooCommerce Email Marketing.
* Fixed: Spacing around checkout widgets.
* Fixed: Set default background color for the checkout footer when using the plugin's footer template.

= 2.0.0 - 2022-06-27 =

BREAKING CHANGES - Some features were removed from the Lite version and moved to the PRO version. Read details at https://fluidcheckout.com/version-2-moved-features/

* Removed: Moved features "Express Checkout", "Gift Options", "Local Pickup" and "Packing Slips" from Lite version to the PRO version.
* Bump tested up to WooCommerce 6.6.1
* Added: New filter `fc_output_checkout_contact_logout_cta_section` to enable displaying a logout link on the "My contact" substep when user is logged in. Defaults to "disabled".
* Improved: RTL support on account address edit screens.
* Improved: Utility colors (success, error, alert, info) to meet WCAG 2.1 level AA for accessibility constrast criterias. Although, this does not guarantee all elements meet the accessibility criterias.
* Improved: Change color for current step in the progress bar to same as complete steps, as there seems to be a consensus that this better communicates the current progress status.
* Improved: Do not load checkout assets on other pages.
* Improved: Renamed `account-page-address` style handle and files to `edit-address-page` to better indicate where the styles are loaded and keep consistency across the plugins.
* Improved: Set width for the login form which improves compatibility with various themes.
* Fixed: Lite version should not affect order pay or order received pages or when user must log in before being able to checkout.
* Fixed: Remove progress bar if cart is expired.
* Fixed: Missing styles for the add payment method page on account pages.
* Fixed: Missing the border on corners of some steps.
* Fixed: Moved login section to inside the "My contact" substep. Fixes the issue with login section not being displayed if user has already entered an email address.

= 1.6.1 - 2022-06-13 =

* Bump tested up to WordPress 6.0 and WooCommerce 6.5.1
* Added: Body class `fc-checkout-step-current--<step_id>` to let developers change elements styles based on the currently active step.
* Added: Feature to disable the place order buttons when not in the last step, activated by default. Use filter `fc_checkout_maybe_disable_place_order_button` to deactivate changes to the place order button `disabled` state.
* Added: Compatibility with theme Enfold.
* Added: Compatibility with theme Striz.
* Added: Compatibility with theme Razzi.
* Added: Compatibility with plugin CurieRO.
* Added: Compatibility with plugin WP Crowdfunding.
* Improved: Translations to Dutch.
* Improved: Compatibility with plugin Brazilian Market.
* Improved: Compatibility with plugin German Market.
* Fixed: Order summary height too big on desktop view in some instances, resulting in extra whitespace.
* Fixed: Duplicate IDs and field names when the additional place order section is displayed on the sidebar.
* Fixed: Missing compatibility RTL support for some themes.
* Fixed: JS error on checkout fragments script when fragments are returned in an unexpected format.
* Fixed: Fatal error related to coupon code fields functions calling `wc_coupon_enabled` too early.

= 1.6.0 - 2022-05-19 =

* Added: New option to enable/disable the Checkout Progress Bar feature.
* Added: New hooks `fc_shipping_methods_before_packages_inside` and `fc_shipping_methods_after_packages_inside`.
* Added: Translation to Dutch (Netherlands). Thanks to Robin Bak, Duncan - magnesium-minerals.nl, Damy Bosch - advice.nl.
* Improved: Clear object cache with `wp_cache_flush` when saving settings or updating the plugin.
* Improved: Moved some options from the "Advanced" to "Tools" and "Checkout" settings tabs. Removed the "Advanced" settings tab.
* Improved: Only register checkout header widget areas when using the Fluid Checkout header template.
* Improved: Display the shipping calculator above the shipping methods in the cart page (PRO feature).
* Improved: Refactor make SCSS code reusable by using variables like `$_body-theme-selector` and `$_body-page-selector`.
* Improved: Only display no shipping methods message on the checkout page when using the template file shipping-methods-available.php.
* Improved: Refactor integrated coupon code feature to use own AJAX functions and scripts.
* Improved: Refactor extract coupon code styles into a separate file.
* Improved: Allow fragments to be replaced every time even when their contents are equal the existing elements in the DOM when they contain any element with class `fc-fragment-always-replace`.
* Improved: Compatibility with plugin Brazilian Market.
* Improved: Compatibility with theme Shoptimizer.
* Improved: Compatibility with themes, set expected styles for cart items rows in the order summary.
* Improved: Spacing around trust symbols widget areas.
* Fixed: Hide shipping methods on the cart page when WooCommerce the option "Hide shipping costs until an address is entered" is checked (PRO feature).
* Fixed: Run hooks `fc_shipping_methods_before_packages`, `fc_shipping_methods_after_packages` only on initial page load skip on AJAX fragments requests.
* Fixed: Do not attempt to output the admin Gift Message edit form on the front end.

= 1.5.8 - 2022-05-03 =

* Added: New hooks `fc_checkout_before_step_shipping_fields_inside` and `fc_checkout_after_step_shipping_fields_inside` which contents are replaced with every checkout update.
* Improved: Compatibility with WooCommerce Delivery & Pickup Date Time Pro by CodeRockz, when selecting the delivery fields position as "After the shipping address", it will be displayed after the "Shipping Methods" section when shipping methods are displayed after the "Shipping Address" section in the checkout page.
* Improved: Also display "edit cart" link on order summary for mobile devices.
* Fixed: Run hooks `fc_checkout_before_step_billing_fields`, `fc_checkout_after_step_billing_fields`, `fc_checkout_before_step_shipping_fields` and `fc_checkout_after_step_shipping_fields` only on initial page load skip on AJAX fragments requests.
* Fixed: Moved hook `woocommerce_checkout_after_customer_details` out of the form-billing.php template file, now run on the hook `fc_checkout_after_step_billing_fields`.

= 1.5.7 - 2022-04-12 =

* Improved: Change the default position for the shipping methods section to after the shipping address. The position for the shipping methods section can be changed in the plugin settings.
* Fixed: Checkout fields arguments merge functions replacing some existing classes.

= 1.5.6 - 2022-04-11 =

* Fixed: Fatal error (JS) when `select2` script is disabled on the checkout page. Fixes issue with page fragments loading indefinitely.
* Fixed: Fatal error (PHP) when changing some checkout fields arguments.

= 1.5.5 - 2022-04-06 =

* Added: Compatibility with theme LeadEngine.
* Fixed: Not updating checkout options while typing the postcode and other address fields when shipping phone feature is enabled.
* Fixed: Not updating checkout options while entering the billing address.
* Fixed: Jumping to the top of the page, most notably on mobile, when `select2` fields break while updating the checkout page.
* Fixed: Fatal error when Checkout Widgets feature is disabled while WooCommerce PayPal Payments plugin is active.

= 1.5.4 - 2022-03-29 =

* Added: Compatibility with plugin Fluent CRM.
* Added: Compatibility with plugin Klaviyo.
* Added: Compatibility with plugin MailerLite WooCommerce Integration.
* Added: Compatibility with plugin MailPoet.
* Added: Compatibility with plugin Polylang.
* Added: Translation to Italian (Italy). Thanks to Samuele from floralgarden.it.
* Added: Translation to Turkish (Turkey). Thanks to Orkun Akça.
* Improved: Compatibility with Brazilian Market, set fields as required according to the person type selected.
* Fixed: Email validation should consider an empty optional email field as valid.
* Fixed: Remove duplicate phone numbers on emails.
* Fixed: Layout and alignment of the place order section.
* Fixed: Letter case for guest checkout section separator.

= 1.5.3 - 2022-03-03 =

* Added: Support for RTL languages.
* Added: New option to enable/disable Local Pickup features.
* Added: New option to select position for the shipping methods substep (before or after shipping address).
* Added: New filter `fc_checkout_login_button_class` for changing login button classes.
* Improved: Compatibility with WooCommerce PayPal Payments, fixes missing spacing around the payment buttons.
* Improved: Refactor move pickup point to its own substep, instead of using the shipping address substep to display the shop address.
* Improved: Refactor make filters `fc_substep_{$substep_id}_attributes` available to all substeps.
* Fixed: Restore previous values entered for the billing address when switching back to new billing address ("same as shipping" checkbox unchecked).
* Fixed: Restore previous values entered for the shipping address when switching between "Local pickup" and other shipping methods.
* Fixed: Shipping costs being shown with tax included when tax settings is set to display without tax included.
* Fixed: State field validation message for required field displaying even when field is optional.
* Fixed: Fatal error when our checkout fields optimization feature is disabled.

= 1.5.2 - 2022-02-14 =

* Added: Compatibility with theme Avada.
* Added: Compatibility with theme Electro.
* Added: Compatibility with theme The Hanger.
* Added: Compatibility with theme Phlox PRO.
* Added: Compatibility with theme Zota.
* Added: Compatibility with plugin Flexible Shipping.
* Added: Compatibility with plugin PagSeguro for WooCommerce.
* Added: Compatibility with plugin WooCommerce Affirm Gateway.
* Added: New filter `fc_checkout_update_before_unload` to let developers control whether to try to save users data when leaving the checkout page.
* Improved: Compatibility with plugin WooCommerce PayPal Payments - by WooCommerce. Now the buttons are displayed below the terms checkbox as expected.
* Improved: Refactor checkout script to make better use of `fcSettings`.
* Fixed: Position for the hook `woocommerce_after_shipping_rate` to be displayed inside the shipping method `<label>` element.
* Fixed: Prevent fatal errors when using the Plugin Organizer or similar plugins. Also checks if the function `WC` is available before before loading the plugin features.

= 1.5.1 - 2022-02-03 =

* Added: Compatibility with plugin Brazilian Market on WooCommerce - by Claudio Sanches.
* Added: New filters `fc_is_step_complete_shipping_field_keys_skip_list` and `fc_is_step_complete_billing_field_keys_skip_list` to skip validating required fields in order to determine if the steps are complete or not.
* Added: Add new classes for form fields `form-row-one-third`, `form-row-two-thirds` and `form-row-middle`.
* Improved: Validate shipping methods fields selection on the client-side.
* Fixed: Remove duplicate product image on checkout order summary for some themes.
* Fixed: Do not set first shipping method as selected from the template file, instead, let WooCommerce manage the chosen shipping method.
* Fixed: PHP warning `Undefined array key "type"` when trying to get the substep review text for custom fields.

= 1.5.0 - 2022-01-28 =

* Bump tested up to WordPress 5.9 and WooCommerce 6.1
* Added: New filter `fc_checkout_update_fields_selectors` for CSS selectors used to trigger update the checkout fragments.
* Added: New filters `fc_is_billing_same_as_shipping_checked` and `fc_output_billing_same_as_shipping_as_hidden_field` for billing same as shipping.
* Added: New filter `fc_is_billing_address_data_same_as_shipping_before` to allow developers to hijack the returning value for the function `FluidCheckout_Steps::is_billing_address_data_same_as_shipping_before()`.
* Added: Function to get list of address field keys, necessary for Address Book (PRO) feature.
* Added: New class `fc-no-validation-icon` for checkout field classes to prevent or remove the validation check icon.
* Added: New class `fc-skip-hide-optional-field` to skip hiding optional checkout fields.
* Added: New debug mode advanced option.
* Added: New "Tools" settings section. Only available where there are tools to be displayed.
* Added: New filter `fc_billing_same_as_shipping_option_label` to change the label for the option "billing address same as shipping".
* Added: Compatibility with plugin Creative Mail.
* Improved: Color contrast set by Fluid Checkout to pass WCAG 2.1 AA.
* Improved: Renamed the checkout settings subtab from "Checkout options" to "Checkout".
* Improved: Compatibility with plugin WooCommerce Stripe Payment Gateway - by WooCommerce, will not show Express Checkout section if the Stripe payment gateway is not available.
* Improved: Compatibility with plugin Checkout Field Editor for WooCommerce (free) - by Themehigh, now the changes applied to billing and shipping fields are also applied to the address edit form on the account pages.
* Improved: Compatibility with theme Neve, login form is now displayed in the modal as expected.
* Improved: Compatibility with plugin Checkout Field Editor for WooCommerce. Add option to make changes to checkout fields affect account edit address screen.
* Improved: Display contact substep fields based on the order of field keys in the contact fields list.
* Improved: Dynamically display contact substep field values on the substep review text when the step is completed.
* Improved: Refactor custom admin setting types moving each type to their own files.
* Improved: Add `state`, `country` and `select` field types to the optional fields to hide behind an "add" link.
* Improved: Refactor replace use of `$checkout` variable from `WC()->checkout()` in multiple places.
* Improved: Display shipping only fields after the fields in common with the billing section (same as billing only fields).
* Improved: Refactor normalize theme compat styles to use theme specific selector `body.theme-slug`, where `slug` is the actual theme slug.
* Improved: Refactor functions to generate substep review text with array of lines for easier customization.
* Improved: Display custom fields in the substep review text.
* Improved: Change function priority get checkout field values from persisted posted data or session to `100`, previously `10`.
* Improved: Also update the checkout form and order summary when the browser tab gets visible again, as when changing tabs.
* Improved: Change order of gift message field to before the gift from/sender field to make it consistent with other parts of the website.
* Fixed: Stretched product images on the checkout order summary.
* Fixed: Fatal error while editing the checkout page on Elementor, and possibly other page editors.
* Fixed: Skip setting posted data to session or customer object when the `post_data` request parameter is not provided, avoiding the values from being cleared unintentionally.
* Fixed: Remove field values from session in case they are not provided with the `post_data` parameter, fixes not being able to unselect/uncheck optional `checkbox`, `radio` and `select` fields.
* Fixed: Parse posted data for multiple-value/multi-select fields as arrays.
* Fixed: Use filtered parsed posted data when getting field keys to save to customer session.
* Fixed: Shipping and billing phone numbers being displayed twice on order confirmation page.
* Fixed: Missing borders between some steps and substeps.
* Fixed: Maybe get shipping country value from session when appropriate.
* Fixed: Allow HTML elements for gift message text, message footer and information text on Packing Slip documents.
* Fixed: Display gift message section on Packing Slip documents even when option to display gift message as part of the totals table is enabled.
* Fixed: Typo in the filter name, renaming `fc_adress_field_keys_skip_list` to `fc_address_field_keys_skip_list`.
* Fixed: Checks for shipping and billing address when determining if the steps are complete to use the correct country values when addresses were changed by hooks.
* Fixed: Prevents fatal error on admin pages by checking for available resources before calling them.
* Fixed: Added the missing hook `woocommerce_checkout_after_customer_details` back to the checkout page after the billing form.
* Fixed: Validation of fields in the contact substep.
* Fixed: Styles for `select2` fields to fill 100% width of available field container space.
* Fixed: Styles for `select2` multiple selection fields for various themes.
* Fixed: Only display shipping phone in the contact step review text when the field is available.
* Removed: Duplicate filter hook `fc_general_settings`, instead use the hook `fc_checkout_general_settings`.

= 1.4.3 - 2022-01-12 =

* Added: New actions `fc_checkout_header_widgets_inside_before` and `fc_checkout_header_widgets_inside_after` to add content inside the checkout header widget area via PHP code.
* Improved: Moved checkout header widgets to before the cart icon link in the template file `fc/checkout/checkout-header.php`.
* Improved: Compatibility with plugin WooCommerce Stripe Payment Gateway - by WooCommerce.
* Fixed: Use of deprecated function `is_ajax` on payment.php template file since WooCommerce 6.1.0.
* Fixed: Fatal error while adding gift message styles to email notifications if only one parameter is provider, while two parameters are expected.

= 1.4.2 - 2022-01-04 =

* Bump minimum required version to PHP 7.4.
* Added: Translation to Spanish (Spain). Thanks to Giomar Morales from senseiwpacademy.com.
* Added: Compatibility with plugin German Market.
* Added: Compatibility with plugin WooCommerce Authorize.Net Gateway.
* Added: Compatibility with plugin Captcha Pro by BestWebSoft.
* Added: Compatibility with plugin WooCommerce Amazon Pay.
* Added: Compatibility with theme Hello Elementor.
* Added: Compatibility with theme Orchid Store.
* Added: Compatibility with theme Diza.
* Added: New widget area to display trust symbols below the place order button.
* Added: Option to set a background color for the checkout page. Refactor checkout header background color to output custom styles in a `<style>` tag.
* Added: New filter `fc_display_checkout_page_title` to make the checkout page title visible. When hidden, checkout page title is output as `screen-reader-only`. Defaults to hidden.
* Added: New filter `fc_checkout_express_checkout_section_title` to allow changing the express checkout section title.
* Added: New filter `fc_output_checkout_contact_login_cta` to control whether to display the call to action "Already have an account? Log in." in the contact step.
* Added: New action `fc_checkout_below_contact_login_cta` to allow adding content to the contact login substep.
* Added: New option `checkoutEnablePreventUnload` to the `fcSettings` to allow disabling the "data loss protection" script when closing the browser tab with unsaved changes to checkout fields.
* Updated: `collapsible-block` library to version 1.1.7.
* Improved: Block the place order button with attribute and class `disabled` when processing the order to prevent user from submitting duplicate orders.
* Improved: Compatibility with plugin Germanized for WooCommerce.
* Improved: Compatibility with theme Neve.
* Improved: Compatibility with theme Astra.
* Improved: Compatibility with various themes and possibly plugins that add a payment section after the order review section.
* Improved: Compatibility of email styles from the Gift Options feature with other plugins.
* Fixed: Display coupon code messages below the substep title, instead of above.
* Fixed: Re-focus on email field after applying email typo suggestion.
* Fixed: Also consider a link button as focusable when setting focus while opening a section.
* Fixed: Run additional order notes hooks `woocommerce_before_order_notes` and `woocommerce_after_order_notes` when order notes field is disabled or removed.
* Fixed: Scroll position when proceeding to next steps was being calculated wrong on some themes.
* Fixed: Removed background color from `fieldset` elements on payment method fields section.
* Fixed: Billing address form not displaying when the shipping country was not yet selected on single-step layout mode.
* Fixed: Certain SVG logo images not being displayed on the checkout header.
* Fixed: Fatal error when activating Fluid Checkout on older versions of PHP.

= 1.4.1 - 2021-12-03 =

* Added: Translation to German. Thanks to @reilix.
* Added: Compatibility with theme Kentha.
* Added: Compatibility with theme MrTailor.
* Added: Compatibility with theme Riode.
* Added: Compatibility with WooCommerce Delivery & Pickup Date Time Pro by CodeRockz.
* Added: Conditionally add the shipping package name to the shipping method section. Added the hook `fc_shipping_method_display_package_name` to control whether to display the package name.
* Added: Filter to change button classes for "Proceed to next step" buttons.
* Added: New filter `fc_substep_title_<substep_id>` to allow changing the titles of each substep.
* Improved: Compatibility with theme Woodmart.
* Improved: Added function to allow unregistering checkout steps.
* Improved: Allow collapsible sections to be created without a toggle link when toggle label is `null`.
* Improved: Use registered checkout fields to display shipping and billing addresses substep review text.
* Fixed: Missing no shipping method message sometimes. Changes to that message styles where necessary to make it work properly.
* Fixed: Missing billing fields sometimes when allowed countries settings are changed.
* Fixed: Billing address being overwritten with same as shipping for logged users.
* Fixed: Only check for options and hooks when preparing additional notes substep.
* Fixed: Missing borders for some steps or substeps.
* Fixed: JS error preventing proceed to next step when progress elements are not present.
* Fixed: Compatibility with theme Impreza when required plugin UpSolution Core is not activated.
* Fixed: Prevent fatal error while login when WooCommerce session is not available.
* Fixed: Prevent fatal error on admin screens when the WooCommerce session object is not available.

= 1.4.0 - 2021-10-26 =

* Bump tested up to WooCommerce 5.8
* Added: New functions to handle anonymous functions used in hooks (closure).
* Added: Compatibility with theme Aora by Thembay.
* Added: Compatibility with theme Phlox by averta.
* Added: Compatibility with theme Impreza by UpSolution.
* Improved: Automatically determine the label for the "Proceed to <next step>" button based on the registered steps. Custom translations will need to be updated.
* Improved: Refactor of the front-end checkout validation script to allow developers to add validation type extensions.
* Improved: Compatibility with theme Neve.
* Fixed: Checkout fields values should be replaced with data from registered customer profile when user logs from the checkout page or otherwise. Renamed hook `fc_customer_persisted_data_clear_fields` to `fc_customer_persisted_data_clear_fields_order_processed`.
* Fixed: When adding new steps, functions to get current and next steps and outputting the progress bar now works as expected.
* Fixed: Translation of shipping package names to match what is used by WooCommerce.
* Fixed: Coupon code "Add" link label now respects the option to make field labels `lowercase`, instead of always making it `lowercase`.
* Removed: Step registration argument `next_step_button_label` is no longer used as the label of the button to proceed to next step is now retrieved dynamically.

= 1.3.2 - 2021-10-04 =

* Fixed: Fix build process to save theme compat files in the right place.

= 1.3.1 - 2021-10-01 =

* Added: New option to move shipping phone field to the contact step.
* Added: Compatibility with theme Divi by Elegant Themes.
* Improved: Coupon code field and items shows loading status while processing adding or removing a coupon code.
* Improved: Refactoring of Express Checkout feature, and added option to disable it.
* Improved: Developement build scripts to use shared gulpfile.js, updated `gulp-sass` to 5.0.0.
* Improved: Admin settings structure. Added "Integrations" subtab. Moved "Optional fields" and "Address Fields" settings into the "Features" section of the "Checkout Options" subtab.
* Improved: Add parameter to allow setting custom attributes to substeps elements.
* Improved: Add mechanism to conditionally make substeps non-editable or hidden.
* Improved: Compatibility with theme Woodmart version 6.1.4+, fixing social login forms and styles for coupon codes.
* Fixed: Fatal error because steps were not registered on admin pages and AJAX requests. Fixes compatibility with page editor Elementor.

= 1.3.0 - 2021-09-22 =

* Bump tested up to WooCommerce 5.7
* Added: Compatibility for plugin "Germanized for WooCommerce - by vendidero".
* Added: Compatibility styles for plugin "MailPoet - by MailPoet".
* Added: New option to hide the additional order notes field. Saves to the WooCommerce option `woocommerce_enable_order_comments`.
* Improved: Moved local pickup functions and customizations to a new class, potentially breaking sites with customizations that rely on these functions.
* Improved: Changed the hook used to initialize the plugin features from `plugins_loaded` to `after_setup_theme` to allow themes to customize early plugin settings and features.
* Improved: Show "Pickup point" as the substep title. Text can be changed by using the filter `fc_shipping_address_local_pickup_point_title`.
* Improved: Add option "Make 'Add' link buttons lowercase" to prevent plugin from changing the optional fields link buttons to `lowercase` when keeping the letter case is necessary.
* Improved: Changed the markup for the checkbox "Same as shipping address" for better compatibility with WooCommerce form field styles.
* Improved: Compatibility styles for checkbox and validation check icon for Blocksy theme.
* Fixed: Fields of type `hidden` being wrapper in expansible hidden field sections.
* Fixed: Steps count was including the shipping step when not needed or disabled.
* Fixed: Order summary title styles breaking the layout on some themes.
* Fixed: Only make the labels of total line as `uppercase` instead of the whole line.
* Fixed: Removed extra margin on collapsible form sections on some themes.
* Fixed: Unintended checkout update triggered for some fields. Fixes issue preventing users to fill payment information for the plugin "Mercado Pago payments for WooCommerce - by Mercado Pago".
* Removed: Unused `cart-totals.php` template file.

= 1.2.10 - 2021-09-10 =

* Added: New filter hook `fc_coupon_code_field_initially_expanded` to allow displaying the coupon code field always expanded.
* Improved: Fix plugin and theme compatibility styles enqueue function to use filter hook instead of options to allow disabling loading compatibility files.
* Fixed: Fix substep "Additional notes" being displayed even when all fields are removed.
* Fixed: Typos and info in the readme.txt.

= 1.2.9 - 2021-08-18 =

* Bump tested up to WooCommerce 5.6
* Added: New action hooks `fc_before_substep_<substep_id>` and `fc_after_substep_<substep_id>`.
* Improved: Add compatibility with plugin "Sg Checkout Location Picker for WooCommerce" by Sevengits.
* Improved: Add compatibility with plugin "SG Map to Address" by Sevengits.
* Improved: Add compatibility with plugin options for delivery or pickup date and time for "Delivery & Pickup Date Time for WooCommerce (Free)" by CodeRockz.
* Fixed: Billing fields not being copied properly when using the Astra theme.
* Fixed: Only display payment request buttons at checkout if enabled in the settings for the plugin "WooCommerce Stripe Gateway" by WooCommerce.
* Fixed: Focus position changing inside text fields when updating the checkout sections.
* Fixed: Compatibility with Loco Translate for custom location for translation files, should possibly fix it for other translation plugins.

= 1.2.8 - 2021-08-12 =

* Added: Support for express payment buttons for the plugin "WooCommerce Stripe Gateway" by WooCommerce.
* Added: Support for themes "Shoptimizer" and "Woodmart".
* Improved: Better accessibility, with support for keyboard-only navigation and descriptive content for screen readers.
* Improved: Added more space for product names and details on the order summary.
* Improved: Change the way plugin compatibility classes and styles are loaded, extending support for WordPress Multi-site mode.
* Fixed: Coupon code field height on some themes.

= 1.2.7 - 2021-08-09 =

* Fixed: Missing assets in release 1.2.6

= 1.2.6 - 2021-08-09 =

* Fixed: Prevent "Fatal errors" on WooCommerce settings page when the type of the `$settings` parameter is not an `Array`.

= 1.2.5 - 2021-08-02 =

* Bump tested up to WordPress 5.8 and WooCommerce 5.5
* Added: New filter hook `fc_place_order_button_classes` to allow developers to change the place order button classes.
* Added: Handy "Settings" link on the plugins list.
* Added: New feature to automatically hide shipping address fields when "Local Pickup" is selected.
* Improved: Moved action hooks `fc_checkout_before_step_shipping_fields` and `fc_checkout_after_step_shipping_fields` do inside the shipping address fields wrapper element.
* Improved: Update translation to pt-BR.
* Fixed: Added the place order section as a fragment in the checkout page as it is expected from the original WooCommerce behavior.
* Fixed: Login link on error message for existing email does not open the login modal.
* Removed: Links to external feedback platform. Favoring WordPress Support Forums instead.

= 1.2.4 - 2021-07-20 =

* Added: Plugin compatibility styles enqueue functions.
* Added: Compatibility with plugin "Mercado Pago payments for WooCommerce" by Mercado Pago.
* Added: Compatibility with plugin "Stripe For WooCommerce" by Payment Plugins.
* Fixed: Broken icon markup for some payment methods.
* Fixed: Position for payment method icons to the right at checkout.
* Fixed: Payment methods styles forcing display of payment method options not available for the some devices.

= 1.2.3 - 2021-07-17 =

* Improved: Add compatibility with plugin "Delivery & Pickup Date Time for WooCommerce (Free)" by CodeRockz.
* Fixed: Conflict with plugin "Merge + Minify + Refresh" by Launch Interactive preventing checkout features to work.
* Fixed: Add back the hooks `woocommerce_checkout_billing` and `woocommerce_checkout_shipping` for better compatibility. Changed template files `form-billing.php` and `form-shipping.php`.
* Fixed: Added missing clearings to some checkout sections which were allowing overlapping fields.

= 1.2.2 - 2021-07-06 =

* Fixed: Gift message not displaying on emails when the option "display as part of order details table" was checked

= 1.2.1 - 2021-07-05 =

* New feature: Added information message box for packing slips, works with __WooCommerce PDF Invoices & Packing Slips (by Ewout Fernhout)__ and __WooCommerce Print Invoices/Packing Lists (by SkyVerge)__.
* Fixed: Wrong check preventing compatibility classes from loading on the admin pages.
* Fixed: Display gift message on packing slips.
* Fixed: Select2 field height for themes Storefront, OnePress, PopularFX and Zakra.

= 1.2.0 - 2021-06-25 =

* First public release.
