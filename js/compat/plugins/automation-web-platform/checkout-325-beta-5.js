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
        var messageClass = isSuccess ? 'custom-message-success' : 'custom-message-error';

        var messageElement = $billingPhoneField.next('.woocommerce-message.custom-message');
        if (messageElement.length === 0) {
            messageElement = $('<div class="woocommerce-message custom-message"></div>');
            $billingPhoneField.after(messageElement);
        }
        messageElement
            .removeClass('custom-message-success custom-message-error')
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
        $('.otp-input').each(function() {
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

                if (!otpAjax.isLoggedIn) {
                    sessionStorage.setItem('otp_verified', 'true');
                }

                const phoneNumber = $('#billing_phone').val();
                if (otpAjax.isLoggedIn) {
                    await $.post({
                        url: otpAjax.ajaxurl,
                        data: {
                            action: 'update_user_phone_number',
                            phone_number: phoneNumber,
                            security: otpAjax.nonce
                        }
                    });
                }

				// CHANGE: Trigger update checkout event
				$(document.body).trigger('update_checkout');

                setTimeout(function() {
                    $awpOtpPopup.hide();
                    // CHANGE: Remove checkout form submission
                }, 2000);
            } else {
                console.log("Incorrect OTP");
                showPopupMessage(awp_translations.otp_incorrect, false);
            }
        } catch (error) {
            console.log("Error in verifying OTP", error);
            showPopupMessage(awp_translations.otp_incorrect, false);
        }
    }

    $(document.body).on('checkout_error', function() {
        $awpOtpPopup.hide();
    });

    // CHANGE: Add more selectors to trigger OTP verification
    $(document).on('click', '#place_order, .fc-checkout-step .fc-step__substep-save, .fc-checkout-step .fc-step__next-step', async function(e) {
    if (otpAjax.enableForVisitorsOnly === 'yes' && otpAjax.isLoggedIn === 'true') {
        return true;
    }

    if (!otpSent && $awpOtpPopup.is(':hidden')) {
        e.preventDefault();
        var phoneNumber = $('#billing_phone').val();
        var firstName = $('#billing_first_name').val();
        $userPhoneNumber.text(phoneNumber);

        try {
            const response = await $.post({
                url: otpAjax.ajaxurl,
                dataType: 'json',
                data: {
                    action: 'verify_phone_number',
                    phone_number: phoneNumber,
                    security: otpAjax.nonce
                }
            });

            if (response.success) {
                if (response.data.status === 'verified') {
					// CHANGE: TODO:: Trigger button click from the inital event instead of submitting the checkout form

					// If target of the event is the substep button
					if ( e.target.matches('.fc-checkout-step .fc-step__substep-save') ) {
						return true;
					}

                    // CHANGE: Remove checkout form submission
                } else if (response.data.status === 'not_verified') {
                    sendOtp(phoneNumber, firstName);
                    $awpOtpPopup.show();
                } else if (sessionStorage.getItem('otp_verified') === 'true') {
					// CHANGE: TODO:: Trigger button click from the inital event instead of submitting the checkout form
					// If target of the event is the substep button
					if ( e.target.matches('.fc-checkout-step .fc-step__substep-save') ) {
						return true;
					}
                    // CHANGE: Remove checkout form submission
                } else {
                    sendOtp(phoneNumber, firstName);
                    $awpOtpPopup.show();
                }
            } else {
                if (response.data.status === 'not_verified') {
                    sendOtp(phoneNumber, firstName);
                    $awpOtpPopup.show();
                } else {
                    showPopupMessage(response.data.message, false);
                }
            }
        } catch (error) {
            showPopupMessage(awp_translations.phone_registered, false);
        }
    }
});


    $(document).on('click', '.awp-otp-popup-close', function() {
        $awpOtpPopup.hide();
        otpSent = false;
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

    $(document).on('input', '.otp-input', function() {
        if ($(this).val().length === this.maxLength) {
            $(this).next('.otp-input').focus();
        }
        var otp = '';
        $('.otp-input').each(function() {
            otp += $(this).val();
        });
        if (otp.length === 6) {
            verifyOtp();
        }
    });

    $(document).on('keydown', '.otp-input', function(e) {
        if (e.key === 'Backspace' && $(this).val().length === 0) {
            $(this).prev('.otp-input').focus().val('');
        }
    });

    $(document).on('paste', '.otp-input', function(e) {
        var clipboardData = e.originalEvent.clipboardData.getData('text');
        var otpInputs = $('.otp-input');
        for (var i = 0; i < clipboardData.length; i++) {
            $(otpInputs[i]).val(clipboardData[i]);
        }
        otpInputs[clipboardData.length - 1].focus();
        var otp = '';
        $('.otp-input').each(function() {
            otp += $(this).val();
        });
        if (otp.length === 6) {
            verifyOtp();
        }
    });
});
