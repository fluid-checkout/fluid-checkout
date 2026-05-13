(function ($) {
  "use strict";
  /*
   author: sohay@biteship.com
   version: 2.2.12
  */
  $(function () {
    createTracker($);
    loadProvince($);

    setTimeout(function () {
      /* Hide the component states, city, & postcode */
      const prefixs = ["billing", "shipping"];
      prefixs.forEach((prefix) => {
        let hide_component = [
          prefix + "_country_field",
          prefix + "_company_field",
          prefix + "_address_1_field",
          prefix + "_address_2_field",
          prefix + "_state_field",
          prefix + "_city_field",
          prefix + "_postcode_field",
          prefix + "biteship_district",
          prefix + "_biteship_district_info_field",
        ];
        hide_component.forEach((element) => {
          if (window.location.href.toString().includes("edit-address") && !element.includes("country")) {
            $(`#${element}`).hide();
          } else if (!window.location.href.toString().includes("edit-address")) {
            $(`#${element}`).hide();
          }
        });
        $(`#${prefix}_biteship_location`).css({
          "padding-left": "29px",
          background: 'url("' + window.location.protocol + "//" + window.location.host.replace("www.", "") + '/wp-content/plugins/biteship/public/images/pin.svg") no-repeat left',
          "background-size": "13px",
          "background-position": "7px",
          height: "48px",
          "min-height": "45px",
        });
      });
      $("#myModal").hide();
      customShippingText($);
      if (parseInt(phpVars.userSession.clearCache)) {
        clearComponent($, "billing", true);
        clearComponent($, "shipping", true);
        $("[data-title=Shipping]").text("Masukkan alamat lengkap untuk menghitung ongkos kirim");
        console.log("CLEAR CACHE");
      } else {
        if (phpVars.checkoutType === "dropdown" && !location.href.includes("edit-address")) {
          removeState("billing_areas");
          removeState("shipping_areas");
          var billing_areas = localStorage.getItem("billing_areas");
          if (billing_areas == undefined) {
            getAreasV2($, "billing");
          }
          var shipping_areas = localStorage.getItem("shipping_areas");
          if (shipping_areas == undefined) {
            getAreasV2($, "shipping");
          }
        }
        $("#billing_postcode").val(phpVars.userSession.postcode);
        $("body").trigger("update_checkout", { update_shipping_method: true });
      }
    }, 1800);

    modifyComponent($, "billing");
    modifyComponent($, "shipping");
    $("body").append(`
    <div id="myModal">
      <div class="modal-content">
        <div>
          <span style="font-size: 25px;">Pin Alamat .</span>
          <span id="closemyModal" class="close">&times;</span>
        </div>
        <form id="mapSearchForm" style="margin-top: 10px;font-size:13px;">
          <input style="width: 100%" type="text" id="placeSearch" placeholder="Masukkan alamat lengkap yang ada atau pilih pin"> 
        </form>
        <div id="mapCheckout" class="map-order-review"></div>
      </div>
    </div>`);

    if (!phpVars) {
      phpVars = {};
    }
    $("#placeSearch").click(function () {
      if (screen.width >= 500) {
        disableScroll();
      }
    });

    gmapView($);
    var modal = $("#myModal");
    var close = $("#closemyModal");
    if (close.length > 0) {
      close[0].addEventListener("click", function () {
        closeModal(modal);
      });
    }
    $(document).on("click", ".select-location", function () {
      closeModal(modal);
      $(document.body).trigger("update_checkout");
    });

    /* This only for smart search version 0.0.2*/
    smartSearchV2($, phpVars);

    // CHANGE: Reload select2 fields for the smart search after checkout fragments update
    $( document.body ).on( 'updated_checkout', function() {
	smartSearchV2($, phpVars);
    } );

    /* Refresh fee calculation when payment method is changed */
    $("form.checkout").on("change", 'input[name="payment_method"]', function () {
      $(document.body).trigger("update_checkout");
    });

    $("form.checkout").on("change", 'input[name="is_insurance_active"]', function () {
      $(document.body).trigger("update_checkout");
    });

    /* Refresh calculation when pilih Alamat Toko is changed */
    refreshMultiOrigin($, "billing");
    refreshMultiOrigin($, "shipping");
  });
})(jQuery);

