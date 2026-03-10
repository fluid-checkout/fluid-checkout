(function ($) {

    // CHANGE: Add event delegation to account for dynamically added elements.
    jQuery(document).on('click', '.fooevents-copy-from-purchaser', function () {

        var billing_first_name = jQuery('#billing_first_name').val();
        var billing_last_name = jQuery('#billing_last_name').val();
        var billing_email = jQuery('#billing_email').val();
        var billing_phone = jQuery('#billing_phone').val();
        var billing_company = jQuery('#billing_company').val();

        var parent = jQuery(this).parent('p').parent('div');

        parent.find('input').closest('.fooevents-attendee-first-name input').val(billing_first_name);
        parent.find('input').closest('.fooevents-attendee-last-name input').val(billing_last_name);
        parent.find('input').closest('.fooevents-attendee-email input').val(billing_email);
        parent.find('input').closest('.fooevents-attendee-telephone input').val(billing_phone);
        parent.find('input').closest('.fooevents-attendee-company input').val(billing_company);

        return false;

    });

    if ('autocopy' == frontObj.copyFromPurchaser || 'autocopyhideemail' == frontObj.copyFromPurchaser) {

        var billing_first_name = jQuery('#billing_first_name').val();
        var billing_last_name = jQuery('#billing_last_name').val();
        var billing_email = jQuery('#billing_email').val();
        var billing_phone = jQuery('#billing_phone').val();
        var billing_company = jQuery('#billing_company').val();

        jQuery('.fooevents-attendee-first-name input').val(billing_first_name);
        jQuery('.fooevents-attendee-last-name input').val(billing_last_name);
        jQuery('.fooevents-attendee-email input').val(billing_email);
        jQuery('.fooevents-attendee-telephone input').val(billing_phone);
        jQuery('.fooevents-attendee-company input').val(billing_company);

        // CHANGE: Add event delegation to account for dynamically added elements.
        jQuery(document).on('change', '.woocommerce-billing-fields input:not(.attendee-class input)', function () {

            var billing_first_name = jQuery('#billing_first_name').val();
            var billing_last_name = jQuery('#billing_last_name').val();
            var billing_email = jQuery('#billing_email').val();
            var billing_phone = jQuery('#billing_phone').val();
            var billing_company = jQuery('#billing_company').val();

            jQuery('.fooevents-attendee-first-name input').val(billing_first_name);
            jQuery('.fooevents-attendee-last-name input').val(billing_last_name);
            jQuery('.fooevents-attendee-email input').val(billing_email);
            jQuery('.fooevents-attendee-telephone input').val(billing_phone);
            jQuery('.fooevents-attendee-company input').val(billing_company);

        });

    }

})(jQuery);