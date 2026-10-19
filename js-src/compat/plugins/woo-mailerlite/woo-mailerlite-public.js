jQuery(document).ready(function(a) {
	// CHANGE: Remove allowedInputs (billing_email billing_first_name billing_last_name billing-first_name billing-last_name shipping-first_name shipping-last_name woo_ml_subscribe email)
	// CHANGE: Remove state vars firstName lastName foundEmail checkboxAdded mailerLiteCheckoutBlockActive listeningEvents iteratorInterrupt execute
	// CHANGE: Remove mailerlitePublicJsCaptured and enabled checkboxSettings triggerAddEvents bootstrap
	// CHANGE: Remove retryCount maxRetries interval setInterval inputFound some clearInterval getElementById discovery loop input
	// CHANGE: Remove mailerLiteCheckoutBlockWrapper data-block-name mailerlite-block woo-mailerlite block detection return false
	// CHANGE: Remove allowedInputs.forEach match email first_name last_name querySelector firstName lastName assignment
	// CHANGE: Remove dynamic checkbox createElement input wooMlCheckoutCheckbox setAttribute className style marginTop label labelText cursor pointer inline-flex alignItems center htmlFor display none span textContent want receive your newsletter wrapper appendChild insertBefore checkboxWrapper woo-ml-subscribe-wrapper closest parentNode nextSibling
	// CHANGE: Remove change listeners firstName lastName signup addEventListener input name billing_email
	// CHANGE: Remove clearTimeout execute setTimeout debounce allowedInputs.includes e.target.id gate
	function validateMLSub(e) {
	    // CHANGE: Track in-flight AJAX / selector-based fields live in checkoutMLSub; resolve email dynamically for FC
	    const email = document.querySelector( '#billing_email' );

	    if(email !== null && email.value.length > 0) {
		   checkoutMLSub(e);
	    }
	}

	function checkoutMLSub(e) {
	    // CHANGE: Track in-flight AJAX so overlapping FC checkout updates do not stack requests
	    if ( typeof checkoutMLSub._xhr === 'undefined' ) {
		   checkoutMLSub._xhr = null;
	    }

	    /** set cookie before sending request to server
		* since multiple checkout update requests can be sent
		* and server cookies won't get updated, so send the saved
		* cookie as a request parameter
	    **/

	    if (!getCookie('mailerlite_checkout_token')) {
		   var now = new Date();
		   now.setTime(now.getTime() + 48 * 3600 * 1000);
		   document.cookie = `mailerlite_checkout_token=${(+new Date).toString()}; expires=${now.toUTCString()}; path=/`;
	    }

	    // CHANGE: Use FC field selectors instead of upstream cached element refs
	    const accept_marketing = document.querySelector( '#woo_ml_subscribe' ).checked;
	    const email = document.querySelector( '#billing_email' );
	    const first_name_field = document.querySelector( '#billing_first_name' );
	    const last_name_field = document.querySelector( '#billing_last_name' );

	    // CHANGE: Bail if an ajax request is already running
	    if ( checkoutMLSub._xhr !== null ) { return; }

	    // CHANGE: Use current plugin AJAX action when localized data is available, keep legacy fallback
	    var ajaxUrl;
	    var ajaxData;

	    if ( typeof wooMailerLitePublicData !== 'undefined' ) {
		   ajaxUrl = wooMailerLitePublicData.ajaxUrl;
		   ajaxData = {
			  action: 'woo_mailerlite_set_cart_email',
			  nonce: wooMailerLitePublicData.nonce,
			  email: email?.value ?? null,
			  signup: accept_marketing,
			  language: wooMailerLitePublicData.language,
			  name: first_name_field?.value ?? '',
			  last_name: last_name_field?.value ?? '',
			  cookie_mailerlite_checkout_token: getCookie( 'mailerlite_checkout_token' ),
		   };
	    } else if ( typeof woo_ml_public_post !== 'undefined' ) {
		   ajaxUrl = woo_ml_public_post.ajax_url;
		   ajaxData = {
			  action: 'post_woo_ml_email_cookie',
			  email: email?.value ?? null,
			  signup: accept_marketing,
			  language: woo_ml_public_post.language,
			  first_name: first_name_field?.value ?? '',
			  last_name: last_name_field?.value ?? '',
			  cookie_mailerlite_checkout_token: getCookie( 'mailerlite_checkout_token' ),
		   };
	    } else {
		   return;
	    }

	    checkoutMLSub._xhr = jQuery.ajax({
		   url: ajaxUrl,
		   type: 'post',
		   data: ajaxData,
		   // CHANGE: Set the xhr variable to null when the request is complete
		   complete: function() {
			  checkoutMLSub._xhr = null;
		   },
	    })
	}

	// CHANGE: Preselect from localized settings (current plugin) or legacy hidden field
	if ( typeof wooMailerLitePublicData !== 'undefined' && wooMailerLitePublicData.checkboxSettings && wooMailerLitePublicData.checkboxSettings.preselect ) {
	    jQuery( '#woo_ml_subscribe' ).prop( 'checked', true );
	} else if ( jQuery( '#woo_ml_preselect_enabled' )?.val() == 'yes' ) {
	    jQuery( '#woo_ml_subscribe' ).prop( 'checked', true );
	}

	// CHANGE: Use captured event handlers instead of events attached to specific elements
	var handleBlur = function( e ) {
	    if ( e.target.matches( '#billing_email' ) || e.target.matches( '#billing_first_name' ) || e.target.matches( '#billing_last_name' ) ) {
		   validateMLSub(e);
	    }
	};
	var handleChange = function( e ) {
	    if ( e.target.matches( '#woo_ml_subscribe' ) ) {
		   validateMLSub(e);
	    }
	}
	document.addEventListener( 'blur', handleBlur, true );
	document.addEventListener( 'change', handleChange, true );
	// CHANGE: END - Use captured event handlers instead of events attached to specific elements
});

function getCookie(name) {
	const value = `; ${document.cookie}`;
	const parts = value.split(`; ${name}=`);
	if (parts.length === 2) {
	    return parts.pop().split(';').shift()
	}
	return null;
}