function getKeyByValue(object, value) {
  return Object.keys(object).find((key) => object[key].toUpperCase() === value.toUpperCase());
}

function getAreasV2($, prefix) {
  var check = document.getElementById(`${prefix}_biteship_province`);
  if (check !== null) {
    var areaID = $(`#${prefix}_biteship_province`).val();
    if (areaID !== null && areaID.length > 0) {
      var baseURL = phpVars.biteshipBaseUrl || "";
      $(".overlay").css("display", "block");
      $.ajax({
        url: `${baseURL}/v1/woocommerce/maps/administrative_division_levels`,
        data: {
          level: "2",
          area_id: areaID,
        },
        type: "POST",
        headers: {
          authorization: `Bearer ${phpVars.biteshipLicenseKey || ""}`,
        },
        success: function (data) {
          if (!data.success) {
            alert("API Biteship sedang bermasalah.");
          }
          if (data.areas.length > 0) {
            /* render data city, district, zipcode */
            localStorage.setItem(`${prefix}_areas`, JSON.stringify(data.areas));
            setMapAreas($, data.areas, `${prefix}_biteship_city`, "administrative_division_level_2_name");
            setMapAreas($, data.areas, `${prefix}_biteship_district`, "administrative_division_level_3_name");
            setMapAreas($, data.areas, `${prefix}_biteship_zipcode`, "postal_code");
            $(`#${prefix}_state`).val(getStateID(data.areas[0]["administrative_division_level_1_name"])).change();
            getListMultiOrigin($, areaID, prefix);
            setCountryState($, "billing");
            setCountryState($, "shipping");
            clearMap();
            $("body").trigger("update_checkout");
            $(".overlay").css("display", "none");
          }
        },
        error: function (_) {
          console.log("Gagal mencari lokasi berdasarkan provinsi, kota & kecamatan.");
          $(".overlay").css("display", "none");
        },
      });
    }
  }
}

function setMapAreas($, areas, component, division) {
  let choose = "";
  if (component.includes("city")) {
    choose = "Pilih Kota";
  } else if (component.includes("district")) {
    choose = "Pilih Kecamatan";
  } else if (component.includes("zipcode")) {
    choose = "Pilih Kode Pos";
  }
  $(`#${component}`).empty().prop("disabled", false);
  let unique = [];
  areas.map(function (val) {
    if (!unique.includes(val[division])) {
      if (unique.length == 0) {
        $(`#${component}`).append('<option value="" selected>' + choose + "</option>");
      }
      unique.push(val[division]);
      $(`#${component}`).append('<option value="' + val[division] + '">' + val[division] + "</option>");
    }
  });
}

function setPosition(marker, infoWindow, map, location, $) {
  marker.setPosition(location);
  marker.setMap(map);
  infoWindow.setContent("<p class='select-location'>Pilih titik ini</p>");
  infoWindow.setPosition(location);
  infoWindow.open(map, marker);

  var latLong = `${location.lat()},${location.lng()}`;
  $("#position").val(latLong);
  $("#billing_biteship_location_coordinate").val(latLong);
  $("#shipping_biteship_location_coordinate").val(latLong);
  getAddress($, latLong);
  // CHANGE: Use captured event
  $( document.body ).on("input", "#billing_biteship_location_coordinate", function () {
    $("body").trigger("update_checkout", { update_shipping_method: true });
  });
  // CHANGE: Use captured event
  $(document.body).on("input", "#shipping_biteship_location_coordinate", function () {
    $("body").trigger("update_checkout", { update_shipping_method: true });
  });
}

