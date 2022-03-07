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
* Fixed: When adding new steps, functions to get current and next steps and outputing the progress bar now works as expected.
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
