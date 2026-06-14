(function ($) {
  /**
   * Add the Box Now Delivery button or embedded map.
   */
  function addButton() {
    if (
      $("#box_now_delivery_button").length === 0 &&
      boxNowDeliverySettings.displayMode === "popup"
    ) {
      var buttonText = boxNowDeliverySettings.buttonText || "Pick a locker";

      $('label[for="shipping_method_0_box_now_delivery"]').after(
        '<button type="button" id="box_now_delivery_button" style="display:none;">' +
          buttonText +
          "</button>"
      );

      attachButtonClickListener();
    } else if (boxNowDeliverySettings.displayMode === "embedded") {
      $('label[for="shipping_method_0_box_now_delivery"]').after(
        '<div id="box_now_delivery_embedded_map" style="display:none;"></div>'
      );
      embedMap();
    }
    applyButtonStyles();
  }

  /**
   * Apply the custom styles for the Box Now Delivery button.
   */
  function applyButtonStyles() {
    var buttonColor = boxNowDeliverySettings.buttonColor || "#6CD04E ";

    var styleBlock = `
      <style id="box-now-delivery-button-styles">
        #box_now_delivery_button {
          background-color: ${buttonColor} !important;
          color: #fff !important;
        }
      </style>
    `;

    $("head").append(styleBlock);
  }

  /**
   * Attach click event listener to the Box Now Delivery button.
   */
  function attachButtonClickListener() {
	// CHANGE: Add event delegation.
	$( document ).on( "click", "#box_now_delivery_button", function ( event ) {
	  event.preventDefault();
	  createPopupMap();
	});
  }
  function GetUserCountry() {
    // Get the selected country from the billing_country select input
    let selectedCountry;

    // Modified if clause that mitigates for shipping, billing address and cases where only one service country is selected.
    if ($("#ship-to-different-address-checkbox").is(":checked")) {
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
    var iframe = $("#box_now_delivery_embedded_map iframe");

    if (iframe.length === 0) {
      iframe = createEmbeddedIframe();

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

      $("#box_now_delivery_embedded_map")
        .css({
          position: "relative",
          width: "100%",
          height: "80vh", // Set the height to 100%
          overflow: "auto",
        })
        .append(iframe)
        .append(lockerInfoContainer.append(lockerDetailsContainer));

      // Add the load event listener to the iframe
      iframe.on("load", function () {
        // Add the event listener for the embedded map
        window.addEventListener("message", function (event) {
          if (typeof event.data.boxnowClose !== "undefined") {
            // Handle the close event
          } else {
            updateLockerDetailsContainer(event.data);
          }
        });
      });
    }

    var selected = $(
      'input[name^="shipping_method"]:checked, input[name^="shipping_method"][type="hidden"]'
    );

    if (selected.length && selected.val().includes("box_now_delivery")) {
      $("#box_now_delivery_embedded_map").show();
    } else {
      $("#box_now_delivery_embedded_map").hide();
    }
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
      $("#box_now_delivery_overlay").remove();
      $("iframe[src^='https://widget-v5.boxnow.gr/popup.html']").remove();
      $("iframe[src^='https://widget-v5.boxnow.cy/popup.html']").remove();
      $("iframe[src^='https://widget-v5.boxnow.bg/popup.html']").remove();
      $("iframe[src^='https://widget-v5.boxnow.hr/popup.html']").remove();
    });

    $("body").append(overlay);
  }

  /**
   * Create an iframe for the popup map.
   */
  function createPopupMap() {
    let gpsOption = boxNowDeliverySettings.gps_option;
    let partnerId = boxNowDeliverySettings.partnerId;
    let postalCode = $('input[name="billing_postcode"]').val();
    let country = GetUserCountry();
    console.log(country);

    if (country === "CY") {
      src = "https://widget-v5.boxnow.cy/popup.html";
    } else if (country === "BG") {
      src = "https://widget-v5.boxnow.bg/popup.html";
    } else if (country === "HR") {
      src = "https://widget-v5.boxnow.hr/popup.html";
    } else {
      src = "https://widget-v5.boxnow.gr/popup.html";
    }

    partnerId ? (src += "?partnerId=" + partnerId + "&") : "?";

    if (gpsOption === "off") {
      src +=
        "gps=no&zip=" +
        encodeURIComponent(postalCode) +
        "&autoclose=yes&autoselect=no";
    } else {
      src += "gps=yes&autoclose=yes&autoselect=no";
    }

    let iframe = $("<iframe>", {
      src: src,
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

    // Add an event listener for the 'message' event
    window.addEventListener("message", function (event) {
      if (
        event.data === "closeIframe" ||
        event.data.boxnowClose !== undefined
      ) {
        $("#box_now_delivery_overlay").remove(); // Add this line
        iframe.remove();
      } else {
        updateLockerDetailsContainer(event.data);
      }
    });

    createOverlay();
    $("body").append(iframe);
  }

  /**
   * Create an iframe for the embedded map.
   */
  function createEmbeddedIframe() {
    let gpsOption = boxNowDeliverySettings.gps_option;
    let partnerId = boxNowDeliverySettings.partnerId;
    let postalCode = $('input[name="billing_postcode"]').val();
    let country = GetUserCountry();

    if (country === "CY") {
      src = "https://widget-v5.boxnow.cy";
    } else if (country === "BG") {
      src = "https://widget-v5.boxnow.bg";
    } else if (country === "HR") {
      src = "https://widget-v5.boxnow.hr";
    } else {
      src = "https://widget-v5.boxnow.gr";
    }

    partnerId ? (src += "?partnerId=" + partnerId + "&") : "?";

    if (gpsOption === "off") {
      src += "gps=no&zip=" + encodeURIComponent(postalCode);
    } else {
      src += "gps=yes";
    }

    return $("<iframe>", {
      src: src,
      css: {
        width: "100%",
        height: "70%",
        border: 0,
      },
    });
  }

  // Add the event listener
  window.addEventListener("message", function (event) {
    if (typeof event.data.boxnowClose !== "undefined") {
      // Handle the close event
      if (boxNowDeliverySettings.displayMode === "popup") {
        $(".boxnow-popup").remove();
      }
    } else {
      updateLockerDetailsContainer(event.data);
      showSelectedLockerDetailsFromLocalStorage();
    }
  });

  /**
   * Update the locker details container with selected locker data.
   *
   * @param {object} lockerData Locker data object.
   */
  function updateLockerDetailsContainer(lockerData) {
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

    localStorage.setItem("box_now_selected_locker", JSON.stringify(lockerData));

    // Ensure the locker details container is added after the Box Now Delivery button
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
<div style="font-family: Verdana , Arial, sans-serif;font-weight:300;margin-top: -7px;">
  <p style="margin: 1px 0px; color: #61bb46;font-weight: 400;height: 25px;"><b>Selected Locker</b></p>
  <p style="margin: 1px 0px; font-size: 13px;line-height:20px;height: 20px;">${locker_name}</p>
  <p style="margin: 1px 0px; font-size: 13px;line-height:20px;height: 20px;">${locker_address}</p>
  <p style="margin: 1px 0px; font-size: 13px;line-height:20px;height: 20px;">${locker_postal_code}</p>
</div>`;

    // Define the content for Greek.
    var greekContent = `
<div style="font-family: Verdana , Arial, sans-serif;font-weight:300;margin-top: -7px;">
  <p style="margin: 1px 0px; color: #61bb46;font-weight: 400;height: 25px;"><b>Επιλεγμένο locker</b></p>
  <p style="margin: 1px 0px; font-size: 13px;line-height:20px;height: 20px;">${locker_name}</p>
  <p style="margin: 1px 0px; font-size: 13px;line-height:20px;height: 20px;">${locker_address}</p>
  <p style="margin: 1px 0px; font-size: 13px;line-height:20px;height: 20px;">${locker_postal_code}</p>
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

    if (boxNowDeliverySettings.displayMode === "popup") {
      $("#box_now_delivery_overlay").remove();
      $("iframe[src^='https://widget-v5.boxnow.gr/popup.html']").remove();
      $("iframe[src^='https://widget-v5.boxnow.cy/popup.html']").remove();
      $("iframe[src^='https://widget-v5.boxnow.bg/popup.html']").remove();
      $("iframe[src^='https://widget-v5.boxnow.hr/popup.html']").remove();
    }

	// CHANGE: Trigger update checkout event.
	$( document.body ).trigger( "update_checkout" );
  }

  /**
   * Show the selected locker details from local storage.
   */
  function showSelectedLockerDetailsFromLocalStorage() {
    var lockerData = localStorage.getItem("box_now_selected_locker");

    if (lockerData) {
      updateLockerDetailsContainer(JSON.parse(lockerData));
    }
  }

  /**
   * Toggle the Box Now Delivery button or embedded map based on the selected shipping method.
   */
  function toggleBoxNowDelivery() {
    if (boxNowDeliverySettings.displayMode === "popup") {
      toggleBoxNowDeliveryButton();
    } else if (boxNowDeliverySettings.displayMode === "embedded") {
      embedMap();
    }
  }

  /**
   * Toggle the Box Now Delivery button visibility based on the selected shipping method.
   */
  function toggleBoxNowDeliveryButton() {
    var boxButton = $("#box_now_delivery_button");

    // Set the background color once since it's common for all conditions
    boxButton.css("background-color", boxNowDeliverySettings.buttonColor);

    if ($("#shipping_method_0_box_now_delivery").is(":checked")) {
      boxButton.show();
    } else if ($("shipping_method_[0]").is(":checked")) {
      boxButton.hide();
    } else if (
      $('input[type="hidden"][name="shipping_method[0]"]').val() ===
      "box_now_delivery"
    ) {
      boxButton.show();
    }
  }

  /**
   * Initialize the script.
   */
  function init() {
    addButton();
    toggleBoxNowDelivery();
	// CHANGE: Add event listener to the button skipping the checks from addButton().
	attachButtonClickListener();

	// CHANGE: Remove clearSelectedLockerDetails() function call.

    if ($("#shipping_method_0_box_now_delivery").is(":checked")) {
      showSelectedLockerDetailsFromLocalStorage();
    }
  }

  /**
   * Remove the selected locker details from local storage and hide the locker details container
   */
  function clearSelectedLockerDetails() {
    localStorage.removeItem("box_now_selected_locker");
    $("#box_now_selected_locker_details").hide().empty();
  }

  // Document ready event
  $(document).ready(function () {
    /**
     * Add validation for order placement to ensure locker selection.
     */
    function addOrderValidation() {
      $(document.body).on("click", "#place_order", function (event) {
        var lockerData = localStorage.getItem("box_now_selected_locker");

        if (
          !lockerData &&
          ($('input[type="radio"][name="shipping_method[0]"]:checked').val() ===
            "box_now_delivery" ||
            $('input[type="hidden"][name="shipping_method[0]"]').val() ===
              "box_now_delivery")
        ) {
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

    // Show the selected locker details from localStorage
    showSelectedLockerDetailsFromLocalStorage();

    // Call init() function when the shipping method list is updated
    $(document.body).on("updated_checkout", function () {
      init();
    });

    // Call the toggle function when the shipping method changes
    $(document.body).on(
      "change",
      'input[type="radio"][name="shipping_method[0]"]',
      toggleBoxNowDelivery
    );

	// CHANGE: Remove addOrderValidation() function call to replace it with field validation from Fluid Checkout.

    $("body").on("change", "#billing_country", function () {
      clearSelectedLockerDetails();
    });
  });
})(jQuery);