function getAddress($, latLong) {
  var enc = function (s, b) {
    var w = "";
    for (var i = 0; i < s.length; i++) {
      w += String.fromCharCode(s.charCodeAt(i) ^ b);
    }
    return unescape(w);
  };
  var apiKey = enc(phpVars.apiKey, window.location.host.replace("www.", "").length) || "";
  $.ajax({
    url: `https://maps.googleapis.com/maps/api/geocode/json?latlng=${latLong}&sensor=true&key=${apiKey}`,
    type: "GET",
    success: function (response) {
      switch (response.status) {
        case "OK":
          var address = response.results[0].formatted_address;
          $("#billing_biteship_location").val(address).addClass("valid");
          $("#shipping_biteship_location").val(address).addClass("valid");
          if (address.length >= 110) {
            $(`#billing_biteship_location`).css({
              "padding-left": "29px",
              background: 'url("' + window.location.protocol + "//" + window.location.host.replace("www.", "") + '/wp-content/plugins/biteship/public/images/pin.svg") no-repeat left',
              "background-size": "13px",
              "background-position": "7px",
              height: "66px",
            });
            $(`#shipping_biteship_location`).css({
              "padding-left": "29px",
              background: 'url("' + window.location.protocol + "//" + window.location.host.replace("www.", "") + '/wp-content/plugins/biteship/public/images/pin.svg") no-repeat left',
              "background-size": "13px",
              "background-position": "7px",
              height: "66px",
            });
          } else {
            $(`#billing_biteship_location`).css({
              "padding-left": "29px",
              background: 'url("' + window.location.protocol + "//" + window.location.host.replace("www.", "") + '/wp-content/plugins/biteship/public/images/pin.svg") no-repeat left',
              "background-size": "13px",
              "background-position": "7px",
              height: "48px",
            });
            $(`#shipping_biteship_location`).css({
              "padding-left": "29px",
              background: 'url("' + window.location.protocol + "//" + window.location.host.replace("www.", "") + '/wp-content/plugins/biteship/public/images/pin.svg") no-repeat left',
              "background-size": "13px",
              "background-position": "7px",
              height: "48px",
            });
          }
          saveState("billing_biteship_location", address);
          saveState("shipping_biteship_location", address);
          saveState("billing_biteship_location_coordinate", latLong);
          saveState("shipping_biteship_location_coordinate", latLong);
          break;
        default:
          alert(response.status);
      }
    },
  });
}

function customShippingText($) {
  if ($("[data-title=Shipping]").html() !== undefined) {
    if ($("[data-title=Shipping]").html().includes("options") || $("[data-title=Shipping]").html().includes("invalid")) {
      $("[data-title=Shipping]").text("Masukkan alamat lengkap untuk menghitung ongkos kirim.");
    }
  }
}

function clearComponent($, prefix, isActive) {
  $(`#${prefix}_city`).val("");
  $(`#${prefix}_postcode`).val("");
  $(`#${prefix}_state`).prop("selected", false);
  $(`#${prefix}_biteship_new_district`).val("");
  $(`#${prefix}_biteship_multi_origins`).val("");
  if (isActive) {
    $(`#${prefix}_biteship_location`).val("");
  }
  if (isActive) {
    getAreasV2($, prefix);
  }
  customShippingText($);
}

function getProvinces() {
  return {
    Bali: "IDNP1",
    "Bangka Belitung": "IDNP2",
    Banten: "IDNP3",
    Bengkulu: "IDNP4",
    "DI Yogyakarta": "IDNP5",
    "DKI Jakarta": "IDNP6",
    Gorontalo: "IDNP7",
    Jambi: "IDNP8",
    "Jawa Barat": "IDNP9",
    "Jawa Tengah": "IDNP10",
    "Jawa Timur": "IDNP11",
    "Kalimantan Barat": "IDNP12",
    "Kalimantan Selatan": "IDNP13",
    "Kalimantan Tengah": "IDNP14",
    "Kalimantan Timur": "IDNP15",
    "Kalimantan Utara": "IDNP16",
    "Kepulauan Riau": "IDNP17",
    Lampung: "IDNP18",
    Maluku: "IDNP19",
    "Maluku Utara": "IDNP20",
    "Nanggroe Aceh Darussalam (NAD)": "IDNP21",
    "Nusa Tenggara Barat (NTB)": "IDNP22",
    "Nusa Tenggara Timur (NTT)": "IDNP23",
    Papua: "IDNP24",
    "Papua Barat": "IDNP25",
    Riau: "IDNP26",
    "Sulawesi Barat": "IDNP27",
    "Sulawesi Selatan": "IDNP28",
    "Sulawesi Tengah": "IDNP29",
    "Sulawesi Tenggara": "IDNP30",
    "Sulawesi Utara": "IDNP31",
    "Sumatera Barat": "IDNP32",
    "Sumatera Selatan": "IDNP33",
    "Sumatera Utara": "IDNP34",
  };
}
function getStateID(key) {
  var mapState = getProvinces();
  return mapState[key];
}

