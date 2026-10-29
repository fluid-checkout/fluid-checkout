(function ($) {
    let lockerSelected = false;
    let iframeObserver = null;
    let popupIframe = null;
    let checkoutUIRefreshScheduled = false;
    let lastPersistedLockerId = null;

    const boxNowTrustedOriginRegex = /^https:\/\/.*\.boxnow\..*$/;

    function handleBoxNowMessage(event) {
        // 1. Must be from our iframe
        if (boxNowDeliverySettings.displayMode === "popup" && (!popupIframe || event.source !== popupIframe.contentWindow)) {
            return;
        }

        const data = event.data;
        const origin = event.origin;
        const isAllowed = boxNowTrustedOriginRegex.test(origin);

        // Ignore untrusted origins. Only listen for BOX NOW events
        if(!isAllowed){
            // TODO 3.1.1: console error logs untrusted origins from other plugins that may try to postMessage. This may lead to console flooding. 
            //console.error('BOX NOW Delivery SHORTCODE: untrusted origin, ignoring message', { origin });
            closeBoxNowPopup()
            return
        }
        
        // Safety check: ignore non-object messages unless it's "closeIframe"
        if (typeof data !== "object" && data !== "closeIframe") {
            return;
        }

        // Now it's safe to process the message
        // Handle close message
        if (data === "closeIframe" || (typeof data === "object" && data !== null && data.boxnowClose !== undefined)) {
            // Remove popup overlays/iframes
            if (boxNowDeliverySettings.displayMode === "popup") {
                closeBoxNowPopup(); 
            }
            return;
        }

        // Handle locker data selection
        updateLockerDetailsContainer(data, {
            persistToSession: true,
            closePopup: true,
        });
        lockerSelected = true;
    }

    function closeBoxNowPopup() {
        popupIframe = null;

        if (iframeObserver) {
            iframeObserver.disconnect();
            iframeObserver = null;
        }

        $("#box_now_delivery_iframe_popup").remove();
        $("#box_now_delivery_overlay").remove();
    }

    /**
     * Add the BOX NOW Delivery button or embedded map.
     */
    function getBoxNowShippingMethodAnchor() {
        var input = $('input[name^="shipping_method"][value*="box_now_delivery"]').first();

        if (!input.length) {
            return $();
        }

        var inputId = input.attr("id");
        var option = input.closest("li, .woocommerce-shipping-methods__item, .shipping_method");
        var label = option.find("label").filter(function () {
            return !inputId || this.htmlFor === inputId;
        }).first();

        if (!label.length && inputId) {
            label = $("label").filter(function () {
                return this.htmlFor === inputId;
            }).first();
        }

        return label.length ? label : input;
    }

    function addButton() {
        var useEmbedded = boxNowDeliverySettings.displayMode === "embedded" && boxNowDeliverySettings.page === "checkout";
        var shippingMethodAnchor = getBoxNowShippingMethodAnchor();

        if (
            $("#box_now_delivery_button").length === 0 &&
            !useEmbedded &&
            shippingMethodAnchor.length
        ) {
            var buttonText = boxNowDeliverySettings.buttonText || "Pick a locker";

            shippingMethodAnchor.after(
                '<button type="button" id="box_now_delivery_button" style="display:none;">' +
                buttonText +
                "</button>"
            );

            attachButtonClickListener();
        } else if (useEmbedded) {
            $("#box_now_delivery_button").remove();
            $("#box_now_delivery_iframe_popup").remove();
            $("#box_now_delivery_overlay").remove();
            popupIframe = null;

            if (iframeObserver) {
                iframeObserver.disconnect();
                iframeObserver = null;
            }

            if ($("#box_now_delivery_embedded_map").length === 0 && shippingMethodAnchor.length) {
                shippingMethodAnchor.after(
                    '<div id="box_now_delivery_embedded_map" style="display:none;"></div>'
                );
            }

            if ($("#box_now_delivery_embedded_map").length) {
                embedMap();
            }
        }
        applyButtonStyles();
    }

    /**
     * Apply the custom styles for the BOX NOW Delivery button.
     */
    function applyButtonStyles() {
        var buttonColor = boxNowDeliverySettings.buttonColor || "#6CD04E";

        var styleBlock = `
      <style id="box-now-delivery-button-styles">
        #box_now_delivery_button {
          background-color: ${buttonColor} !important;
          color: #fff !important;
        }
      </style>
    `;

        $("#box-now-delivery-button-styles").remove();
        $("head").append(styleBlock);
    }

    /**
     * Attach click event listener to the BOX NOW Delivery button.
     */
    function attachButtonClickListener() {
        // CHANGE: Use event delegation so the handler survives Fluid Checkout shipping method fragment refreshes.
        $( document )
            .off( 'click.boxNowDelivery', '#box_now_delivery_button' )
            .on( 'click.boxNowDelivery', '#box_now_delivery_button', function ( event ) {
                event.preventDefault();
                createPopupMap();
            } );
    }

    function GetUserCountry() {
        let selectedCountry;

        // Modified if clause that mitigates for shipping, billing address and cases where only one service country is selected.
        if ($('#ship-to-different-address-checkbox').is(":checked")) {
            // Check if the shipping country field is a select or hidden input
            if ($('select[name="shipping_country"]').length) {
                // If it's a select, get the selected value
                selectedCountry = $('select[name="shipping_country"]').val();
            } else if ($('input[name="shipping_country"]').length) {
                // If it's a hidden input, get the value directly
                selectedCountry = $('input[name="shipping_country"]').val();
            }
        } else {
            // Check if the billing country field is a select or hidden input
            if ($('select[name="billing_country"]').length) {
                // If it's a select, get the selected value
                selectedCountry = $('select[name="billing_country"]').val();
            } else if ($('input[name="billing_country"]').length) {
                // If it's a hidden input, get the value directly
                selectedCountry = $('input[name="billing_country"]').val();
            }
        }

        return selectedCountry;
    }
    /**
     * Embed the map to the page.
     */
    function embedMap() {
        var embeddedMap = $("#box_now_delivery_embedded_map");
        var isSelected = isBoxNowDeliverySelected();

        $("#box_now_delivery_button").hide();
        if (!embeddedMap.length || !isSelected) {
            embeddedMap.hide();
            return;
        }

        var iframe = embeddedMap.find("iframe");
        var widgetUrl = getEmbeddedWidgetUrl();
        var currentWidgetUrl = iframe.attr("data-box-now-src") || iframe.attr("src");

        if (iframe.length && currentWidgetUrl !== widgetUrl) {
            iframe.remove();
            iframe = $();
        }

        if (iframe.length === 0) {
            iframe = createEmbeddedIframe(widgetUrl);

            var lockerDetailsContainer = $("<div>", {
                id: "box_now_selected_locker_details",
                css: {
                    display: "none",
                    marginTop: "10px",
                },
            });

            // Create a new div to hold the locker information
            var lockerInfoContainer = $("<div>", {
                id: "locker_info_container",
            });

            embeddedMap
                .css({
                    position: "relative",
                    width: "100%",
                    height: "80vh", // Set the height to 100%
                    overflow: "auto"
                })
                .prepend(iframe);

            if (embeddedMap.find("#locker_info_container").length === 0) {
                embeddedMap.append(lockerInfoContainer.append(lockerDetailsContainer));
            }
        }

        embeddedMap.show();
        showSelectedLockerDetailsFromLocalStorage();
    }

    // Overlay for the popup iframe
    function createOverlay() {
        var overlay = $("<div>", {
            id: "box_now_delivery_overlay",
            css: {
                position: "fixed",
                top: 0,
                left: 0,
                width: "100%",
                height: "100%",
                backgroundColor: "rgba(0, 0, 0, 0)",
                zIndex: 9998,
            },
        });

        overlay.on("click", function () {
            closeBoxNowPopup()
        });

        $("body").append(overlay);
    }

    /**
     * Create an iframe for the popup map.
     */
    function createPopupMap() {
        // Prevent duplicate popups
        if ($("#box_now_delivery_iframe_popup").length) {
            return;
        }

        let src;
        let gpsOption = boxNowDeliverySettings.gps_option;
        let partnerId = boxNowDeliverySettings.partnerId;
        let postalCode = $('input[name="billing_postcode"]').val();
        let country = GetUserCountry();

        if (country === "CY") {
            src = "https://widget-v5.boxnow.cy/popup.html"; // TODO 3.1.1: update when available
        } else if (country === "BG") {
            src = "https://widget-v5.boxnow.bg/popup.html";
        } else if (country === "HR") {
            src = "https://widget-v5.boxnow.hr/popup.html";
        } else if (country === "SI") {
            src = "https://widget-v5.boxnow.si/popup.html";
        } else {
            src = "https://widget-v5.boxnow.gr/popup.html";
        }

        if (partnerId) {
            src += "?partnerId=" + partnerId + "&";
        } else {
            src += "?";
        }

        if (gpsOption === "off") {
            src +=
                "gps=no&zip=" +
                encodeURIComponent(postalCode) +
                "&autoclose=yes&autoselect=no";
        } else {
            src += "gps=yes&autoclose=yes&autoselect=no";
        }

        let iframe = $("<iframe>", {
            id: "box_now_delivery_iframe_popup",
            src: src,
            allow: "geolocation https://*.boxnow.gr https://*.boxnow.cy https://*.boxnow.bg https://*.boxnow.si https://*.boxnow.hr",
            css: {
                position: "fixed",
                top: "50%",
                left: "50%",
                width: "80%",
                height: "80%",
                border: 0,
                borderRadius: "20px",
                transform: "translate(-50%, -50%)",
                zIndex: 9999,
            },
        });
        popupIframe = iframe[0];

        createOverlay();
        $("body").append(iframe);
        observeIframeRemoval(iframe);
    }

    function observeIframeRemoval(iframe) {
        if (!iframe || typeof MutationObserver === "undefined") return;

        // Prevent multiple observers
        if (iframeObserver) {
            iframeObserver.disconnect();
        }
        
        iframeObserver= new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                for (const node of mutation.removedNodes) {
                    if (node === iframe[0]) {
                        // iframe disappeared for ANY reason
                        $("#box_now_delivery_overlay").remove();
                        $(".boxnow-popup").remove();
                        iframeObserver.disconnect();
                        iframeObserver = null;

                        return;
                    }
                }
            }
        });

        iframeObserver.observe(document.body, { childList: true });
    }

    /**
     * Create an iframe for the embedded map.
     */
    function getEmbeddedWidgetUrl() {
        let src;
        let gpsOption = boxNowDeliverySettings.gps_option;
        let partnerId = boxNowDeliverySettings.partnerId;
        let postalCode = $('input[name="billing_postcode"]').val();
        let country = GetUserCountry();

        if (country === "CY") {
            src = "https://widget-v5.boxnow.cy"; // TODO 3.1.1: update when available
        } else if (country === "BG") {
            src = "https://widget-v5.boxnow.bg";
        } else if (country === "HR") {
            src = "https://widget-v5.boxnow.hr";
        } else if (country === "SI") {
            src = "https://widget-v5.boxnow.si";
        } else {
            src = "https://widget-v5.boxnow.gr";
        }

        if (partnerId) {
            src += "?partnerId=" + partnerId + "&";
        } else {
            src += "?";
        }

        if (gpsOption === "off") {
            src += "gps=no&zip=" + encodeURIComponent(postalCode);
        } else {
            src += "gps=yes";
        }

        return src;
    }

    function createEmbeddedIframe(src) {
        return $("<iframe>", {
            src: src,
            "data-box-now-src": src,
            css: {
                width: "100%",
                height: "70%",
                border: 0,
            },
        });
    }

    function sendLockerToServer(lockerId) {
        if (!lockerId || lockerId === lastPersistedLockerId) {
            return;
        }

        lastPersistedLockerId = lockerId;
        $.ajax({
            url: boxNowDeliverySettings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'boxnow_set_locker',
                locker_id: lockerId,
                nonce: boxNowDeliverySettings.nonce
            },
            success: function(response) {
                if ((!response || response.success !== true) && lastPersistedLockerId === lockerId) {
                    lastPersistedLockerId = null;
                }
            },
            error: function(xhr, status, error) {
                if (lastPersistedLockerId === lockerId) {
                    lastPersistedLockerId = null;
                }
            }
        });
    }

    /**
     * Update the locker details container with selected locker data.
     *
     * @param {object} lockerData Locker data object.
     * @param {object} options Rendering and persistence options.
     */
    function updateLockerDetailsContainer(lockerData, options) {
        options = options || {};

        // Check if locker data is not undefined
        if (
            lockerData.boxnowLockerId === undefined ||
            lockerData.boxnowLockerAddressLine1 === undefined ||
            lockerData.boxnowLockerPostalCode === undefined ||
            lockerData.boxnowLockerName === undefined
        ) {
            return;
        }

        // Get the selected locker details
        var locker_id = lockerData.boxnowLockerId;
        var locker_address = lockerData.boxnowLockerAddressLine1;
        var locker_postal_code = lockerData.boxnowLockerPostalCode;
        var locker_name = lockerData.boxnowLockerName;
        // Add more fields as needed

        // CHANGE: Remember previously selected locker id before updating the fields.
        var previous_locker_id = $( '#_boxnow_locker_id' ).val() || '';
        if ( ! previous_locker_id && $( '#box_now_selected_locker_input' ).length ) {
            try {
                var previous_locker_data = JSON.parse( $( '#box_now_selected_locker_input' ).val() );
                previous_locker_id = previous_locker_data && previous_locker_data.boxnowLockerId ? previous_locker_data.boxnowLockerId : '';
            } catch ( e ) {
                previous_locker_id = '';
            }
        }

        if (options.persistToSession) {
            localStorage.setItem("box_now_selected_locker", JSON.stringify(lockerData));
        }

        // Ensure the locker details container is added after the BOX NOW Delivery button
        if ($("#box_now_selected_locker_details").length === 0) {
            $("#box_now_delivery_button").after(
                '<div id="box_now_selected_locker_details" style="display:none;"></div>'
            );
        }

        // Add a hidden input field to store locker_id
        if ($("#_boxnow_locker_id").length === 0) {
            $("<input>")
                .attr({
                    type: "hidden",
                    id: "_boxnow_locker_id",
                    name: "_boxnow_locker_id",
                    value: locker_id,
                })
                .appendTo("#box_now_selected_locker_details");
        } else {
            $("#_boxnow_locker_id").val(locker_id);
        }

        // Update the locker details container
        // Get the language of the webpage.
        // If the language is not defined, default to Greek.
        var language = document.documentElement.lang || "el";

        // Define the content for English.
        var englishContent = `
<div id="locker-info">
  <p class="locker-title"><b>Selected Locker</b></p>
  <p class="locker-detail">${locker_name}</p>
  <p class="locker-detail">${locker_address}</p>
  <p class="locker-detail">${locker_postal_code}</p>
</div>`;

        // Define the content for Greek.
        var greekContent = `
<div id="locker-info">
  <p class="locker-title"><b>Επιλεγμένο Locker</b></p>
  <p class="locker-detail">${locker_name}</p>
  <p class="locker-detail">${locker_address}</p>
  <p class="locker-detail">${locker_postal_code}</p>
</div>`;

        // Choose the correct content based on the language.
        var content = language === "el" ? greekContent : englishContent;

        // Update the locker details container.
        $("#box_now_selected_locker_details").html(content).show();

        // Add a hidden input field to store locker information
        if ($("#box_now_selected_locker_input").length === 0) {
            $("<input>")
                .attr({
                    type: "hidden",
                    id: "box_now_selected_locker_input",
                    name: "box_now_selected_locker",
                    value: JSON.stringify(lockerData),
                })
                .appendTo("#box_now_selected_locker_details");
        } else {
            $("#box_now_selected_locker_input").val(JSON.stringify(lockerData));
        }

        if (options.persistToSession) {
            sendLockerToServer(locker_id);

            // CHANGE: Sync Fluid Checkout validation field immediately so session and step validation do not lag behind the selected locker UI.
            var terminalField = $("#box_now-terminal");
            if (terminalField.length) {
                terminalField.val(JSON.stringify(lockerData));
            }
            // CHANGE: END - Sync Fluid Checkout validation field immediately so session and step validation do not lag behind the selected locker UI.
        }

        toggleExpressCheckoutForBoxNow();

        if (options.closePopup && boxNowDeliverySettings.displayMode === "popup") {
            closeBoxNowPopup();
        }

        // CHANGE: Trigger update checkout only when the selected locker changed.
        if ( String( previous_locker_id ) !== String( locker_id ) ) {
            $( document.body ).trigger( "update_checkout" );
        }
    }

    /**
     * Show the selected locker details from local storage.
     */
    function showSelectedLockerDetailsFromLocalStorage() {
        var lockerData = localStorage.getItem("box_now_selected_locker");

        if (!lockerData) {
            return;
        }

        try {
            updateLockerDetailsContainer(JSON.parse(lockerData), {
                persistToSession: false,
                closePopup: false,
            });
        } catch (e) {
            localStorage.removeItem("box_now_selected_locker");
        }
    }

    /**
     * Toggle the BOX NOW Delivery button or embedded map based on the selected shipping method.
     */
    function toggleBoxNowDelivery() {
        if (boxNowDeliverySettings.displayMode === "popup" || boxNowDeliverySettings.page !== "checkout") {
            toggleBoxNowDeliveryButton();
        } else if (boxNowDeliverySettings.displayMode === "embedded") {
            embedMap();
        }

        toggleExpressCheckoutForBoxNow();
    }

    function isBoxNowDeliverySelected() {
        if (boxNowDeliverySettings.page !== "checkout") {
            return false;
        }

        var selectedRadio = $('input[type="radio"][name^="shipping_method"]:checked');
        if (selectedRadio.length && selectedRadio.val() && selectedRadio.val().indexOf("box_now_delivery") !== -1) {
            return true;
        }

        var selectedHidden = $('input[type="hidden"][name^="shipping_method"]');
        for (var i = 0; i < selectedHidden.length; i++) {
            var hiddenValue = selectedHidden.eq(i).val();
            if (hiddenValue && hiddenValue.indexOf("box_now_delivery") !== -1) {
                return true;
            }
        }

        return false;
    }

    function getSelectedLockerId() {
        var lockerField = $("#_boxnow_locker_id").val();
        if (lockerField) {
            return lockerField;
        }

        var lockerData = localStorage.getItem("box_now_selected_locker");
        if (!lockerData) {
            return "";
        }

        try {
            var parsedLockerData = JSON.parse(lockerData);
            return parsedLockerData && parsedLockerData.boxnowLockerId ? parsedLockerData.boxnowLockerId : "";
        } catch (e) {
            return "";
        }
    }

    function registerStripeExpressCheckoutExtensionData() {
        if (window._bndpStripeExpressExtensionRegistered) {
            return;
        }

        if (!(window.wp && wp.hooks && typeof wp.hooks.addFilter === "function")) {
            return;
        }

        wp.hooks.addFilter(
            "wcstripe.express-checkout.cart-place-order-extension-data",
            "box-now-delivery",
            function (extensionData) {
                var lockerId = getSelectedLockerId();

                if (!lockerId || !isBoxNowDeliverySelected()) {
                    return extensionData;
                }

                return Object.assign({}, extensionData, {
                    "box-now-delivery": Object.assign(
                        {},
                        extensionData["box-now-delivery"] || {},
                        { _boxnow_locker_id: lockerId }
                    )
                });
            }
        );

        window._bndpStripeExpressExtensionRegistered = true;
    }

    var expressCheckoutContainerSelector = [
        "#wc-stripe-express-checkout-element",
        ".wc-stripe-express-checkout-element",
        "#wc-stripe-payment-request-wrapper",
        ".wc-stripe-payment-request-wrapper",
        ".wc-stripe-payment-request-button",
        ".wc-stripe-express-checkout-button",
        ".woocommerce-paypal-payments-ppcp-button",
        ".wc-ppcp-checkout-container",
        ".wc-ppcp-paypal-buttons",
        ".ppc-button-wrapper",
        "#ppc-button",
        ".paypal-button-container",
        ".paypal-buttons",
        ".wc-block-components-express-payment"
    ].join(",");

    function getExpressCheckoutContainers() {
        return $(expressCheckoutContainerSelector).filter(function () {
            return $(this).parents(expressCheckoutContainerSelector).length === 0;
        });
    }

    function escapeHtml(value) {
        return $("<div>").text(value).html();
    }

    function toggleExpressCheckoutForBoxNow() {
        return;
    }

    var expressCheckoutToggleScheduled = false;

    function scheduleExpressCheckoutToggle() {
        if (expressCheckoutToggleScheduled) {
            return;
        }

        expressCheckoutToggleScheduled = true;
        window.requestAnimationFrame(function () {
            expressCheckoutToggleScheduled = false;
            toggleExpressCheckoutForBoxNow();
        });
    }

    function isUserInitiatedChange(event) {
        var originalEvent = event && event.originalEvent;
        return !!originalEvent && originalEvent.isTrusted !== false;
    }

    function clearSelectedLocker() {
        lastPersistedLockerId = null;
        localStorage.removeItem("box_now_selected_locker");
        $("#box_now_selected_locker_details").hide().empty();
        // CHANGE: Clear Fluid Checkout validation field when the selected locker is cleared.
        $("#box_now-terminal").val("");
        // CHANGE: END - Clear Fluid Checkout validation field when the selected locker is cleared.
        removeLockerFromSession();
        toggleExpressCheckoutForBoxNow();
    }

    /**
     * Toggle the BOX NOW Delivery button visibility based on the selected shipping method.
     */
    function toggleBoxNowDeliveryButton() {
        var boxButton = $("#box_now_delivery_button");

        // Set the background color once since it's common for all conditions
        var buttonColor = boxNowDeliverySettings.buttonColor || "#6CD04E";
        boxButton.css("background-color", buttonColor);

        var isSelected = false;
        if(boxNowDeliverySettings.page === "thankyou_page"){
            isSelected = true;
        }else if (boxNowDeliverySettings.page === "checkout"){
            // Radio-based selection
            var radio = $('input[type="radio"][name="shipping_method[0]"]:checked');
            if (radio.length) {
                isSelected = String(radio.val() || "").indexOf("box_now_delivery") !== -1;
            }
            // Hidden input fallback (some themes/flows)
            if (!isSelected) {
                var hiddenVal = $('input[type="hidden"][name="shipping_method[0]"]').val();
                if (hiddenVal) {
                    isSelected = String(hiddenVal).indexOf("box_now_delivery") !== -1;
                }
            }
        }

        if (isSelected) {
            boxButton.show();
        } else {
            boxButton.hide();
        }
    }

    /**
     * Initialize the script.
     */
    function init() {
        addButton();
        toggleBoxNowDelivery();

        // CHANGE: Re-bind the button listener on every init, including when addButton() skips creating a new button, so the handler survives checkout fragment replacements.
        attachButtonClickListener();

        if (isBoxNowDeliverySelected()) {
            showSelectedLockerDetailsFromLocalStorage();
        }
    }

    function scheduleCheckoutUIRefresh() {
        if (checkoutUIRefreshScheduled) {
            return;
        }

        checkoutUIRefreshScheduled = true;
        window.requestAnimationFrame(function () {
            checkoutUIRefreshScheduled = false;
            init();
            toggleExpressCheckoutForBoxNow();
        });
    }

    /**
     * Remove the selected locker details from Session.
     */
    function removeLockerFromSession() {
        try {
            $.ajax({
                url: boxNowDeliverySettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'bndp_clear_boxnow_locker',
                    nonce: boxNowDeliverySettings.nonce
                },
                success: function(response) {
                    toggleExpressCheckoutForBoxNow();
                },
                error: function(xhr, status, error) {
                    console.warn("removeLockerFromSession response error: ", error);
                }
            });

        } catch (e) {
            console.error("removeLockerFromSession error: ", e);
        }
    }

    // Document ready event
    $(document).ready(function () {
        registerStripeExpressCheckoutExtensionData();
        /**
         * Add validation for order placement to ensure locker selection.
         */
        function addOrderValidation() {
            $(document.body).on("click", "#place_order", function (event) {
                if (isBoxNowDeliverySelected() && !getSelectedLockerId()) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    alert(
                        boxNowDeliverySettings.lockerNotSelectedMessage ||
                        "Please select a locker first!"
                    );
                    return false;
                }
            });
        }

        init();
        window.removeEventListener("message", handleBoxNowMessage); // Avoid multiple listeners via AJAX in some themes
        window.addEventListener("message", handleBoxNowMessage, false);

        // Show the selected locker details from localStorage
        showSelectedLockerDetailsFromLocalStorage();
        toggleExpressCheckoutForBoxNow();

        // Call init() function when the shipping method list is updated
        $(document.body)
            .off("updated_checkout.boxNowDelivery")
            .on("updated_checkout.boxNowDelivery", scheduleCheckoutUIRefresh);

        // Call the toggle function when the shipping method changes
        $(document.body).on(
            "change",
            'input[type="radio"][name="shipping_method[0]"]',
            toggleBoxNowDelivery
        );

        // CHANGE: Remove addOrderValidation() function call to replace it with field validation from Fluid Checkout.

        // When shipping country changes clear selected locker from local storage and session
        $(document.body).on("change", "#shipping_country", function (event) {
            if (!isUserInitiatedChange(event)) {
                return;
            }

            clearSelectedLocker();
        });

        // When billing_country country changes and the user selected the option 
        // to ship to same address as billing, then procceed to clear selected 
        // locker from local storage and session
        $(document.body).on("change", "#billing_country", function (event) {
            if (!isUserInitiatedChange(event)) {
                return;
            }

            if (!$('#ship-to-different-address-checkbox').is(":checked")) {
                clearSelectedLocker();
            }
        });

        // When the user toggles the option to ship to different address,
        // procceed to clear selected locker from local storage and session
        $(document.body).on("change", "#ship-to-different-address-checkbox", function (event) {
            if (!isUserInitiatedChange(event)) {
                return;
            }

            clearSelectedLocker();
        });
    });
})(jQuery);
