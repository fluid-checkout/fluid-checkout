(function(window, document, $, undefined){

	window.fluidCheckout = {};

	fluidCheckout.init = function() {

		fluidCheckout.body = $('body');

		// For animations
		fluidCheckout.inRightClasses  = 'wsc-animated slideInRight';
		fluidCheckout.inLeftClasses  = 'wsc-animated slideInLeft';
		fluidCheckout.outClasses = 'wsc-animated slideOutRight';

		// Cache some variables
		fluidCheckout.vars = {
			beforeshipping: $('#wsc-before-shipping-fields'),
			modal: $('.wsc-modal'),
			modalinside: $('.wsc-modal-inside'),
			wscinside: $('.wsc-inside'),
			billing_address_1: document.getElementById('billing_address_1'),
			address_val: '',
			display_modal: $('#wsc-wrapper').hasClass('wsc-modal') ? true : false,
			payment_method: null,
			isDevice: false
		}

		fluidCheckout.wooInit();
		
	}

	fluidCheckout.wooInit = function() {

		// Create multiple pages (frames), count them and ID them
		fluidCheckout.frames();

		// move address fields
		$.each( $('.woocommerce-billing-fields .form-row.address-field'), function( index, value ) {
			// fluidCheckout.vars.beforeshipping.append(value);
		});

		// test if we are on a device
		if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
			fluidCheckout.vars.isDevice = true;
		}

		if( fluidCheckout.vars.isDevice ) {
			var touchorclick = 'touchend';
		} else {
			var touchorclick = 'click';
		}

		// no-modal only events
		fluidCheckout.body

		.on( touchorclick, 'a.wsc-save-cart', function() {

			$('form.wsc-save-cart').slideToggle();

		})

		// More click events
		fluidCheckout.body

		.on( touchorclick, '.wsc-progress', function(e) {
			var clickid = $(e.target).attr('data-id');
			var currentid = $('.wsc-inside .wsc-frame:visible').attr('id').substr('5', '1' );

			// Hacky cause it doesn't work if you go from 1=>3 or reverse
			if( clickid == currentid ) {
				return; 
			} else if( clickid > currentid ) {
				$('#wsc-main').click();
			} else {
				$('.wsc-prev').click();
			}

		});

		setTimeout( function() {
			var shippingToggle = $('#ship-to-different-address-checkbox');

			if( !shippingToggle.is(':checked') ) {

				shippingToggle.prop( "checked", false );

				$('.shipping_address').hide();
				
			}

		}, 500);

		// Do next/prev stuff
		fluidCheckout.nav();

	}

	// Create/assign classes to multi-page frames
	fluidCheckout.frames = function() {

		// Show first frame
		var frames = fluidCheckout.vars.wscinside.find('.wsc-frame');
		frames.first().show();

		console.log('frames', frames)

		// Add unique ID to each frame for navigating
		for (var i = frames.length - 1; i >= 0; i--) {
			var num = i + 1;
			$(frames[i]).attr('id', 'frame' + num);
			$('#wsc-progressbar').prepend('<div class="wsc-progress" id="progress' + num + '" data-id="' + num + '">' + fluidCheckoutVars.step_title + num + '</div>');
		}
	}

	fluidCheckout.updateTotal = function() {
		var total = $('.order-total .amount').html();

		if( total == null ) {
			// if total isn't there for some reason, get it here instead
			total = $('.shop_table .total .amount').text();
		}
		$('.wsc-cart-total .total-price').html( fluidCheckoutVars.total + total );
	}

	// Format our credit card inputs
	fluidCheckout.ccFields = function() {

		$('input.card-number').attr('placeholder', '•••• •••• •••• ••••').attr('autocomplete','cc-number').attr('type', 'tel').payment('formatCardNumber');
		
      	$('.card-cvc').attr('placeholder', '•••').attr('type', 'tel').payment('formatCardCVC');

		// Replace expire select menus with text fields
		var newExpiry = '<input type="tel" autocomplete="cc-exp" id="cc-expire-replacement" placeholder="•• / ••••">';

		if( !$('#cc-expire-replacement').length ) {

			$('#cc-expire-month').before( $(newExpiry).payment('formatCardExpiry') );

		}

		$('#cc-expire-month, #cc-expire-year').hide();

		setTimeout( function() {
			$('#cc-expire-replacement').on( 'keyup', function() {
				// TODO: needs to be a regex to remove 0 from front, and get next 1 or 2 numbers before slash
				var month = $(this).val().substr(1, 1);
				var year = $(this).val().substr(5, 4);
				$('#cc-expire-month').val( month );
				console.log(year);
				$('#cc-expire-year').val( year );
			});
		}, 100);
		

	}

	fluidCheckout.animateInLeft = function(el) {
		el.css('display', 'block');

		// Show element, then animate to fix bug
		setTimeout( function() {
			el.removeClass(fluidCheckout.outClasses).addClass(fluidCheckout.inLeftClasses);
		},1);
	}

	fluidCheckout.animateOut = function(el) {
		el.removeClass(fluidCheckout.inRightClasses).addClass(fluidCheckout.outClasses).css('display', 'none');
	}

	// Next/prev navigation
	fluidCheckout.nav = function() {

		// Push initial state when page loads
		if( window.location.href.indexOf( fluidCheckoutVars.woo_checkout_url ) != -1 ) {
			fluidCheckout.doHistory(1);
			fluidCheckout.doProgress(1);
		}

		// Support browser back button
		window.addEventListener('popstate', fluidCheckout.popstate );

		$('#wsc-main').on('click', fluidCheckout.doNext );

		$('.wsc-prev').on('click', fluidCheckout.doPrev );
	}

	fluidCheckout.doPrev = function() {

		var shown = $('.wsc-inside section:visible');
		// var prev = $(shown).prev('section');
		var prevId = shown.attr('id').substring(5);
		prevId--;

		if( prevId == '0' ) {
			window.location.href = fluidCheckoutVars.woo_cart_url;
			return;
		}

		$('#wsc-main').show().html( fluidCheckoutVars.next );
		
		var prev = $('#frame' + prevId);

		fluidCheckout.animateInLeft( prev );

		fluidCheckout.animateOut( shown );

		fluidCheckout.doHistory(prevId);

		fluidCheckout.doProgress(prevId);

	}

	fluidCheckout.validate = function(input) {

		if( $(input).val() == '' ) {
			$(input).css('background-color','rgba(255,0,0,.5)');
		} else {
			$(input).css('background-color','');
		}

	}

	fluidCheckout.doNext = function(e) {
		e.preventDefault();

		//var next = $(this).data("next");
		var current = $('.wsc-inside .wsc-frame:visible');
		
		// Get next frame ID
		var nextId = current.attr('id').substring(5);

		nextId++;

		if( $(this).text() == fluidCheckoutVars.purchase ) {
			$('#place_order').click();
			return;
		}

		var frames = fluidCheckout.vars.wscinside.find('.wsc-frame');

		if( nextId == frames.length && fluidCheckout.vars.display_modal ) {
			// This is the last frame, so make it a purchase btn
			$('#wsc-main').text( fluidCheckoutVars.purchase );
		} else if ( nextId == frames.length && !fluidCheckout.vars.display_modal ) {
			// don't need purchase button if no modal
			$('#wsc-main').hide();
		}

	  	var next = $('#frame' + nextId);
		
		$( next ).removeClass().addClass('wsc-frame ' + fluidCheckout.inRightClasses).css('display', 'block');

		fluidCheckout.animateOut( current );

		if( fluidCheckout.vars.display_modal ) {
			window.scrollTo(0,0);
			$('.wsc-prev').show();
		}

		fluidCheckout.doHistory(nextId);

		fluidCheckout.doProgress(nextId);

	}

	fluidCheckout.popstate = function(e) {
		// console.log(e.state);

		// Prevents going back when button is clicked
		if( e.state == null ) {
			return;
		}

		var shown = $('.wsc-inside section:visible');
		// var prev = $(shown).prev('section');
		var prevId = e.state.frame_id;
		
		// If we are on the first frame, do default browser back
		if( e.state.frame_id == $(shown).attr('id').substring(5) ) {
			window.history.back();
			return;
		}

		$('#wsc-main').show().html( fluidCheckoutVars.next );

		if( prevId == '0' ) {
			window.location.href = fluidCheckoutVars.woo_cart_url;
			return;
		}

		var prev = $('#frame' + prevId);

		var frames = fluidCheckout.vars.wscinside.find('.wsc-frame');

		if( prevId == frames.length ) {
			// This is the last frame, so make it a purchase btn
			$('#wsc-main').text( fluidCheckoutVars.purchase );
		}

		fluidCheckout.animateInLeft(prev);
		fluidCheckout.animateOut(shown);

		fluidCheckout.doProgress(prevId);
	  
	}

	fluidCheckout.doHistory = function(id) {
		// Let's make history
		history.pushState( { 'frame_id': id }, null, '#' );
	}

	fluidCheckout.doProgress = function(id) {
		// Add active class to current progress bar item
		$('#wsc-progressbar div').removeClass('active');
		$('#progress' + id).addClass('active');
	}

	$(document).ready( function() {
		fluidCheckout.init(); 
	});

	$(document).on( 'load_ajax_content_done', function() {

		// Need to check if we are on checkout page
		if( window.location.href.indexOf( fluidCheckoutVars.woo_checkout_url ) == -1 ) {
			$('#wsc-wrapper').hide();
			return;
		} else {
			fluidCheckout.init();
		}
	});

})(window, document, jQuery);