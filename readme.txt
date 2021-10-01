=== Fluid Checkout for WooCommerce ===
Contributors: fluidwebco, diegoversiani, luiggiab
Tags: woocommerce, e-commerce, checkout, conversion, multi-step, one-page
Requires PHP: 7.2
Requires at least: 5.0
Tested up to: 5.8
Stable tag: 1.3.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Provides a distraction free checkout experience for any WooCommerce store. Ask for shipping information before billing in a truly linear multi-step or one-step checkout, add options for gift message, and display a coupon code field at the checkout page that does not distract your customers. Similar to the Shopify checkout, and even better.


== Description ==

Fluid Checkout simplifies and improves the checkout experience on WooCommerce websites for your new and repeating customers with a truly linear, Shopify-like checkout.

Eliminate unnecessary friction at the checkout page and benefit from better conversion rates, customer satisfaction, and earned customer loyalty.

Ask for shipping information before billing in a **multi-step or one-step checkout**, easily add trust symbols, add options for gift message and packaging and display a coupon code field that does not distract your customers.

Similar to the Shopify checkout, and even better!

Better accessibility at the checkout page with support for keyboard-only navigation and screen readers.

[View detailed list of features on our website](https://fluidcheckout.com/features/)

= Demos =

* [Multi step layout](https://demos.fluidcheckout.com/multi-step/cart/?add-to-cart=14&quantity=2)
Make sure to test the checkout flow by adding to the cart and completing the purchase at least two times to see how the plugin streamlines the process for repeat customers.

* [Single step layout](https://demos.fluidcheckout.com/single-step/cart/?add-to-cart=22&quantity=2)
Single step checkout is recommended when only a few fields are required, usually when selling digital products or allowing local store pickup where shipping address information is not needed.

* [Highly stylized theme](https://demos.fluidcheckout.com/theme-deli/cart/?add-to-cart=14&quantity=2)
With themes that are highly stylized such as Storefront Deli -- when comparing to a plain theme such as the default Storefront theme, Fluid Checkout adapts itself with part of the theme's styles, keeping the same look and feel while improving the experience.

= Lite Version (Free) =

* **Multi-step or Single-step**: Choose between multi-step and one-step checkout layouts. While Fluid Checkout changes the layout of the checkout page, it will still look and feel like your website.

* **Optimized for mobile**: Fluid Checkout is optimized for mobile devices and will surface the most appropriate keyboard type on fields such as phone and email. In fact, Fluid Checkout was created with mobile devices in mind, and enhanced with more functionality and style for bigger screens.

* **Easily add trust symbols to the checkout page**: Add any widget such as accepted payment methods, security badges, reviews, testimonials, or anything that can boost the perceived trust customers have on the website. The checkout page contains widget areas displayed at strategic positions:

1. Order Summary: at the bottom of the order summary, below the order details and the place order button when present.
2. Checkout Sidebar: displayed on the sidebar, below the order summary.
3. Checkout Header - Desktop: at the checkout header -- only displayed on desktop devices and when using the plugin's header and footer templates.
4. Checkout Header - Mobile: at the top of the page, right below the checkout header -- only displayed on mobile devices and when using the plugin's header and footer templates.

* **Shipping before billing**: Customers expect to fill up shipping information before thinking about billing, by asking for the shipping information before billing, we remove unnecessary friction, matching the customer's expectations.

* **Hide optional fields**: The average checkout page has 16 open fields, by removing optional fields from the immediate view we can reduce that number to about 8-9 fields. These fields can still be entered as the customer can click the "Add <insert optional field name>" links to reveal the fields they need. Examples of these fields are the "Company", "Address line 2", "Phone" and "Order notes".

* **Instant field validation**: The default WooCommerce checkout only validates when the form is submitted, leading to confusion and frustration. Customers want the "Place order" button to be the last thing they click to complete their purchase. Some things can only be validated when placing the order, such as if the credit card is valid and has enough funds to cover the order total, however, most errors at checkout can be prevented by instantly validating the customer data.

* **Integrated coupon code field at the checkout**: When users see an open coupon code field at the checkout page the changes they will leave the website and go "coupon hunting" is very high, and they might not come back. The integrated coupon code field is displayed in a custom expansible section, and while less noticeable is still discoverable by customers who have a coupon and need to add it.

* **Offer gift options**: Customers can add a gift message to their order, to be printed with the packing slip generated by popular invoices and packing slips plugins.

* **Shipping phone field**: Add a separate phone field for shipping-related questions, in addition to the native billing phone field.

* **Automatically saved customer data**: customers won't lose any information they already have entered on the checkout page, and will get back exactly where they left when re-visiting it. Only payment information won't be saved for security reasons.

* **Skip completed steps**: Repeat customers will love how easy it is to complete their next purchase. Fluid Checkout skips the steps where all required information is provided and validated while providing an easy way to review and change any of the information.

* **Log-in without leaving the checkout**: Repeat customers with an account registered can log in from the checkout page directly without having to visit another page and make their way back to checkout.


= PRO Version =

We are working to bring to you the following PRO features:

* Cart page optimization
* Edit cart at checkout
* Thank you / Order confirmation page
* [Google Address Autocomplete for WooCommerce](https://fluidcheckout.com/product/fc-google-address-autocomplete/)
* Customize checkout steps and fields
* Account matching, let registered customers complete the purchase without logging in and attach the order to their account
* Account pages optimization

**[Google Address Autocomplete for WooCommerce](https://fluidcheckout.com/product/fc-google-address-autocomplete/) is now available as a stand-alone plugin**.


= Need more features? =

**[Request a feature](https://support.fluidcheckout.com/).**


= Tested WooCommerce Themes =

By default, Fluid Checkout works with every WooCommerce theme. Some themes may need adjustments due to not using WooCommerce standards hooks or styles. We've tested certain third-party WooCommerce themes to ensure better compatibility with Fluid Checkout:

* Astra
* Blocksy
* Divi
* Flatsome
* Generate Press
* Hello Elementor
* Kadence
* Neve
* Ocean WP
* Shoptimizer
* Storefront
* OnePress
* Woodmart
* Woostify
* Zakra

** Don't see your theme in the list? No problem, try Fluid Checkout now and if you experience any issues please let us know through the [support forum](https://wordpress.org/support/plugin/fluid-checkout/) and we'll fix it asap.**


= Compatible with popular plugins =

* Germanized for WooCommerce - by vendidero
* WooCommerce PDF Invoices & Packing Slips - by Ewout Fernhout
* WooCommerce Print Invoices/Packing Lists - by SkyVerge

** Don't see a plugin in the list? No problem, try Fluid Checkout now and if you experience any issues please let us know through the [support forum](https://wordpress.org/support/plugin/fluid-checkout/) and we'll fix it asap.**


= Fully Customizable =

In addition to a number of default settings (including a custom header/logo), multi-step or single step layout types and easily enabling/disabling features from settings page, the plugin contains HTML/PHP based templates and provides many filters and action hooks that allow for customization.

To customize template files, copy the templates to your theme folder, add a hook to the filter `fc_override_template_with_theme_file` to allow the plugin's version of the template to be overriden by the your customized template file.

To customize styles, when making small adjustments just add your custom CSS through your theme, the Customizer Custom CSS field or using a plugin.

If you need more control, you can remove the plugin styles and add your own complete custom CSS files. If you are familiar with SASS and other code building tools, the original SASS files are included in the plugin and can be modified and rebuilt, most styles make use of SASS variables that can make the process a lot easier.


== Translations ==

All labels and other texts added or changed by the plugin are translatable using the built in WordPress functions.

Currently the plugin is translated into the languages below, and more are comming soon:

* English (Default)
* Portuguese - Brazil

** Comming soon **

* Spanish - Argentina (soon)
* Dutch (soon)
* German (soon)
* German - Formal (soon)
* Italian (soon)

** Note: **
Fluid Checkout is fully localized/translatable. This is very important for all users worldwide.
Please contribute your language to the plugin to make it even more useful.
For translating we recommend the plugin ["Loco Translate - By Tim Whitlock"](https://wordpress.org/plugins/loco-translate/).


== Installation ==

= Automatic installation =

* Log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.
* Search for "Fluid Checkout for WooCommerce", and press "Install now".
* Or, press "Upload Plugin" and select the zip file, then press "Install Now".

= Settings =

Once installed and activated, the Fluid Checkout will take over the WooCommerce checkout page and change its layout to the **multi-step** option. The default settings were chosen to minimize checkout abandonment and to work for most shops. Some features need to be enabled on the settings page before you see them at the checkout page.

If you want to tweak the settings, head over to WP Admin > WooCommerce > Settings > Fluid Checkout.


== Frequently Asked Questions ==

= Will Fluid Checkout work with my theme? =

Yes! Fluid Checkout should work with most theme out-of-the-box. However some themes may need adjustments due to not using WooCommerce standard hooks or styles.

**If you have any issues using Fluid Checkout with your theme please let us know through the [support forum](https://wordpress.org/support/plugin/fluid-checkout/) and we'll fix it asap.**

= Does Fluid Checkout work with the plugins I use on my webshop? =

Although Fluid Checkout was built in a way that it should be compatible with most plugins, the checkout page is a really complex part of WooCommerce and there are myriads of plugins that extends it. Because of that, it is likely that some plugins won't work optimaly with Fluid Checkout out-of-the-box.

**If you have any issues using Fluid Checkout with other plugins please let us know through the [support forum](https://wordpress.org/support/plugin/fluid-checkout/) and we'll fix it asap.**

= Is Fluid Checkout fully compatible with ADA/WCAG 2.1 Level AA requirements? Will it make my webshop compliant? =

No. While Fluid Checkout does improve the accessibility of the checkout page by implementing support for keyboard-only navigation and screen readers, **we can't say if your webshop will be 100% compliant or not** with ADA, WCAG or any other accessibility requirements. 

Did you know that only about 30% of accessibility issues can be detected with automated tools? The majority of the issues can only be detected with manual testing on each page of the website. Read the article: [Automated Accessibility Testing Tools: How Much Do Scans Catch?](https://www.essentialaccessibility.com/blog/automated-accessibility-testing-tools-how-much-do-scans-catch).

We have plans to add an "accessible layout" feature that will ensure most accessibility issues will be fixed.

**[Contact us](https://fluidcheckout.com/support/) if you need help with making your webshop accessible to people with disabilities.**

= How do I add trust symbols to the checkout page? =

The plugin provides widget areas in strategic positions on the checkout page for adding the trust symbols. Head over to WP Admin > Appearance > Widget Areas, and add any type of widget to boost the perceived trust customers have on the website.

= How do I get Fluid Checkout for WooCommerce PRO? =

We are working on building the PRO version of Fluid Checkout. Visit [our website](https://fluidcheckout.com) and sign up to be the first to know when it's ready.


== Screenshots ==

1. Step 1 - Contact: Email, account creation and newsletter sign-up
2. Step 2 - Shipping: Shipping address, shipping method, gift options, additional order notes
3. Step 3 - Billing: Billing address and company information
4. Step 4 - Payment: Payment options and discounts
5. Single Step: All fields are displayed in one single step.
6. Settings: Multi-step or single-step layout, choose a logo and header color, enable/disable features
7. Theme Deli: The checkout page looks and feels like your website, even with highly styled themes


== Changelog ==

= Unreleased =
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


== Upgrade Notice ==

= 1.3 =
Moved local pickup functions and customizations to a new class, potentially breaking sites with customizations that rely on these functions.