function setZipcode($, district, prefix) {
  if (localStorage.getItem(`${prefix}_areas`) !== null) {
    let no = 1;
    var listZipcode = [];
    $(`#${prefix}_biteship_zipcode`).empty();
    $(`#${prefix}_biteship_multi_origins`).val("");
    var areas = JSON.parse(localStorage.getItem(`${prefix}_areas`));
    areas.map(function (val) {
      if (district == val.administrative_division_level_3_name) {
        if (!listZipcode.includes(val.postal_code)) {
          if (no === 1) {
            $(`#${prefix}_biteship_zipcode`).append('<option value="" selected>Pilih Kode Pos</option>');
          }
          $(`#${prefix}_biteship_zipcode`).append('<option value="' + val.postal_code + '">' + val.postal_code + "</option>");
          no += 1;
          listZipcode.push(val.postal_code);
        }
      }
    });
  }
}

function setDistrict($, city, prefix) {
  if (localStorage.getItem(`${prefix}_areas`) !== null) {
    let no = 0;
    var areas = JSON.parse(localStorage.getItem(`${prefix}_areas`));
    var listDistricts = [];
    $(`#${prefix}_biteship_district`).empty();
    $(`#${prefix}_biteship_zipcode`).empty();
    areas.map(function (val) {
      if (city == val.administrative_division_level_2_name) {
        if (!listDistricts.includes(val.administrative_division_level_3_name)) {
          if (no == 0) {
            $(`#${prefix}_biteship_district`).append('<option value="" selected>Pilih Kecamatan</option>');
          }
          $(`#${prefix}_biteship_district`).append('<option value="' + val.administrative_division_level_3_name + '">' + val.administrative_division_level_3_name + "</option>");
          listDistricts.push(val.administrative_division_level_3_name);
        }
      }
    });
  }
}

function closeModal(modal) {
  modal.css("display", "none");
}

function modifyComponent($, prefix) {
  $("#billing_phone_field>label").html('No Tlp <abbr class="required" title="required">*</abbr>');
  $("#billing_email_field>label").html('Alamat Email <abbr class="required" title="required">*</abbr>');

  // CHANGE: Use captured event
  $( document.body ).on( 'change', `#${prefix}_biteship_address`, function () {
    var address = $(`#${prefix}_biteship_address`).val();
    $(`#${prefix}_address_1`).val(address);
    saveState(`${prefix}_address_1`, address);
  });
  // CHANGE: Use captured event
  $( document.body ).on( 'click', `#${prefix}_biteship_location`, function () {
    $("#myModal").show();
    $("#placeSearch").val("");
  });
  // CHANGE: Use captured event
  $( document.body ).on( 'change', `#${prefix}_biteship_province`, function () {
    clearComponent($, prefix, false);
    getAreasV2($, prefix);
    saveState(`${prefix}_biteship_province`, $(`#${prefix}_biteship_province`).val());
    $("body").trigger("update_checkout");
  });
  // CHANGE: Use captured event
  $( document.body ).on( 'change', `#${prefix}_biteship_city`, function () {
    clearComponent($, prefix, false);
    var city = $(`#${prefix}_biteship_city`).val();
    $(`#${prefix}_city`).val(city);
    setDistrict($, city, prefix);
    saveState(`${prefix}_biteship_city`, $(`#${prefix}_biteship_city`).val());
    $("body").trigger("update_checkout");
  });
  // CHANGE: Use captured event
  $( document.body ).on( 'change', `#${prefix}_biteship_district`, function () {
    var district = $(`#${prefix}_biteship_district`).val();
    saveState(`${prefix}_biteship_district`, $(`#${prefix}_biteship_district`).val());
    setZipcode($, district, prefix);
  });
  // CHANGE: Use captured event
  $( document.body ).on( 'change', `#${prefix}_biteship_zipcode`, function () {
    /* only used zipcode or longlat for courier */
    var zipcode = $(`#${prefix}_biteship_zipcode`).val();
    $(`#${prefix}_postcode`).val(zipcode).change();
    $("body").trigger("update_checkout");
    saveState(`${prefix}_biteship_zipcode`, $(`#${prefix}_biteship_zipcode`).val());
  });

  // CHANGE: Use captured event
  /* smart search */
  $( document.body ).on( 'change', `#${prefix}_biteship_new_district`, function () {	
    var district = $(`#${prefix}_biteship_new_district`).val();
    if (district.length === 0) {
      $(`#${prefix}_biteship_district_info`).val(""); /* clear data */
      $(`#${prefix}_biteship_district_info_field`).hide();
      $("body").trigger("update_checkout", { update_shipping_method: true });
      clearComponent($, "billing", true);
      clearComponent($, "shipping", true);
    }
  });
}

