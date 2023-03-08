# Changelog

All notable changes to this project will be documented in this file and the plugin's readme.txt file.

To avoid duplicate work, changes are first added to the [plugin's readme.txt file](https://github.com/fluidweb-co/fluid-checkout/blob/main/readme.txt), then after a few iterations, they are moved to this file.

The format is based on the [WordPress plugin readme file standard](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Given a version number MAJOR.MINOR.PATCH, increments are made to:

- MAJOR version when incompatible API changes are introduced,
- MINOR version when new functionality is added in a backwards compatible manner, and
- PATCH version when backwards compatible bug and security fixes are made.

Additional labels for beta builds are available as extensions to the MAJOR.MINOR.PATCH format (ie. 1.5.0-beta-1).


# CHANGES

[See latest changes in the plugin's readme.txt](https://github.com/fluidweb-co/fluid-checkout/blob/main/readme.txt)

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
