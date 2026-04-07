/**
 * Manage checkout functions necessary for the PayPlus Checkout payment gateway.
 *
 * DEPENDS ON:
 * - jQuery // Interact with WooCommerce events
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.PayPlusCheckout = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};



	/**
	 * METHODS
	 */



	// END - COPIED FROM PAYPLUS CHECKOUT.JS
	function closePayplusIframe(force) {
		if ($("#pp_iframe").length && ($("#pp_iframe").is(":visible") || force === true)) {
			$("#pp_iframe").fadeOut(()=>{
				$('.payplus-option-description-area').show()
				// CHANGE: Enable also additional Place Order buttons, and remove `disabled` class
				$("#place_order, .fc-place-order-button").prop("disabled",false).removeClass( 'disabled' );


			})
		}
	}
	function  addScriptApple(){
		if(payplus_script_checkout.payplus_import_applepay_script &&
			isMyScriptLoaded(payplus_script_checkout.payplus_import_applepay_script) ){
			const script = document.createElement('script');
			script.src =payplus_script_checkout.payplus_import_applepay_script;
			document.body.append(script);
		}
	}
	function isMyScriptLoaded(url) {
		var scripts = document.getElementsByTagName('script');
		for (var i = scripts.length; i--;) {
			if (scripts[i].src == url) return false;
		}
		return true;
	}
	function getIframePayment(src,width,height){
		let iframe = document.createElement('iframe');
		iframe.id="pp_iframe";
		iframe.name='payplus-iframe'
		iframe.src=src;
		iframe.height =height;
		iframe.width =width;
		iframe.setAttribute('style',`border:0px`);
		iframe.setAttribute('allowpaymentrequest','allowpaymentrequest')
		return iframe;
	}
	function openPayplusIframe(src) {

		$('.alertify').remove();
		const url = new URL(window.location.href);
		url.searchParams.set('payplus-iframe', '1');
		window.history.pushState({}, '', url);
		const ppIframe= document.querySelector(".pp_iframe");
		const height =ppIframe.getAttribute('data-height');
		ppIframe.innerHTML="";
		ppIframe.append(getIframePayment(src,"100%",height));
		// CHANGE: Disable also additional Place Order buttons, and add `disabled` class
		$("#place_order, .fc-place-order-button").prop("disabled",true).addClass( 'disabled' );

		if(payplus_script_checkout.payplus_mobile){
			$('html, body').animate({
				scrollTop: $(".place-order").offset().top
			});
		}
		addScriptApple();

	}

	function openIframePopup(src, height) {
		let  windowWidth = window.innerWidth;
			if(windowWidth< 568){
				height ="100%";
			}

		if (!alertify.popupIframePaymentPage) {
			alertify.dialog('popupIframePaymentPage', function factory() {
				return {
					main: function (src) {


						this.message = getIframePayment(src,"100%",height);
						addScriptApple();

					},
					setup: function () {
						return {
							options: {
								autoReset: false,
								overflow: false,
								maximizable: false,
								movable: false,
								frameless: true,
								transition: 'fade',
							},
							focus: {
								element: 0
							}
						};
					},

					prepare: function () {
						this.setContent(this.message);
					},

					hooks: {
						onshow: function() {
							this.elements.dialog.style.maxWidth = '100%';
							this.elements.dialog.style.width = '1050px';
							this.elements.dialog.style.height = '100%';
							this.elements.content.style.top = '25px';
						}
					}
				}
			});
		}
		alertify.popupIframePaymentPage(src);
	}
	// END - COPIED FROM PAYPLUS CHECKOUT.JS



	
	/**
	 * Maybe open the PayPlus iframe on form submit.
	 */
	var checkoutFormSubmitHandler = function( _e, result, wc_checkout_form ) {
		// COPIED FROM PAYPLUS CHECKOUT.JS
		if (result.payplus_iframe && 'success' === result.result) {
			wc_checkout_form.$checkout_form.removeClass( 'processing' ).unblock();
			if (result.viewMode == 'samePageIframe') {
				openPayplusIframe(result.payplus_iframe.data.payment_page_link)
			} else if(result.viewMode == 'popupIframe') {
				openIframePopup(result.payplus_iframe.data.payment_page_link,700)
			}

			// CHANGED: Need to return `false` here to let Fluid Checkout exit the function at the expected point,
			// as returning `true` means the code should continue running.
			return false;
		}
		// END - COPIED FROM PAYPLUS CHECKOUT.JS
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		if ( _hasInitialized ) return;

		if ( _hasJQuery ) {
			// Payment method change event
			// Originally the event `change` is not used by the PayPlus Checkout plugin,
			// but it's added here to ensure the function is called when the payment method is changed.
			$( document.body ).on( 'click change', 'input[name="payment_method"]', closePayplusIframe );

			// Place order event handler
			$( document.body ).on( 'fc_checkout_request_place_order_success', checkoutFormSubmitHandler );

			// COPIED FROM PAYPLUS CHECKOUT.JS
			// Adapted to use ES5 function syntax
			$( window ).on( "popstate", function() {
				closePayplusIframe( false );
			} );
			// END - COPIED FROM PAYPLUS CHECKOUT.JS

			_hasInitialized = true;
		}
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