function saveState(key, value) {
  localStorage.setItem(key, value);
}

function loadState(key) {
  return localStorage.getItem(key);
}

function removeState(key) {
  return localStorage.removeItem(key);
}

function preventDefault(e) {
  e.preventDefault();
}

function preventDefaultForScrollKeys(e) {
  var keys = { 37: 1, 38: 1, 39: 1, 40: 1 };
  if (keys[e.keyCode]) {
    preventDefault(e);
    return false;
  }
}
var supportsPassive = false;
try {
  window.addEventListener(
    "test",
    null,
    Object.defineProperty({}, "passive", {
      get: function () {
        supportsPassive = true;
      },
    }),
  );
} catch (e) {}

var wheelOpt = supportsPassive ? { passive: false } : false;
var wheelEvent = "onwheel" in document.createElement("div") ? "wheel" : "mousewheel";
function disableScroll() {
  window.addEventListener("DOMMouseScroll", preventDefault, false);
  window.addEventListener(wheelEvent, preventDefault, wheelOpt);
  window.addEventListener("touchmove", preventDefault, wheelOpt);
  window.addEventListener("keydown", preventDefaultForScrollKeys, false);
}
function enableScroll() {
  window.removeEventListener("DOMMouseScroll", preventDefault, false);
  window.removeEventListener(wheelEvent, preventDefault, wheelOpt);
  window.removeEventListener("touchmove", preventDefault, wheelOpt);
  window.removeEventListener("keydown", preventDefaultForScrollKeys, false);
}

function loadProvince($) {
  let no = 0;
  var mapState = getProvinces();
  const prefixs = ["billing", "shipping"];
  prefixs.forEach((prefix) => {
    if (document.getElementById(`${prefix}_biteship_province`) !== null) {
      if (!document.getElementById(`${prefix}_biteship_province`).innerHTML.includes("IDNP1")) {
        for (const [key, value] of Object.entries(mapState)) {
          if (no == 0) {
            $(`#${prefix}_biteship_province`).empty();
            $(`#${prefix}_biteship_province`).append('<option value="" selected>Pilih Provinsi</option>');
            $(`#${prefix}_biteship_city`).empty();
            $(`#${prefix}_biteship_city`).append('<option value="" selected>Pilih Kota</option>');
            $(`#${prefix}_biteship_district`).empty();
            $(`#${prefix}_biteship_district`).append('<option value="" selected>Pilih Kecamatan</option>');
            $(`#${prefix}_biteship_zipcode`).empty();
            $(`#${prefix}_biteship_zipcode`).append('<option value="" selected>Pilih Kode Pos</option>');
            $(`#${prefix}_biteship_multi_origins`).empty();
            $(`#${prefix}_biteship_multi_origins`).append('<option value="" selected>Pilih province dulu</option>');
          }
          $(`#${prefix}_biteship_province`).append('<option value="' + value + '">' + key + "</option>");
          no += 1;
        }
      }
    }
  });
}

function createTracker($) {
  if (phpVars.trackingPageUrl.length > 0 && parseInt(phpVars.trackingPageIsactive)) {
    if ($("body").html().includes('data-title="Actions"')) {
      $("tr.woocommerce-orders-table__row").each(function (i) {
        var btnView = $(this).find("td.woocommerce-orders-table__cell.woocommerce-orders-table__cell-order-actions a");
        var component = $(this).find("td.woocommerce-orders-table__cell.woocommerce-orders-table__cell-order-actions");

        var orderId = new URL(btnView.attr("href")).searchParams.get("view-order");
        if (orderId === null) {
          const temp = btnView.attr("href").split("/");
          if (temp.length > 3) {
            orderId = temp[temp.length - 2];
          }
        }
        if (orderId !== undefined) {
          var data = {
            action: "get_order_detail",
            orderId: orderId,
            dataType: "json",
          };
          $.post("/wp-admin/admin-ajax.php", data, function (response) {
            if (response.success) {
              if (response.data.waybill_id.length > 0) {
                component.append(
                  `<a href="${phpVars.trackingPageUrl}?waybill_id=${response.data.waybill_id}" target="_blank" class="woocommerce-button wp-element-button button view">TRACK</a>`,
                  [btnView],
                );
              }
            } else {
              console.log("ERROR!!!", orderId);
            }
          });
        }
      });
    }
  }
}

