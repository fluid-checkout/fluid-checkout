(function ($) {
  var $lastFocusedEl;
  var $captureUrl;
  var $cancelUrl;
  var $selectedMethod;
  var $cancelledModal = false;
  var $currentOrderData;

  // managed by checkout-app through postMessage
  var isClosePrevented = false;
  var placeOrderForm = $("form.checkout");
  if (!placeOrderForm || placeOrderForm.length === 0) {
    placeOrderForm = $("#order_review");
  }

  if (isRvvupMethodEnabled("APPLE_PAY")) {
    handleApplePayStartup();
  }
  placeOrderForm.on("click", "#place_order", function (event) {
    $selectedMethod = $("#payment .wc_payment_method:has(input:checked)");

    // Only trigger logic for Rvvup payment methods
    if (!$selectedMethod.is('[class*="payment_method_rvvup_gateway_"]')) return;

    if (
      $selectedMethod.is('[class*="payment_method_rvvup_gateway_APPLE_PAY"]') ||
      $selectedMethod.is('[class*="payment_method_rvvup_gateway_KLARNA"]') ||
      $selectedMethod.is('[class*="payment_method_rvvup_gateway_ZOPA_RETAIL_FINANCE"]')
    ) {
      // Use default behavior which is to use the `redirect` url received back form the server.
      return;
    }

    $lastFocusedEl = this;

    event.preventDefault();
    var isInlineCardPayment =
      isRvvupMethodEnabled("CARD") &&
      window.rvvup_st &&
      $selectedMethod.is('[class*="payment_method_rvvup_gateway_CARD"]') &&
      rvvup_parameters.settings.card.flow === "INLINE";

    if (isInlineCardPayment) {
      button = document.getElementById("tp_place_order");
      if (!button || button.disabled) {
        submit_error(rvvup_parameters.i18n.generic, $("form.checkout"));
        $(document.body).trigger("update_checkout");
        return;
      }
    }
    var unblockFormOnComplete = !isInlineCardPayment;
    var requestOptions = getRequestOptions(unblockFormOnComplete);
    if (requestOptions.checkoutForm) {
      blockElement(requestOptions.checkoutForm);
    }

    doCheckout(requestOptions)
      .then((data) => {
        $currentOrderData = data;

        try {
          if (isInlineCardPayment) {
            var isInvalidCardData =
              !data.paymentActions.authorization.token ||
              !data.paymentActions.capture.redirect_url ||
              !data.paymentActions.confirm_authorization.url;
            if (isInvalidCardData) {
              throw rvvup_parameters.i18n.generic;
            }
            $captureUrl = data.paymentActions.capture.redirect_url;
            window.rvvup_st.updateJWT(data.paymentActions.authorization.token);
            $("#tp_place_order").trigger("click");
          } else {
            if (!data.paymentActions.authorization.redirect_url) {
              throw rvvup_parameters.i18n.generic;
            }
            $cancelUrl = data.paymentActions.cancel.redirect_url;
            showModal(data.paymentActions.authorization.redirect_url, $selectedMethod);
          }
        } catch (e) {
          submit_error(e, requestOptions.checkoutForm);
        }
      })
      .catch((data) => {
        if (requestOptions.checkoutForm) {
          submit_error(
            data.messages ? data.messages : wc_checkout_params.i18n_checkout_error,
            requestOptions.checkoutForm
          );
        }
      });
  });

  function cancelModal() {
    if (!$cancelUrl) return;
    if (isClosePrevented) return;
    if ($cancelledModal) return;
    $cancelledModal = true;
    showModal($cancelUrl, $selectedMethod);
  }

  function getRequestOptions(unblockFormOnComplete = true) {
    var checkoutForm = $("form.checkout");
    if (!checkoutForm || checkoutForm.length === 0) {
      return {
        data: $("form#order_review").serialize() + "&order_id=" + rvvup_parameters.orderId,
        url: rvvup_parameters.endpoints.payOrder,
      };
    }
    return {
      checkoutForm: checkoutForm,
      data: checkoutForm.serialize(),
      url: wc_checkout_params.checkout_url,
      unblockFormOnComplete: unblockFormOnComplete,
    };
  }

  function doCheckout(requestOptions) {
    return new Promise((resolve, reject) => {
      $.ajax({
        type: "POST",
        url: requestOptions.url,
        data: requestOptions.data,
        dataType: "json",
        success: function (e) {
          detachUnloadEventsOnSubmit();
          try {
            if (
              "success" === e.result &&
              (requestOptions.checkoutForm &&
                requestOptions.checkoutForm.triggerHandler("checkout_place_order_success", e)) !== false
            ) {
              return resolve(e);
            } else if ("failure" === e.result) {
              throw "Result failure";
            } else {
              throw "Invalid response";
            }
          } catch (error) {
            if (e.reload) {
              return void window.location.reload();
            }
            e.refresh && g(document.body).trigger("update_checkout");
            reject(e);
          }
        },
        error: function (e, t, o) {
          detachUnloadEventsOnSubmit();
          reject({ messages: o });
        },
        complete: function () {
          if (requestOptions.unblockFormOnComplete) {
            unblockElement(requestOptions.checkoutForm);
          }
        },
      });
    });
  }

  function blockElement(element, message = null) {
    if (element.is("form")) {
      element.addClass("processing");
    }
    element.block({
      message: message,
      overlayCSS: {
        background: "#fff",
        opacity: 0.6,
      },
    });
  }

  function unblockElement(element) {
    if (element.is("form") && element.hasClass("processing")) {
      element.removeClass("processing");
    }
    element.unblock();
  }

  function detachUnloadEventsOnSubmit() {
    $(window).off("beforeunload", this.handleUnloadEvent);
  }

  function submit_error(e, checkoutForm) {
    if (!checkoutForm) {
      return;
    }
    if (e.indexOf("woocommerce-error") < 0) {
      e = '<div class="woocommerce-error">' + e + "</div>";
    }
    $(".woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message").remove(),
      checkoutForm.prepend('<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + e + "</div>"),
      checkoutForm.removeClass("processing").unblock(),
      checkoutForm.find(".input-text, select, input:checkbox").trigger("validate").trigger("blur"),
      scroll_to_notices(),
      $(document.body).trigger("checkout_error", [e]);
  }

  function scroll_to_notices() {
    var e = $(".woocommerce-NoticeGroup-updateOrderReview, .woocommerce-NoticeGroup-checkout");
    e.length || (e = $("form.checkout"));
    $.scroll_to_notices(e);
  }

  function showModal(redirect, selectedMethod) {
    var options = {
      method: selectedMethod,
      modal: $(".rvvup-modal"),
      redirect: redirect,
    };

    handleModal(options);
    window.addEventListener("focus", windowFocussed, true);
    window.addEventListener("blur", windowFocussed, true);
  }

  function resizeModalIframe(width, height) {
    const windowHeight = window.innerHeight;
    const windowWidth = window.innerWidth;

    const finalWidth = width === "max" ? windowWidth - 40 : width > windowWidth ? windowWidth : width;
    const finalHeight = height === "max" ? windowHeight - 40 : height > windowHeight ? windowHeight : height;
    jQuery(".rvvup-modal.rvvup-modal-show .rvvup-dialog").animate({ width: finalWidth, height: finalHeight }, 400);
  }

  function resizeInfoIframe(width, height, url) {
    const importantWidth = width && width !== "max" ? width + "px!important" : "auto";
    const importantHeight = height && height !== "max" ? height + "px!important" : "auto";

    // Common `.css` jQuery do not accept `!important`.
    jQuery(`.rvvup-iframe[src="${url}"]`).css("cssText", `width: ${importantWidth}; height: ${importantHeight};`);
  }

  function handlePostMessage(message) {
    switch (message.data.type) {
      case "rvvup-payment-modal|prevent-close": {
        isClosePrevented = message.data.preventClose;
        break;
      }
      case "rvvup-payment-modal|close": {
        isClosePrevented = false;
        cancelModal();
        break;
      }
      case "rvvup-payment-modal|resize": {
        resizeModalIframe(message.data.width, message.data.height);
        break;
      }
      case "rvvup-info-widget|resize":
        resizeInfoIframe(message.data.width, message.data.height, message.data.url);
        break;
    }
  }

  window.addEventListener("message", handlePostMessage, true);

  function windowFocussed() {
    var $modal = $(".rvvup-modal-show .rvvup-iframe");

    if (!$(document.activeElement).hasClass("rvvup-iframe")) {
      $modal.focus();
    }
  }

  function handleModal(options) {
    if (!options.modal.length) return;
    var modalShowClass = "rvvup-modal-show";
    var overflowClass = "rvvup-modal-is-visible";

    // Remove classes by default
    options.modal.removeClass(modalShowClass);
    $(document.body).removeClass(overflowClass);

    // Get required elements
    var modalLabel = options.method ? options.method.find("label").text().trim() : "";
    var $modalDialog = options.modal.find(".rvvup-dialog");
    var $modalIframe = options.modal.find("iframe");

    // clear width and height
    if (options.clearSize) $modalDialog.css({ width: "", height: "" });

    // Update elements
    $modalDialog.attr("aria-label", modalLabel);
    $modalIframe.attr("src", options.redirect);

    // Exit if no redirect - no need to show modal or fix body overflow
    if (!options.redirect) return;
    options.modal.addClass(modalShowClass);

    // Prevent scroll on the body
    $(document.body).addClass(overflowClass);
  }

  function handleApplePayStartup() {
    try {
      if (!window.ApplePaySession) {
        hideApplePay();
        // When updated the payment methods are re-built, we need to re-validate.
        $("body").on("updated_checkout", hideApplePay);
      }
    } catch (e) {
      console.error(e);
    }

    function hideApplePay() {
      $("#payment .payment_method_rvvup_gateway_APPLE_PAY").hide();

      if ($("#payment input[name='payment_method']:checked").attr("value") === "rvvup_gateway_APPLE_PAY") {
        var $notApplePay = $(
          "#payment input[name='payment_method']:not(#payment_method_rvvup_gateway_APPLE_PAY):first"
        );

        $notApplePay.attr("checked", true);
        $notApplePay.trigger("change");
      }
    }
  }

  /**
   * PayPal Integration
   * - rendering PayPal buttons instead of Place order button section when PayPal is selected initially
   * - replacing PayPal buttons with Place order button section when payment method is changed
   */
  $("body").on("updated_checkout", handlePaypalPaymentMethod);
  $("body").on("payment_method_selected", handlePaypalPaymentMethod);
  $(document).ready(function () {
    var isCheckoutForm = $("form.checkout");

    //only load if we are not on checkout page
    if (!isCheckoutForm || isCheckoutForm.length === 0) {
      handleCardPaymentMethod();
    }
  });

  /**
   * Card Integration
   * - rendering card form on the checkout page
   */
  $("body").on("updated_checkout", handleCardPaymentMethod);

  function handlePaymentMethodSelectedPaypal() {
    var selectedMethod = $("#payment input[name='payment_method']:checked").attr("value");

    /* If Express Payment session, no need to handle any more button rendering. */
    if (isExpressPaymentSession()) {
      return;
    }

    if (window.rvvup_paypal && isRvvupMethodEnabled("PAYPAL")) {
      if (selectedMethod === "rvvup_gateway_PAYPAL") {
        renderPaypalButtons();
        rvvup_toggleCheckoutPayPalMessage(true);
      } else {
        hidePaypalButtons();
        rvvup_toggleCheckoutPayPalMessage(false);
      }
    }
  }

  function handlePaypalPaymentMethod() {
    if (document.readyState === "complete") {
      handlePaymentMethodSelectedPaypal();
    } else {
      /** We are adding timeout here, because of paypal sdk errors **/
      setTimeout(() => {
        handlePaymentMethodSelectedPaypal();
      }, 500);
    }
  }

  function handleCardPaymentMethod() {
    if (isExpressPaymentSession()) return;

    // CHANGE: Bail if payment methods cannot be updated
    console.log('window.can_update_payment_methods', window.can_update_payment_methods);
    if (undefined !== window.can_update_payment_methods && true !== window.can_update_payment_methods) { return; }

    if (isRvvupMethodEnabled("CARD") && rvvup_parameters.settings.card.flow === "INLINE") {
      loadCardForm();
    }
  }

  function isRvvupMethodEnabled(method) {
    return rvvup_parameters.methods.some(function (enabledMethod) {
      return enabledMethod.name.toLowerCase() === method.toLowerCase();
    });
  }

  /**
   * Validate whether we have an express payment session.
   *
   * @returns {boolean}
   */
  function isExpressPaymentSession() {
    return rvvup_parameters.express.isExpress.toString() === "1" && rvvup_parameters.express.id.toString() !== "0";
  }

  var paypalButtonsContainerId = "rvvup-paypal-button";

  function renderPaypalButtonsContainer() {
    var placeOrderButton = $("#place_order");
    var container = document.createElement("div");
    container.id = paypalButtonsContainerId;
    container.style.marginTop = placeOrderButton.css("margin-top");
    $(container).insertAfter(placeOrderButton);
  }

  /**
   * Container for the buttons cannot be rendered immediately as woocommerce's JavaScript
   * is updating the DOM structure - rendering when this function is run for the first time
   */
  function renderPaypalButtons() {
    if (!document.getElementById(paypalButtonsContainerId)) {
      renderPaypalButtonsContainer();
      var errorShown = false;

      rvvup_paypal
        .Buttons({
          style: rvvup_getCheckoutButtonStyle(),
          createOrder: function () {
            $lastFocusedEl = this;
            $selectedMethod = $("#payment .wc_payment_method:has(input:checked)");
            var requestOptions = getRequestOptions();
            if (requestOptions.checkoutForm) {
              blockElement(requestOptions.checkoutForm);
            }

            return doCheckout(requestOptions)
              .then(function (data) {
                $captureUrl = data.paymentActions.capture.redirect_url;
                $cancelUrl = data.paymentActions.cancel.redirect_url;

                return data.paymentActions.authorization.token;
              })
              .catch((data) => {
                if (requestOptions.checkoutForm) {
                  submit_error(
                    data.messages ? data.messages : wc_checkout_params.i18n_checkout_error,
                    requestOptions.checkoutForm
                  );
                  errorShown = true;
                }
              });
          },
          onApprove: function () {
            return new Promise((resolve, reject) => {
              resolve($captureUrl);
            }).then((url) => {
              showModal(url, $selectedMethod);
            });
          },
          onCancel: function () {
            showModal($cancelUrl, $selectedMethod);
          },
          onError: function () {
            var requestOptions = getRequestOptions();
            if (requestOptions.checkoutForm && !errorShown) {
              submit_error(wc_checkout_params.i18n_checkout_error, requestOptions.checkoutForm);
            }
          },
        })
        .render("#" + paypalButtonsContainerId);
    }

    $("#place_order").addClass("rvvup-hide-element").hide();
    $("#" + paypalButtonsContainerId).show();
  }

  function hidePaypalButtons() {
    $("#place_order").removeClass("rvvup-hide-element").show();
    $("#" + paypalButtonsContainerId).hide();
  }

  function appendWooNoticeError(msg) {
    var wrapper = $(".woocommerce-notices-wrapper");
    if (wrapper.length === 0) {
      return;
    }
    var woocommerceError = wrapper.children("ul.woocommerce-error");
    if (woocommerceError.length === 0) {
      woocommerceError = $("<ul>", { class: "woocommerce-error" });
      woocommerceError.appendTo(wrapper);
    }
    $("<li>").html(msg).appendTo(woocommerceError);
  }

  function clearWooNoticeError() {
    var wrapper = $(".woocommerce-notices-wrapper");
    if (wrapper.length === 0) {
      return;
    }
    var woocommerceError = wrapper.children("ul.woocommerce-error");
    if (woocommerceError.length === 0) {
      return;
    }
    woocommerceError.remove();
  }

  $(document).ready(function () {
    var rvvupModal = $("<div></div>")
      .attr("id", "rvvup-modal")
      .addClass("rvvup-modal")
      .html(
        '<div class="rvvup-dialog slide-in-bottom" role="dialog" aria-modal="true">' +
          '        <iframe class="rvvup-iframe" allow="clipboard-read; clipboard-write; payment" allowpaymentrequest="true" src=""></iframe>' +
          "      </div>"
      )
      .on("click", function (e) {
        var targetIsModal = $(e.target).hasClass(".rvvup-dialog");
        var targetWithinModal = $(e.target).closest(".rvvup-dialog").length;

        if (targetIsModal || targetWithinModal) return;

        if ($(e.target).hasClass("rvvup-modal") && $(e.target).hasClass("rvvup-modal-show")) {
          cancelModal();
        }
      });
    $("body").append(rvvupModal);
    var variationField = document.querySelector(".cart [name='variation_id']");
    var quantityField = document.querySelector(".cart [name='quantity']");
    var addCartButton = document.querySelector(".cart [name='add-to-cart']");

    function isGroupedProduct() {
      return $(".cart").hasClass("grouped_form");
    }

    function validateProductPageCheckout(shouldDisplayError = true, clearInitWooNoticeError = true) {
      if (clearInitWooNoticeError === true) {
        clearWooNoticeError();
      }

      if (isGroupedProduct()) {
        return true;
      }

      var hasErrors = false;
      var errorMessage = "";
      if (variationField !== null && (variationField.value.length === 0 || variationField.value === "0")) {
        errorMessage = rvvup_parameters.i18n.no_variation_selected;

        hasErrors = true;
      }

      if (quantityField === null || quantityField.value < 1) {
        errorMessage = rvvup_parameters.i18n.invalid_quantity;

        hasErrors = true;
      }

      if (shouldDisplayError === true && hasErrors === true && errorMessage.length > 0) {
        appendWooNoticeError(errorMessage);
      }

      return hasErrors === false;
    }

    function getProductsList() {
      var productId = null;
      var quantity = null;
      if (!isGroupedProduct()) {
        productId = addCartButton ? addCartButton.value : null;
        quantity = quantityField ? quantityField.value : 1;
        var variationId = variationField ? variationField.value : 0;
        var variation = {};
        /* Gather all selected attributes for product variations */
        $(".cart")
          .find(".variations select")
          .each(function () {
            var attributeValue = $(this).val() || "";

            if (attributeValue.length > 0) {
              variation[$(this).data("attribute_name") || $(this).attr("name")] = attributeValue;
            }
          });
        return [{ id: productId, quantity: quantity, variation_id: variationId, variation: variation }];
      }
      var products = [];

      var quantityElements = document.querySelectorAll(".cart [name^='quantity[']");
      for (var i = 0; i < quantityElements.length; i++) {
        quantity = quantityElements[i].value;
        if (quantity > 0) {
          productId = quantityElements[i].attributes["name"].value.replace("quantity[", "").replace("]", "");
          if (productId > 0) {
            products.push({ id: productId, quantity: quantity, variation_id: 0, variation: {} });
          }
        }
      }
      return products;
    }

    $("rvvup-express-payments").each(function (i) {
      $(this).empty();
      /* if the quantity field is not present on the page we don't handle the product type
               so do not generate the buttons when we do not have a quantity field.
            */
      var supportedProductPage = isGroupedProduct() || quantityField !== null;
      if (window.rvvup_paypal && isRvvupMethodEnabled("PAYPAL") && supportedProductPage && rvvup_isPdpButtonEnabled()) {
        var id = "rvvup-paypal-container-" + i;
        $("<div>", {
          id: id,
        }).appendTo(this);
        rvvup_paypal
          .Buttons({
            style: rvvup_getPdpButtonStyle(),
            onInit: function (data, actions) {
              if (!validateProductPageCheckout(false, false)) {
                actions.disable();
              }

              jQuery("form.cart")
                .find(":input")
                .change(function () {
                  if (validateProductPageCheckout(false)) {
                    actions.enable();

                    return;
                  }

                  actions.disable();
                });
            },
            onClick: function (data, actions) {
              return validateProductPageCheckout();
            },
            createOrder: function () {
              var requestOptions = {
                data: JSON.stringify({
                  payment_method: "rvvup_gateway_PAYPAL",
                  products: getProductsList(),
                }),
                url: rvvup_parameters.endpoints.express,
              };

              return doCheckout(requestOptions)
                .then(function (data) {
                  $captureUrl = data.paymentActions.capture.redirect_url;
                  $cancelUrl = data.paymentActions.cancel.redirect_url;

                  rvvup_parameters.express.id = data.id;
                  rvvup_parameters.express.cancel_url = data.paymentActions.cancel.redirect_url;

                  return data.paymentActions.authorization.token;
                })
                .catch((data) => {
                  clearWooNoticeError();
                  data.messages
                    ? appendWooNoticeError(data.messages)
                    : appendWooNoticeError(rvvup_parameters.i18n.generic);
                });
            },
            onApprove: function (data, actions) {
              return actions.order.get().then(function (orderData) {
                /* Prepare request data with billing & shipping addresses */
                var requestData = {
                  id: rvvup_parameters.express.id,
                  cancel_url: rvvup_parameters.express.cancel_url,
                  billing_address: {
                    first_name: orderData.payer.name.given_name,
                    last_name: orderData.payer.name.surname,
                    email_address: orderData.payer.email_address,
                    phone_number: "",
                    company: "",
                    address_line_1: "",
                    address_line_2: "",
                    city: "",
                    state: "",
                    post_code: "",
                    country_code: "",
                  },
                  shipping_address: {
                    first_name: "",
                    last_name: "",
                    email_address: "",
                    phone_number: "",
                    company: "",
                    address_line_1: "",
                    address_line_2: "",
                    city: "",
                    state: "",
                    post_code: "",
                    country_code: "",
                  },
                };

                if (orderData.payer.hasOwnProperty("address")) {
                  requestData.billing_address.address_line_1 = orderData.payer.address.hasOwnProperty("address_line_1")
                    ? orderData.payer.address.address_line_1
                    : "";
                  requestData.billing_address.address_line_2 = orderData.payer.address.hasOwnProperty("address_line_2")
                    ? orderData.payer.address.address_line_2
                    : "";
                  requestData.billing_address.city = orderData.payer.address.hasOwnProperty("admin_area_2")
                    ? orderData.payer.address.admin_area_2
                    : "";
                  requestData.billing_address.state = orderData.payer.address.hasOwnProperty("admin_area_1")
                    ? orderData.payer.address.admin_area_1
                    : "";
                  requestData.billing_address.post_code = orderData.payer.address.hasOwnProperty("postal_code")
                    ? orderData.payer.address.postal_code
                    : "";
                  requestData.billing_address.country_code = orderData.payer.address.hasOwnProperty("country_code")
                    ? orderData.payer.address.country_code
                    : "";
                }

                if (orderData.payer.hasOwnProperty("phone") && orderData.payer.phone.hasOwnProperty("phone_number")) {
                  requestData.billing_address.phone_number = orderData.payer.phone.phone_number.hasOwnProperty(
                    "national_number"
                  )
                    ? orderData.payer.phone.phone_number.national_number
                    : "";
                }

                if (orderData.purchase_units.length > 0 && orderData.purchase_units[0].hasOwnProperty("shipping")) {
                  var shippingFullName =
                    orderData.purchase_units[0].shipping.hasOwnProperty("name") &&
                    orderData.purchase_units[0].shipping.name.hasOwnProperty("full_name")
                      ? orderData.purchase_units[0].shipping.name.full_name
                      : "";
                  var shippingFullNameArray = shippingFullName.split(" ");

                  requestData.shipping_address.first_name = shippingFullNameArray.shift();

                  if (shippingFullNameArray.length > 0) {
                    requestData.shipping_address.last_name = shippingFullNameArray.join(" ");
                  }

                  if (orderData.purchase_units[0].shipping.hasOwnProperty("address")) {
                    requestData.shipping_address.address_line_1 =
                      orderData.purchase_units[0].shipping.address.hasOwnProperty("address_line_1")
                        ? orderData.purchase_units[0].shipping.address.address_line_1
                        : "";
                    requestData.shipping_address.address_line_2 =
                      orderData.purchase_units[0].shipping.address.hasOwnProperty("address_line_2")
                        ? orderData.purchase_units[0].shipping.address.address_line_2
                        : "";
                    requestData.shipping_address.city = orderData.purchase_units[0].shipping.address.hasOwnProperty(
                      "admin_area_2"
                    )
                      ? orderData.purchase_units[0].shipping.address.admin_area_2
                      : "";
                    requestData.shipping_address.state = orderData.purchase_units[0].shipping.address.hasOwnProperty(
                      "admin_area_1"
                    )
                      ? orderData.purchase_units[0].shipping.address.admin_area_1
                      : "";
                    requestData.shipping_address.post_code =
                      orderData.purchase_units[0].shipping.address.hasOwnProperty("postal_code")
                        ? orderData.purchase_units[0].shipping.address.postal_code
                        : "";
                    requestData.shipping_address.country_code =
                      orderData.purchase_units[0].shipping.address.hasOwnProperty("country_code")
                        ? orderData.purchase_units[0].shipping.address.country_code
                        : "";
                  }
                }

                var requestOptions = {
                  data: JSON.stringify(requestData),
                  url: rvvup_parameters.endpoints.expressUpdate,
                };

                return doCheckout(requestOptions)
                  .then(function () {
                    return new Promise((resolve, reject) => {
                      resolve(rvvup_parameters.urls.checkout_url);
                    }).then((url) => {
                      location.href = url;
                    });
                  })
                  .catch((data) => {
                    clearWooNoticeError();
                    data.messages
                      ? appendWooNoticeError(data.messages)
                      : appendWooNoticeError(rvvup_parameters.i18n.generic);
                  });
              });
            },
            onCancel: function () {
              var requestOptions = {
                data: JSON.stringify({
                  id: rvvup_parameters.express.id,
                }),
                url: rvvup_parameters.endpoints.expressDelete,
              };
              doCheckout(requestOptions)
                .then(function () {
                  return showModal($cancelUrl);
                })
                .catch((data) => {
                  clearWooNoticeError();
                  data.messages
                    ? appendWooNoticeError(data.messages)
                    : appendWooNoticeError(rvvup_parameters.i18n.generic);
                });
            },
            onError: function (e) {
              clearWooNoticeError();
              appendWooNoticeError(rvvup_parameters.i18n.generic);
            },
          })
          .render("#" + id);
      }
    });
  });
  $(document).on("click", "#rvvup-express-payments-cancellation-link", function (e) {
    e.preventDefault();
    $selectedMethod = $("#payment .wc_payment_method:has(input:checked)");

    var requestOptions = {
      data: JSON.stringify({
        id: rvvup_parameters.express.id,
      }),
      url: rvvup_parameters.endpoints.expressDelete,
    };
    doCheckout(requestOptions)
      .then(function () {
        return showModal(
          rvvup_parameters.express.cancel_url,
          $selectedMethod.length > 0 && $selectedMethod.is('[class*="payment_method_rvvup_gateway_"]')
            ? $selectedMethod
            : null
        );
      })
      .catch((data) => {
        clearWooNoticeError();
        data.messages ? appendWooNoticeError(data.messages) : appendWooNoticeError(rvvup_parameters.i18n.generic);
      });
  });

  function loadCardForm() {
    var checkoutFormElement = $("form.checkout");
    var formIdToUse = "order_review";
    if (checkoutFormElement && checkoutFormElement.length > 0) {
      formIdToUse = "tp-form";
      //if checkout form has id
      if (checkoutFormElement.attr("id")) {
        formIdToUse = checkoutFormElement.attr("id");
      } else {
        checkoutFormElement.attr("id", formIdToUse);
      }
    }
    showRvvupLoader($("#rvvup-card-loading-wrapper"), "rvvup-card-form-loader");
    var button = $("#tp_place_order");
    if (!button || button.length <= 0) {
      return;
    }
    window.rvvup_st = SecureTrading({
      jwt: rvvup_parameters.settings.card.initializationToken,
      livestatus: rvvup_parameters.settings.card.liveStatus,
      deferInit: true,
      submitOnSuccess: false,
      panIcon: true,
      stopSubmitFormOnEnter: true,
      formId: formIdToUse,
      buttonId: "tp_place_order",
      errorCallback: function () {
        scroll_to_notices();
        unblockElement($("form.checkout"));
      },
      submitCallback: function (data) {
        var submitData = {
          auth: data.jwt,
        };
        if (data.threedresponse) {
          submitData["threeD"] = data.threedresponse;
        }
        confirmCardAuthorization(submitData);
      },
      translations: {
        "Card number": rvvup_parameters?.settings?.card?.form?.translation?.label?.cardNumber || "Card Number",
        "Expiration date": rvvup_parameters?.settings?.card?.form?.translation?.label?.expiryDate || "Expiration Date",
        "Security code": rvvup_parameters?.settings?.card?.form?.translation?.label?.securityCode || "Security Code",
        Pay: rvvup_parameters?.settings?.card?.form?.translation?.button?.pay || "Pay",
        Processing: rvvup_parameters?.settings?.card?.form?.translation?.button?.processing || "Processing",
        "Field is required":
          rvvup_parameters?.settings?.card?.form?.translation?.error?.fieldRequired || "Field is required",
        "Value is too short":
          rvvup_parameters?.settings?.card?.form?.translation?.error?.valueTooShort || "Value is too short",
        "Value mismatch pattern":
          rvvup_parameters?.settings?.card?.form?.translation?.error?.valueMismatch || "Value is invalid",
      },
      styles: {
        "background-color-input": "#FFFFFF",
        "border-color-input": "#EBEBF2",
        "border-radius-input": "8px",
        "border-size-input": "1px",
        "color-input": "#050505",

        "border-color-input-error": "#ff4545",
        "color-label": "#050505",
        "position-left-label": "0.5rem",

        "font-size-label": "1.2rem",
        "font-size-message": "1rem",
        "space-outset-message": "0rem 0px 0px 0.5rem",
      },
    });
    window.rvvup_st.Components();
    setTimeout(function () {
      hideRvvupLoader("rvvup-card-form-loader", function () {
        $("#tp-form-wrapper").slideDown();
      });
    }, 500);
  }

  function confirmCardAuthorization(submitData, remainingRetries = 5) {
    $.ajax({
      type: "POST",
      url: $currentOrderData.paymentActions.confirm_authorization.url,
      data: JSON.stringify(submitData),
      contentType: "application/json",
      dataType: "json",
      success: function () {
        detachUnloadEventsOnSubmit();
        showModal($captureUrl, $selectedMethod);
      },
      error: function (xhr) {
        var code = xhr.responseJSON.errorCode || null;
        var message = xhr.responseJSON.errorMessage || rvvup_parameters.i18n.generic;
        var canRetry = xhr.status === 404 && code === "card_authorization_not_found";
        if (canRetry && remainingRetries > 0) {
          blockElement($("form.checkout"));
          setTimeout(function () {
            confirmCardAuthorization(submitData, remainingRetries - 1);
          }, 2000);
          return;
        }
        detachUnloadEventsOnSubmit();

        if (code !== "card_restartable_error" && code !== "card_recoverable_error") {
          $(document.body).trigger("update_checkout");
        }
        submit_error(message, $("form.checkout"));
      },
    });
  }

  function showRvvupLoader(prependTo, id) {
    if ($("#" + id).length > 0) {
      return;
    }
    var rvvupLoader = $("<div></div>")
      .attr("id", id)
      .addClass("rvvup-loader")
      .html("<span></span><span></span><span></span>");
    prependTo.prepend(rvvupLoader);
  }

  function hideRvvupLoader(id, callback) {
    $("#" + id).fadeOut(500, function () {
      $(this).remove();
      callback();
    });
  }
})(jQuery);
