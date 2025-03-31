function decodeHtml(html) {
    var txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
}

function check_lenght(nip_value) {
    nip_value = nip_value.replace(/[^0-9\.]+/g, "");
    if (nip_value.length == 10) {
        try {
            return true;
        } catch (e) {
            return false;
        }
    } else {
        return false;
    }
}
function nip_type(){
    let customNipField = jQuery('[data-gus="numer_nip"]');

    if (customNipField.length > 0) {
        return customNipField.attr('id');
    } else if (gus_data.nip_type == 'wlasne') {
        return gus_data.wlasny_id;
    } else {
        return gus_data.nip_type;
    }
}


jQuery(document).ready(function($) {
    // CHANGE: Execute the code when the 'updated_checkout' event is triggered.
    $( document.body ).on( 'updated_checkout', function() {
        if ( $('.get_gus').length === 0 ) {
            // Change: Remove loading animation element since it's replaced with FC validation.
            $('#' + nip_type()).after('<div class="get_gus"><a class="open_gus" onClick="getgus();">' + gus_data.pobierz_dane_nip + '</a> </div><div id="response"></div>');
        }

        if (jQuery('#billing_country').val() !== 'PL') {
            jQuery('.get_gus').hide();
        }
    });
});

// CHANGE: Add event delegation.
jQuery( document ).on( 'change', '#billing_country', function () {
    if (this.value !== 'PL') {
        jQuery('.get_gus').hide();
    } else {
        jQuery('.get_gus').show();
    }
});

// CHANGE: Remove VAT length check since it's replaced with FC validation.

function show_nip_alert() {
    alert(gus_data.wprowadz_poprawny_nip);
    jQuery('.open_gus').html(gus_data.pobierz_dane_nip);
    jQuery('.ladowanie_danych').hide();
}

function getgus() {
    nip = jQuery('#' + nip_type()).val().replace(/[^0-9\.]+/g, "");
    var data = {
        'action': 'get_gusdata',
        "numernip": nip,
        'security': gus_data.security,
    };
    if (check_lenght(nip)) {
        // Change: Remove button label change and loading animation since it's replaced with FC validation.
        jQuery.ajax({
            url: gus_data.ajax_url,
            type: 'POST',
            data: data
        }).done(function (response) {
            if( response == ''){
                alert( 'Serwer GUS nie odpowiada, prosimy spróbować ponownie później' );
                // Change: Remove button label change and loading animation since it's replaced with FC validation.
                return;
            }
            var resp = JSON.parse(response);
            if( resp.type == 'error' ){
                alert( resp.message );
                // Change: Remove button label change and loading animation since it's replaced with FC validation.
                return;
            }
            try {
                fillFields(resp)
                sendNipAjax(nip);
                jQuery(document.body).trigger('update_checkout');
            } catch (e) {
                show_nip_alert();
            }
        });
    } else {
        show_nip_alert();
    }
}

function sendNipAjax(nipValue) {
    if (nipValue) {
        jQuery.ajax({
            url: gus_data.ajax_url,
            type: 'POST',
            data: {
                action: 'save_nip_to_session',
                gus_nip_value: nipValue
            },
            success: function(response) {
            //    console.log(response);
            },
            error: function(response) {
             //   console.log(response);
            }
        });
    }
}

function fillFields(resp) {
    let fieldsMapping = {
        'regon': 'Regon',
        'nazwa_firmy': 'Nazwa',
        'ulica': 'Ulica',
        'miasto': 'Miejscowosc',
        'kod_pocztowy': 'KodPocztowy',
        'budynek': 'NrNieruchomosci',
        'lokal': 'NrLokalu',
        'full_ulica': null,
        'full_numer': null
    };

    function updateBillingField(field, value) {
        field.val(value);

        // Ręczne uruchomienie eventów 'input' i 'change'
        var inputEvent = new Event('input', {
            bubbles: true,
            cancelable: true,
        });
        field[0].dispatchEvent(inputEvent);

        var changeEvent = new Event('change', {
            bubbles: true,
            cancelable: true,
        });
        field[0].dispatchEvent(changeEvent);

        // Ręczne uruchomienie eventów 'focus' i 'blur'
        field[0].focus();
        field[0].blur();
    }

    jQuery.each(fieldsMapping, function(gusKey, respKey) {
        if (respKey && resp[respKey]) {
            let value = decodeHtml(resp[respKey]);
            let field = jQuery('[data-gus="' + gusKey + '"]');
            if (field.length) {
                updateBillingField(field, value);

            }
        }
    });

    // Obsługa pełnej ulicy
    if (jQuery('[data-gus="full_ulica"]').length > 0) {
        let ulica = decodeHtml(resp['Ulica']).replace('ul. ', '');
        let budynek = decodeHtml(resp['NrNieruchomosci']);
        let lokal = decodeHtml(resp['NrLokalu']);
        if (lokal) {
            lokal = '/' + lokal;
        }
        let fullUlicaField = jQuery('[data-gus="full_ulica"]');
        updateBillingField(fullUlicaField, ulica + ' ' + budynek + lokal);
    }

    // Obsługa pełnego numeru
    if (jQuery('[data-gus="full_numer"]').length > 0) {
        let budynek = decodeHtml(resp['NrNieruchomosci']);
        let lokal = decodeHtml(resp['NrLokalu']);
        if (lokal) {
            lokal = '/' + lokal;
        }
        let fullNumerField = jQuery('[data-gus="full_numer"]');
        updateBillingField(fullNumerField, budynek + lokal);
    }

    // Change: Remove button label change and loading animation since it's replaced with FC validation.
}