function smartSearchV2($, phpVars) {
  if (phpVars !== {} && phpVars.checkoutType === "smartsearch") {
    try {
      $(".init-select2 select").select2();
      $(".select2-ajax select").each(function () {
        var baseURL = phpVars.biteshipBaseUrl || "";
        $(this).select2({
          ajax: {
            url: `${baseURL}/v1/woocommerce/maps/areas`,
            dataType: "json",
            headers: {
              authorization: `Bearer ${phpVars.biteshipLicenseKey || ""}`,
            },
            type: "POST",
            delay: 250,
            data: function (params) {
              return {
                countries: "ID",
                input: params.term,
                type: "single",
              };
            },
            processResults: function (data, _) {
              var result = [];
              if (data.areas.length > 0) {
                result = data.areas.map(function (val) {
                  return {
                    id: val.id,
                    text: `${val.postal_code}, ${val.administrative_division_level_3_name}, ${val.administrative_division_level_2_name}, ${val.administrative_division_level_1_name}`,
                    name: val.name,
                    provice: val.administrative_division_level_1_name,
                    district: val.administrative_division_level_3_name,
                    city: val.administrative_division_level_2_name,
                    state: val.administrative_division_level_1_name,
                    zipcode: val.postal_code,
                  };
                });
              }
              saveState("smartsearch_area", JSON.stringify(result));
              return {
                results: result,
              };
            },
            cache: true,
          },
          minimumInputLength: 3,
          language: {
            inputTooShort: function (args) {
              return "Input minimal 3 karakter";
            },
          },
          placeholder: $(this).attr("placeholder"),
        });
      });

      const addressType = ["billing", "shipping"];
      addressType.forEach((prefix) => {
		// CHANGE: Use captured event
	   $( document.body ).on( 'change', `#${prefix}_biteship_new_district`, function () {
          setTimeout(function () {
            var loadState = localStorage.getItem("smartsearch_area");
            if (loadState !== undefined) {
              JSON.parse(loadState).map(function (val) {
                if (val.text === document.getElementById(`select2-${prefix}_biteship_new_district-container`).title) {
                  if (val.id.split("IDNC").length > 1) {
                    getListMultiOrigin($, val.id.split("IDNC")[0], prefix);
                  }
                  $(`#${prefix}_state`).val(val.state).change();
                  $(`#${prefix}_city`).val(val.city);
                  $(`#${prefix}_postcode`).val(val.zipcode);
                  $(`#${prefix}_biteship_district`).val(val.district);
                  $("body").trigger("update_checkout");
                  clearMap();
                  customShippingText($);
                }
              });
            }
          }, 1200);
        });
      });
    } catch (error) {
      console.log(error);
    }
  }
}

function getListMultiOrigin($, areaID, prefix) {
  if (parseInt(phpVars.multipleOriginsIsactive)) {
    var data = {
      action: "biteship_public_get_multiple_origin",
      areaID: areaID,
      security: phpVars.biteshipNonce,
      dataType: "json",
    };
    $.post(woocommerce_params.ajax_url, data, function (response) {
      if (response.success) {
        setMultiOrigin($, response.data, prefix);
      } else {
        window.alert("ERROR!!!");
      }
    });
  } else {
    console.log("multiple origin not active");
  }
}

function setMultiOrigin($, data, prefix) {
  $(`#${prefix}_biteship_multi_origins`).empty();
  if (data.multiple_addresses.length === 0) {
    $(`#${prefix}_biteship_multi_origins`).removeAttr("disabled");
    data.default_address.map(function (val) {
      $(`#${prefix}_biteship_multi_origins`).append('<option value="' + val.id + '">' + val.shopname + " - " + val.address + "</option>");
    });
    return;
  }
  $(`#${prefix}_biteship_multi_origins`).removeAttr("disabled");
  $(`#${prefix}_biteship_multi_origins`).append('<option value="none" selected>Pilih Toko</option>');
  data.multiple_addresses.map(function (val) {
    $(`#${prefix}_biteship_multi_origins`).append('<option value="' + val.id + '">' + val.shopname + " - " + val.address + "</option>");
  });
}

