jQuery(document).ready(function(a) {
	var _xhr = null;
	// CHANGE: Replace field variables with selectors variables
	var _emailFieldSelector = '#billing_email';
	var _firstNameFieldSelector = '#billing_first_name';
	var _lastNameFieldSelector = '#billing_last_name';
	var _signupSelector = '#woo_ml_subscribe';
 
	if (jQuery('#woo_ml_preselect_enabled')?.val() == 'yes') {
	    // CHANGE: Use selector variable, instead of declaring the selector directly
	    jQuery( _signupSelector ).prop('checked', true);
	}
 
	// CHANGE: Remove event handler declarations as they are replaced with captured events at bottom
 
	function validateMLSub() {
	    // CHANGE: Get fields dinamically
	    const email = document.querySelector( _emailFieldSelector );
 
	    if(email !== null && email.value.length > 0) {
		   checkoutMLSub();
	    }
	}
 
	function checkoutMLSub() {
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
 
	    // CHANGE: Use selector variable, instead of declaring the selector directly
	    const accept_marketing = document.querySelector( _signupSelector ).checked;
 
	    // CHANGE: Get fields dinamically
	    const email = document.querySelector( _emailFieldSelector );
	    const first_name_field = document.querySelector( _firstNameFieldSelector );
	    const last_name_field = document.querySelector( _lastNameFieldSelector );
 
	    let first_name = '';
	    let last_name = '';
 
	    if (first_name_field !== null) {
		   first_name = first_name_field.value;
	    }
 
	    if (last_name_field !== null) {
		   last_name = last_name_field.value;
	    }
 
	    // CHANGE: Bail if an ajax request is already running
	    if ( _xhr !== null ) { return; }
 
	    _xhr = jQuery.ajax({
		   url: woo_ml_public_post.ajax_url,
		   type: "post",
		   data: {
			  action: "post_woo_ml_email_cookie",
			  email: email.value,
			  signup: accept_marketing,
			  language: woo_ml_public_post.language,
			  first_name: first_name,
			  last_name: last_name,
			  cookie_mailerlite_checkout_token:getCookie('mailerlite_checkout_token')
		   },
		   // CHANGE: Set the xhr variable to null when the request is complete
		   complete: function() {
			  _xhr = null;
		   },
	    })
	}
 
	// CHANGE: Use captured event handlers instead of events attached to specific elements
	var handleBlur = function( e ) {
	    // EMAIL, FIRST NAME, LAST NAME
	    if ( e.target.matches( _emailFieldSelector ) || e.target.matches( _firstNameFieldSelector ) || e.target.matches( _lastNameFieldSelector ) ) {
		   validateMLSub();
	    }
	};
	var handleChange = function( e ) {
	    // SIGNUP
	    if ( e.target.matches( _signupSelector ) ) {
		   validateMLSub();
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