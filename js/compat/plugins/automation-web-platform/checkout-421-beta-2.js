jQuery(document).ready(function($) {
    var otpSent = false;
    var resendInterval;
    var resendTimer = 5;
    var initialResendTimer = 5;

    var $awpResendOtpBtn = $('#awp_resend_otp_btn');
    var $awpResendTimer = $('#awp_resend_timer');
    var $billingPhoneField = $('#billing_phone_field');
    var $awpOtpPopup = $('#awp_otp_popup');
    var $userPhoneNumber = $('#user_phone_number');
    var $checkoutForm = $('form.woocommerce-checkout');
    var otpInputs = $('.otp-input');
    var $verificationMessage = $('#verification_message');
    // CHANGE: Add new variable
    var $billingPhoneInput = $('#billing_phone');

    // Consolidated Functions
    function startResendTimer() {
        $awpResendOtpBtn.prop('disabled', true);
        updateResendTimerText();
        resendInterval = setInterval(function() {
            resendTimer--;
            updateResendTimerText();
            if (resendTimer <= 0) {
                clearInterval(resendInterval);
                $awpResendOtpBtn.prop('disabled', false);
                $awpResendTimer.text('');
                resendTimer = initialResendTimer;
            }
        }, 1000);
    }

    function updateResendTimerText() {
        $awpResendTimer.text(' (' + resendTimer + 's)');
    }

    function showPopupMessage(message, isSuccess) {
        // CHANGE: Change the message class to make it consitent with the rest of the messages
        var messageClass = isSuccess ? 'woocommerce-success' : 'woocommerce-error';

        // CHANGE: Replace $billingPhoneField variable with $billingPhoneInput and change selector for the message element search
        var messageElement = $billingPhoneInput.next('.fc-wawp-message');
        if (messageElement.length === 0) {
            messageElement = $('<span class="fc-wawp-message"></span>');
            // CHANGE: Replace $billingPhoneField variable with $billingPhoneInput
            $billingPhoneInput.after(messageElement);
        }
        messageElement
            // CHANGE: Replace the custom classes with the default WooCommerce classes
            .removeClass('woocommerce-success woocommerce-error')
            .addClass(messageClass)
            .text(message)
            .show();

        var popupMessageElement = $awpOtpPopup.find('.awp-popup-message');
        if (popupMessageElement.length === 0) {
            popupMessageElement = $('<div class="awp-popup-message"></div>');
            $awpOtpPopup.find('.awp-otp-content').prepend(popupMessageElement);
        }
        popupMessageElement
            .removeClass('custom-message-success custom-message-error')
            .addClass(messageClass)
            .text(message)
            .show();
    }

    async function sendOtp(phoneNumber, firstName) {
        try {
            const response = await $.post({
                url: otpAjax.ajaxurl,
                data: {
                    action: 'send_otp',
                    phone_number: phoneNumber,
                    first_name: firstName,
                    security: otpAjax.nonce
                }
            });
            if (response.success) {
                console.log("OTP sent successfully");
                showPopupMessage(awp_translations.otp_sent_success, true);
                $awpOtpPopup.show();
                otpSent = true;
                startResendTimer();
            } else {
                console.log("Failed to send OTP");
                showPopupMessage(awp_translations.otp_sent_failure, false);
            }
        } catch (error) {
            console.log("Error in sending OTP", error);
            showPopupMessage(awp_translations.otp_sent_failure, false);
        }
    }

    async function verifyOtp() {
        var otp = '';
        otpInputs.each(function() {
            otp += $(this).val();
        });

        try {
            const response = await $.post({
                url: otpAjax.ajaxurl,
                dataType: 'json',
                data: {
                    action: 'verify_otp',
                    otp: otp,
                    security: otpAjax.nonce
                }
            });

            if (response.success) {
                console.log("OTP verified successfully");
                showPopupMessage(awp_translations.otp_verified_success, true);

                // Hide the OTP popup after successful verification
                $awpOtpPopup.hide();

                // CHANGE: Trigger update checkout event
                $( document.body ).trigger( 'update_checkout' );

                // CHANGE: Re-add plugin's previosly removed code to update the user's phone number as meta data
                const phoneNumber = $billingPhoneInput.val();
                if ( otpAjax.isLoggedIn ) {
                    await $.post({
                        url: otpAjax.ajaxurl,
                        data: {
                            action: 'update_user_phone_number',
                            phone_number: phoneNumber,
                            security: otpAjax.nonce
                        }
                    });
                }

                // CHANGE: Replace the checkout form submit event with the default one
                return true;
            } else {
                console.log("Incorrect OTP");
                showPopupMessage(awp_translations.otp_incorrect, false);
            }
        } catch (error) {
            console.log("Error in verifying OTP", error);
            showPopupMessage(awp_translations.otp_incorrect, false);
        }
    }

    function clearOtpInputs() {
        otpInputs.val('').prop('disabled', true).css({ 'background-color': '#cacaca', 'border': '#cacaca' });
        otpInputs.first().prop('disabled', false).css('background-color', 'white');
    }

    // Event Handlers
    $(document).on('click', '#fc-wawp-verify-button', async function(e) {
        // CHANGE: Allow repeated OTP verification
        if ($awpOtpPopup.is(':hidden')) {
            e.preventDefault();
            var phoneNumber = $('#billing_phone').val();
            var firstName = $('#billing_first_name').val();

            // CHANGE: Clear previously entered OTP code
            clearOtpInputs();

            $userPhoneNumber.text(phoneNumber);
            sendOtp(phoneNumber, firstName);
            $awpOtpPopup.show();
            otpInputs.first().focus(); // Always focus on the first input initially
        }
    });

    $(document).on('click', '.awp-otp-popup-close', function() {
        $awpOtpPopup.hide();
        otpSent = false;
        clearOtpInputs();
    });

    // Auto Verify OTP After All Inputs are Filled
    otpInputs.on('input', function() {
        if ($(this).val().length === this.maxLength) {
            $(this).next('.otp-input').prop('disabled', false).css('background-color', 'white').focus();
        }

        // Style the current and unfilled fields accordingly
        otpInputs.each(function() {
            if ($(this).val() === '') {
                $(this).css({ 'background-color': '#cacaca', 'border': '#cacaca' });
            } else {
                $(this).css({ 'background-color': 'white', 'border': '1px solid #ccc' });
            }
        });

        // Check if all OTP inputs are filled
        var allFilled = otpInputs.filter(function() {
            return $(this).val() === '';
        }).length === 0;

        if (allFilled) {
            verifyOtp();
        }
    });

    otpInputs.on('keydown', function(e) {
        if (e.key === 'Backspace') {
            if ($(this).val().length === 0) {
                $(this).prev('.otp-input').focus();
            }
        }
    });

    otpInputs.on('paste', function(e) {
        var clipboardData = e.originalEvent.clipboardData.getData('text');
        otpInputs.each(function(index) {
            $(this).val(clipboardData[index] || '');
        });

        // Automatically verify OTP if all fields are filled
        var allFilled = otpInputs.filter(function() {
            return $(this).val() === '';
        }).length === 0;

        if (allFilled) {
            verifyOtp();
        }
    });

    $(document).on('click', '#awp_verify_otp_btn', verifyOtp);

    $(document).on('click', '#awp_resend_otp_btn', function() {
        var phoneNumber = $('#billing_phone').val();
        var firstName = $('#billing_first_name').val();
        sendOtp(phoneNumber, firstName);
        resendTimer += 5;
        updateResendTimerText();
    });

    $(document).on('click', '#awp_edit_phone_btn', function() {
        $awpOtpPopup.hide();
        otpSent = false;
        $('#billing_phone').focus();
    });

    // Initialize OTP Inputs State
    clearOtpInputs(); // Ensures that the first field is ready for entry when the page is loaded
});