function gmapView($) {
  var marker = null;
  var infoWindow = null;
  var enc = function (s, b) {
    var w = "";
    for (var i = 0; i < s.length; i++) {
      w += String.fromCharCode(s.charCodeAt(i) ^ b);
    }
    return unescape(w);
  };
  var apiKey = enc(phpVars.apiKey, window.location.host.replace("www.", "").length) || "";
  $.getScript(`https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places`, function () {
    var myLatLng = { lat: -6.1753871, lng: 106.8249641 };
    if (phpVars.userSession.coordinate.length > 5) {
      var userCoordinate = phpVars.userSession.coordinate.split(",");
      myLatLng.lat = parseFloat(userCoordinate[0]);
      myLatLng.lng = parseFloat(userCoordinate[1]);
    }

    var map = new google.maps.Map(document.getElementById("mapCheckout"), {
      zoom: 15,
      center: myLatLng,
      disableDefaultUI: true,
      fullscreenControl: true,
    });
    marker = new google.maps.Marker({
      position: null,
      map: null,
      title: "Location",
    });

    infoWindow = new google.maps.InfoWindow({});
    map.addListener("click", function (mapsMouseEvent) {
      var selectedPosition = mapsMouseEvent.latLng;
      setPosition(marker, infoWindow, map, selectedPosition, $);
    });

    /* Add event when user click searchbox autocomplete */
    var input = document.getElementById("placeSearch");
    var autocomplete = new google.maps.places.Autocomplete(input);
    autocomplete.setComponentRestrictions({ country: ["id"] });
    autocomplete.addListener("place_changed", function () {
      var place = autocomplete.getPlace();
      if (!place.geometry) {
        /*
              User entered the name of a Place that was not suggested and
              pressed the Enter key, or the Place Details request failed.
            */
        return;
      }
      myLatLng = {
        lat: place.geometry.location.lat(),
        lng: place.geometry.location.lng(),
      };
      map.setCenter(place.geometry.location);
      marker.setPosition(place.geometry.location);
      marker.setMap(map);
      setPosition(marker, infoWindow, map, place.geometry.location, $);
      $("#position-input").val(place.geometry.location.lat() + "," + place.geometry.location.lng());
      if (screen.width >= 500) {
        enableScroll();
      }
    });
  });
}

function refreshMultiOrigin($, prefix) {
	// CHANGE: Use captured event
    $( document.body ).on( 'change', `#${prefix}_biteship_multi_origins`, function () {
		if (this.value === "none") {
			alert("Pastikan anda sudah pilih toko");
			return;
		}
		if (phpVars.biteshipLicenseKeyType !== "woocommerceFree" || phpVars.biteshipLicenseKeyType !== "woocommerceEssentials") {
			clearMap();
		}
		$("body").trigger("update_checkout", { update_shipping_method: true });
		gmapView($);
	});
}

function clearMap() {
  if (document.getElementById(`position`) !== null) {
    document.getElementById(`position`).value = "";
  }
  if (document.getElementById(`billing_biteship_location`) !== null) {
    document.getElementById(`billing_biteship_location`).value = "";
  }
  if (document.getElementById(`shipping_biteship_location`) !== null) {
    document.getElementById(`shipping_biteship_location`).value = "";
  }
  if (document.getElementById(`billing_biteship_location_coordinate`) !== null) {
    document.getElementById(`billing_biteship_location_coordinate`).value = "";
  }
  if (document.getElementById(`shipping_biteship_location_coordinate`) !== null) {
    document.getElementById(`shipping_biteship_location_coordinate`).value = "";
  }
}

/*setCountryState - for textbox not selected box */
function setCountryState($, prefix) {
  if (!location.href.includes("edit-address")) {
    var fullPrefix = "#" + prefix + `_state`;
    var checkBillState = document.getElementById(prefix + `_state`);
    if (checkBillState !== null) {
      var mapState = getProvinces();
      if ($(fullPrefix).val().includes("IDN")) {
        for (const [key, value] of Object.entries(mapState)) {
          if (value === $(fullPrefix).val()) {
            document.getElementById("billing_state").value = key;
            console.log("yohohoohoh");
            break;
          }
        }
      }
    }
  }
}
