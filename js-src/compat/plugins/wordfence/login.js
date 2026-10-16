(function($) {
	function __(string) {
		if (window.WFLS_LOGIN_TRANSLATIONS && window.WFLS_LOGIN_TRANSLATIONS[string]) {
			return window.WFLS_LOGIN_TRANSLATIONS[string];
		}
		if (window.wflsi18n && typeof window.wflsi18n.__ === 'function') {
			return window.wflsi18n.__(string);
		}
		return string;
	}
	// Login help is plain text, including localized content; Zebra must not parse it as HTML.
	!function(Z){"use strict";Z.Zebra_Tooltips=function(t,l){var u,T,b,v,n={animation_speed:250,animation_offset:20,close_on_click:!0,content:!(this.version="2.1.0"),hide_delay:100,keep_visible:!0,max_width:250,opacity:".95",position:"center",prerender:!1,show_delay:100,vertical_alignment:"above",vertical_offset:0,onBeforeHide:null,onHide:null,onBeforeShow:null,onShow:null},r=this,_=function(e){var t,o,i,a,l,n,r,_,s,p,d,h,c,f,w=e.data("Zebra_Tooltip"),m=Z(window);return w.tooltip||(t=Z("<div>",{"class":"Zebra_Tooltip",css:{opacity:0,display:"block"}}),o=Z("<div>",{"class":"Zebra_Tooltip_Message",css:{maxWidth:w.max_width}}).text(w.content).appendTo(t),i=Z("<div>",{"class":"Zebra_Tooltip_Arrow"}).appendTo(t),a=Z("<div>").addClass("Zebra_Tooltip_Arrow_Border").appendTo(i),Z("<div>").appendTo(i),w.keep_visible&&(t.on("mouseleave"+(w.close_on_click?" click":""),function(){y(e)}),t.on("mouseenter",function(){g(e)})),t.appendTo("body"),w.sticky&&o.addClass("Zebra_Tooltip_Has_Close"),l=t.outerWidth(),n=t.outerHeight(),r=a.outerWidth(),_=a.outerHeight(),s=o.outerWidth(),p=o.outerHeight(),w={tooltip:t,tooltip_width:l,tooltip_height:n+_/2,message:o,arrow_container:i,arrow_width:r,arrow_height:_,arrow:a},t.css({width:w.tooltip_width,height:w.tooltip_height}),w.tooltip_width=w.tooltip_width+(o.outerWidth()-s),w.tooltip_height=w.tooltip_height+(o.outerHeight()-p),t.css({width:w.tooltip_width,height:w.tooltip_height,display:"none"}),w=Z.extend(e.data("Zebra_Tooltip"),w),e.data("Zebra_Tooltip",w)),w.sticky&&!w.close&&(Z("<a>",{"class":"Zebra_Tooltip_Close",href:"javascript:void(0)"}).html("&times;").on("click",function(t){t.preventDefault();var o=e.data("Zebra_Tooltip");o.sticky=!1,e.data("Zebra_Tooltip",o),y(e)}).appendTo(w.message),w.close=!0,w=Z.extend(e.data("Zebra_Tooltip"),w),e.data("Zebra_Tooltip",w)),u=m.width(),T=m.height(),d=e.offset(),Z.extend(w,{element_left:d.left,element_top:d.top,element_width:e.outerWidth(),element_height:e.outerHeight()}),v=m.scrollTop(),b=m.scrollLeft(),h="left"===w.position?w.element_left-w.tooltip_width+w.arrow_width:"right"===w.position?w.element_left+w.element_width-w.arrow_width:w.element_left+(w.element_width-w.tooltip_width)/2,c=w.element_top-w.tooltip_height,f="left"===w.position?w.tooltip_width-w.arrow_width-w.arrow_width/2:"right"===w.position?w.arrow_width/2:(w.tooltip_width-w.arrow_width)/2,h+w.tooltip_width>u+b&&(f-=u+b-(h+w.tooltip_width)-6,h=u+b-w.tooltip_width-6,f+w.arrow_width>w.tooltip_width-6&&(f=w.tooltip_width-6-w.arrow_width),h+f+w.arrow_width/2<w.element_left&&(f=-1e4)),h<b&&(f-=b-h,h=b+2,f<0&&(f=w.arrow_width/2),h+f+w.arrow_width/2>w.element_left+w.element_width&&(f=-1e4)),w.message.css("margin-top",""),w.arrow_container.removeClass("Zebra_Tooltip_Arrow_Top").addClass("Zebra_Tooltip_Arrow_Bottom"),c<v||"below"===w.vertical_alignment&&w.element_top+w.element_height+w.vertical_offset+w.tooltip_height+w.animation_offset<T+v?(c=w.element_top+w.element_height-w.vertical_offset,w.animation_offset=Math.abs(w.animation_offset),w.message.css("margin-top",w.arrow_height/2),w.arrow_container.removeClass("Zebra_Tooltip_Arrow_Bottom").addClass("Zebra_Tooltip_Arrow_Top")):(w.animation_offset=-Math.abs(w.animation_offset),c+=w.vertical_offset),w.arrow_container.css("left",f),w.tooltip.css({left:h,top:c}),Z.extend(w,{tooltip_left:h,tooltip_top:c,arrow_left:f}),w=Z.extend(e.data("Zebra_Tooltip"),w),e.data("Zebra_Tooltip",w),w},g=function(t){var o=t.data("Zebra_Tooltip");clearTimeout(o.show_timeout),o.muted||(clearTimeout(o.hide_timeout),o.show_timeout=setTimeout(function(){(o=_(t)).onBeforeShow&&"function"==typeof o.onBeforeShow&&!1===o.onBeforeShow(t,o.tooltip)||("block"!==o.tooltip.css("display")&&o.tooltip.css({top:o.tooltip_top+o.animation_offset}),o.tooltip.css("display","block"),o.tooltip.stop(),o.tooltip.animate({top:o.tooltip_top,opacity:o.opacity},o.animation_speed,function(){o.onShow&&"function"==typeof o.onShow&&o.onShow(t,o.tooltip)}))},o.show_delay))},y=function(t){var o=t.data("Zebra_Tooltip");clearTimeout(o.hide_timeout),o.sticky||(clearTimeout(o.show_timeout),o.hide_timeout=setTimeout(function(){if(o.tooltip){if(o.onBeforeHide&&"function"==typeof o.onBeforeHide&&!1===o.onBeforeHide(t,o.tooltip))return;o.close=!1,o.destroy&&(o.muted=!0),t.data("Zebra_Tooltip",o),Z("a.Zebra_Tooltip_Close",o.tooltip).remove(),o.tooltip.stop(),o.tooltip.animate({opacity:0,top:o.tooltip_top+o.animation_offset},o.animation_speed,function(){Z(this).css("display","none"),o.onHide&&"function"==typeof o.onHide&&o.onHide(t,o.tooltip)})}},o.hide_delay))};r.hide=function(t,e){t.each(function(){var t=Z(this),o=t.data("Zebra_Tooltip");o&&(o.sticky=!1,e&&(o.destroy=!0),t.data("Zebra_Tooltip",o),y(t))})},r.show=function(t,e){t.each(function(){var t=Z(this),o=t.data("Zebra_Tooltip");o&&(o.sticky=!0,o.muted=!1,e&&(o.destroy=!0),t.data("Zebra_Tooltip",o),g(t))})},t.each(function(){var t,o=Z(this),e=o.attr("title"),i=o.data(),a={};for(t in i)0===t.indexOf("ztt_")&&(t=t.replace(/^ztt\_/,""),void 0!==n[t]&&(a[t]=i["ztt_"+t]));a=Z.extend(n,r.settings,l,a),e&&(a.content=o.attr("title")),void 0!==a.content&&""!==a.content.trim()&&(o.on({mouseenter:function(){e&&Z(this).attr("title",""),g(o)},mouseleave:function(){y(o),e&&Z(this).attr("title",e)}}),o.data("Zebra_Tooltip",Z.extend({tooltip:null,show_timeout:null,hide_timeout:null,sticky:!1,destroy:!1,muted:!1},a)),a.prerender&&_(o))})}}($);

	function selectorSearch(selectors) {
		var input = null;
		return function() {
			if (input !== null)
				return input;
			for (var i = 0; i < selectors.length; i++) {
				input = $(selectors[i]);
				if (input.length === 1)
					return input;
			}
			input = null;
			return input;
		}
	};
	function FormElementLocator(inputSelectors, extraSelectors) {
		var self = this;

		if (typeof extraSelectors == 'undefined')
			extraSelectors = {};

		var found = false;
		var input = null, form = null, extra = {};

		var findInput = selectorSearch(inputSelectors);
		var search = function() {
			input = findInput();
			if (input === null)
				return false;
			form = input.closest('form');
			if (form.length !== 1) {
				form = null;
				return false;
			}
			for (var key in extraSelectors) {
				var match = form.find(extraSelectors[key]);
				if (match.length === 1) {
					extra[key] = match;
				}
				else {
					return false;
				}
			}
			return true;
		};
		this.locate = function() {
			if (!found)
				found = search();
			return found;
		};
		this.getInput = function() {
			return found ? input : null;
		};
		this.getForm = function() {
			return found ? form : null;
		};
		this.getExtra = function(key) {
			if (key in extra)
				return extra[key];
			return null;
		}
	}
	var loginLocator = new FormElementLocator(
		[
			'input[name=log]',
			'input[name=username]',
			'input#username[name=username]',
			'.woocommerce-form-login input[name=username]',
			'.login input[name=username]'
		],
		{
			password: 'input[name="pwd"],input[name=password]'
		}
	);
	var registrationLocator = new FormElementLocator([
		'input[name=user_login]',
		'#reg_email',
		'.woocommerce-form-register input[name=email]',
		'.register input[name=email]'
	]);
	const locators = [
		loginLocator,
		registrationLocator
	];
	function findLoginSubmit(form) {
		var selectors = [
			'input#wp-submit',
			'input[name=wp-submit]',
			'button[type=submit][name=login]',
			'input[type=submit][name=login]',
			'.woocommerce-form-login__submit',
			'input[type=submit]',
			'button[type=submit]'
		];
		for (var i = 0; i < selectors.length; i++) {
			var matches = form.find(selectors[i]);
			var visible = matches.filter(':visible');
			if (visible.length) {
				return visible.first();
			}
			if (matches.length) {
				return matches.first();
			}
		}
		return $();
	}
	function getRelevantInputs() {
		var inputs = $();
		for (var i = 0; i < locators.length; i++) {
			if (locators[i].locate())
				inputs = inputs.add(locators[i].getInput());
		}
		return inputs;
	}
	
	var wfls_init_captcha = function(actionCallback, log) {
		if (typeof log === 'undefined')
			log = getRelevantInputs();
		if (typeof grecaptcha === 'object') {
			grecaptcha.ready(function() {
				grecaptcha.execute(WFLSVars.recaptchasitekey, {action: 'login'}).then(function(token) {
					var tokenField = $('#wfls-captcha-token');
					if (tokenField.length) {
						tokenField.val(token);
					}
					else {
						if (log.length) {
							tokenField = $('<input type="hidden" name="wfls-captcha-token" id="wfls-captcha-token" />');
							tokenField.val(token);
							log.parent().append(tokenField);
						}
					}

					typeof actionCallback === 'function' && actionCallback(true);
				});
			});
		}
		else {
			var tokenField = $('#wfls-captcha-token');
			if (tokenField.length) {
				tokenField.val('grecaptcha-missing');
			}
			else {
				if (log.length) {
					tokenField = $('<input type="hidden" name="wfls-captcha-token" id="wfls-captcha-token" />');
					tokenField.val('grecaptcha-missing');
					log.parent().append(tokenField);
				}
			}

			typeof actionCallback === 'function' && actionCallback(true);
		}
	};

	function wfls_base64url_to_uint8array(base64url) {
		var padding = '='.repeat((4 - (base64url.length % 4)) % 4);
		var base64 = (base64url + padding).replace(/-/g, '+').replace(/_/g, '/');
		var raw = window.atob(base64);
		var bytes = new Uint8Array(raw.length);
		for (var i = 0; i < raw.length; i++) {
			bytes[i] = raw.charCodeAt(i);
		}
		return bytes;
	}

	function wfls_arraybuffer_to_base64url(buffer) {
		var bytes = buffer instanceof Uint8Array ? buffer : new Uint8Array(buffer);
		var binary = '';
		for (var i = 0; i < bytes.length; i++) {
			binary += String.fromCharCode(bytes[i]);
		}
		return window.btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
	}

	function wfls_passkey_request_options_from_json(options) {
		var parsed = $.extend(true, {}, options);
		parsed.challenge = wfls_base64url_to_uint8array(parsed.challenge);
		if (parsed.allowCredentials && parsed.allowCredentials.length) {
			for (var i = 0; i < parsed.allowCredentials.length; i++) {
				parsed.allowCredentials[i].id = wfls_base64url_to_uint8array(parsed.allowCredentials[i].id);
			}
		}
		return parsed;
	}

	function wfls_assertion_to_json(assertion) {
		return {
			id: assertion.id,
			type: assertion.type,
			rawId: wfls_arraybuffer_to_base64url(assertion.rawId),
			response: {
				clientDataJSON: wfls_arraybuffer_to_base64url(assertion.response.clientDataJSON),
				authenticatorData: wfls_arraybuffer_to_base64url(assertion.response.authenticatorData),
				signature: wfls_arraybuffer_to_base64url(assertion.response.signature),
				userHandle: assertion.response.userHandle ? wfls_arraybuffer_to_base64url(assertion.response.userHandle) : null
			}
		};
	}

	function wfls_passkey_error_message(error, fallback) {
		if (wfls_passkeys_require_https()) {
			return __('Passkey login requires HTTPS. Open this site using https:// and try again.');
		}
		if (!error) {
			return fallback;
		}
		if (error.name && error.message) {
			return error.name + ': ' + error.message;
		}
		if (error.message) {
			return error.message;
		}
		return fallback;
	}

	function wfls_passkeys_require_https() {
		return window.location && window.location.protocol === 'http:' && window.location.hostname !== 'localhost';
	}

	function wfls_passkey_redirect_target(form) {
		var redirect = form.find('input[name=redirect_to]').first();
		if (redirect.length && redirect.val()) {
			return redirect.val();
		}
		if ($('.woocommerce').length > 0) {
			return window.location.href;
		}
		return '';
	}

	function wfls_is_interim_login(form) {
		var interim = form.find('input[name="interim-login"]').first();
		return (interim.length && interim.val()) || window.location.search.indexOf('interim-login=1') >= 0;
	}

	function wfls_has_same_origin_parent_frame() {
		if (!window.parent || window.parent === window) {
			return false;
		}

		try {
			return window.parent.location.protocol === window.location.protocol && window.parent.location.host === window.location.host;
		}
		catch (e) {
			return false;
		}
	}

	function wfls_allows_same_relying_party_frame_login(form) {
		return !!wfls_is_interim_login(form) && wfls_has_same_origin_parent_frame();
	}

	function wfls_complete_passkey_login(form, redirect) {
		if (wfls_is_interim_login(form)) {
			if (window.parent && window.parent !== window) {
				try {
					if (window.parent.jQuery) {
						window.parent.jQuery(window.parent).off('beforeunload.wp-auth-check');
					}
					window.parent.location.reload();
					return;
				}
				catch (e) {}
			}

			window.location.reload();
			return;
		}

		window.location.href = redirect || window.location.href;
	}

	function wfls_begin_passkey_login_request(form, onSuccess, onError) {
		$.ajax({
			type: 'POST',
			url: WFLSVars.ajaxurl,
			dataType: 'json',
			data: {
				action: 'wordfence_ls_begin_passkey_login',
				interim_login: wfls_allows_same_relying_party_frame_login(form) ? 1 : 0
			},
			success: function(json) {
				if (json.hasOwnProperty('error')) {
					onError(json.error);
					return;
				}
				onSuccess(json);
			},
			error: function() {
				onError(__('An error was encountered while trying to start passkey login. Please try again.'), true);
			}
		});
	}

	function wfls_post_finish_passkey_login(button, form, token, assertion) {
		var remember = false;
		form.find('input[name=rememberme], input[name=remember]').each(function() {
			if ($(this).is(':checkbox') && $(this).is(':checked')) {
				remember = true;
			}
			else if (!$(this).is(':checkbox') && $(this).val()) {
				remember = true;
			}
		});

		$.ajax({
			type: 'POST',
			url: WFLSVars.ajaxurl,
			dataType: 'json',
			data: {
				action: 'wordfence_ls_finish_passkey_login',
				token: token,
				credential: wfls_assertion_to_json(assertion),
				rememberme: remember ? 1 : 0,
				redirect_to: wfls_passkey_redirect_target(form)
			},
			success: function(json) {
				button.prop('disabled', false).removeClass('disabled');
				if (json.hasOwnProperty('error')) {
					showLoginMessage(json.error, 'error');
					return;
				}
				wfls_complete_passkey_login(form, json.redirect || '');
			},
			error: function() {
				button.prop('disabled', false).removeClass('disabled');
				showLoginMessage(__('An error was encountered while trying to authenticate with a passkey. Please try again.'), 'error', true);
			}
		});
	}

	function wfls_finish_passkey_login(button, form, token, assertion) {
		wfls_post_finish_passkey_login(button, form, token, assertion);
	}

	function wfls_start_passkey_login(button) {
		if (!loginLocator.locate()) {
			console.error('Required login elements not found');
			return;
		}

		var form = loginLocator.getForm();
		button.prop('disabled', true).addClass('disabled');

		wfls_begin_passkey_login_request(form, function(json) {
			navigator.credentials.get({
				publicKey: wfls_passkey_request_options_from_json(json.options)
			}).then(function(assertion) {
				wfls_finish_passkey_login(button, form, json.token, assertion);
			}).catch(function(error) {
				button.prop('disabled', false).removeClass('disabled');
				if (error && error.name === 'NotAllowedError') {
					return;
				}
				showLoginMessage(wfls_passkey_error_message(error, __('Passkey login could not be completed.')), 'error', true);
			});
		}, function(message, textOnly) {
			button.prop('disabled', false).removeClass('disabled');
			showLoginMessage(message, 'error', textOnly);
		});
	}

	function showLoginMessage(message, type, textOnly) {
		var contentMethod = textOnly ? 'text' : 'html';
		$('.wfls-login-message').remove();
		$('#wfls-login-modal').remove();

		var heading = $('#login > h1');
		if (heading.length > 0) {
			var dom = (type === 'error' ? $('<div id="login_error">') : $('<p class="message">'));
			dom.addClass('wfls-login-message');
			dom.addClass('notice');
			if (type === 'error') {
				dom.addClass('notice-error');
			}
			dom[contentMethod](message);
			heading.after(dom);
			dom.get(0).scrollIntoView();
			return;
		}
		else if ($('.woocommerce').length > 0){
			var content = $('<div class="woocommerce wfls-login-message">');
			var errorList = $('<ul role="alert">')
				.addClass(type === 'error' ? 'woocommerce-error' : 'woocommerce-info');
			content.append(errorList);
			errorList.append($('<li>')[contentMethod](message));
			// CHANGE: Set the form as the default location for messages instead of using container elements, to ensure compatibility with FC's login popup.
			var container = loginLocator.getForm();
			if (container === null) {
				var containerSearch = selectorSearch([
					'#primary',
					'.content-area',
					'#main',
					'.site-main',
					'main'
				]);
				container = containerSearch();
			}
			if (container !== null) {
				container.before(content);
				content.get(0).scrollIntoView();
				return;
			}
		}
		var messageModal = $('<div>')
			.attr('id', 'wfls-login-modal')
			.css({
				position: 'fixed',
				top: 0,
				right: 0,
				bottom: 0,
				left: 0,
				'background-color': 'rgba(0,0,0,0.5)',
				'z-index': 9999,
				display: 'flex',
				'align-items': 'center',
				'justify-content': 'center',
				padding: '16px'
			})
			.appendTo($('body'));
		messageModal.append(
			$('<div>')
				.css({
					'background-color': '#FFF',
					'border-radius': '4px',
					padding: '16px',
					'text-align': 'center'
				})
				.append(
					$('<p>')[contentMethod](message)
				)
				.append(
					$('<button>').text('Dismiss')
						.on('click', function() {
							messageModal.remove();
						})
				)
		);
	}
		
	
	var wfls_init_captcha_contact = function() {
		$('.wfls-registration-captcha-contact').on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();

			if (registrationLocator.locate()) {
				$('#wfls-prompt-overlay').remove();
				var overlay = $('<div id="wfls-prompt-overlay"></div>');
				var wrapper = $('<div id="wfls-prompt-wrapper"></div>');
				var field = $('<p><label for="wfls-message"></label><br/><textarea name="wfls-message" id="wfls-message" class="wfls-textarea"></textarea></p>');
				field.find('label[for=wfls-message]').text(__('Message to Support'));
				var nonce = $('<input type="hidden" name="wfls-message-nonce" id="wfls-message-nonce"/>');
				var button = $('<p class="submit"><input type="submit" name="wfls-support-submit" id="wfls-support-submit" class="button button-primary button-large"/></p>');
				button.find('input[type=submit]').val(__('Send'));
				wrapper.append(field).append(nonce).append(button);
				overlay.append(wrapper);
				registrationLocator.getForm().css('position', 'relative').append(overlay);
				
				$('#wfls-message-nonce').val($(this).data('token'));
	
				$('#wfls-support-submit').on('click', function(e) {
					e.preventDefault();
					e.stopPropagation();

					$('#login_error, p.message').remove();
	
					var data = registrationLocator.getForm().serialize();
					data += '&action=wordfence_ls_register_support';

					$.ajax({
						type: 'POST',
						url: WFLSVars.ajaxurl,
						dataType: 'json',
						data: data,
						success: function(json) {
							if (json.hasOwnProperty('error')) {
								showLoginMessage(json.error, 'error');
								var dom = $('<div id="login_error">' + json.error + '</div>');
								$('#login > h1').after(dom);
							}
							else if (json.hasOwnProperty('message')) { //Success
								showLoginMessage(json.message, 'message');
								var dom = $('<p class="message">' + json.message + '</p>');
								$('#login > h1').after(dom);
								$('#wfls-support-submit, #wfls-message').attr('disabled', true);
							}
						},
						error: function(err) {
							showLoginMessage(__('An error was encountered while trying to send the message. Please try again.'), 'error', true);
							var dom = $('<div id="login_error"></div>');
							dom.html(__('<strong>ERROR</strong>: An error was encountered while trying to send the message. Please try again.'));
							$('#login > h1').after(dom);
						}
					});
				});

				field.find("#wfls-message").focus();
			}
		});
	};

	function FormBlocker(form, buttonSelector, clickOnSubmit) {

		var self = this;
		var blocked = false;
		var released = false;
		clickOnSubmit = clickOnSubmit || false;
		var clickSubmitInProgress = false;

		this.getButtons = function() {
			return form.find(buttonSelector);
		}

		this.block = function() {
			if (blocked)
				return false;
			blocked = true;
			this.getButtons().addClass('disabled').prop('disabled', true);
			return true;
		}

		this.unblock = function() {
			this.getButtons().removeClass('disabled').prop('disabled', false);
			blocked = false;
		}

		this.release = function() {
			released = true;
		}

		this.clickSubmit = function() {
			this.unblock();
			var submitButton = this.getButtons().first();
			setTimeout(function() {
				submitButton.trigger('click');
			}, 1);
		}

		this.initialize = function(callback) {
			form.on('submit', function(event) {
				if (released && (!clickOnSubmit || clickSubmitInProgress)) {
					if (clickSubmitInProgress)
						clickSubmitInProgress = false;
					return;
				}
				event.preventDefault();
				event.stopPropagation();
				if (released) {
					clickSubmitInProgress = true;
					self.clickSubmit();
					return;
				}
				if (self.block()) {
					callback();
				}
			});
		}

	}

	var wfls_query_ajax = function(blocker) {
		$('.wfls-login-message').remove();

		if (!loginLocator.locate()) {
			console.error('Required login elements not found');
			return;
		}
		var form = loginLocator.getForm();
		var log = loginLocator.getInput();
		var pwd = loginLocator.getExtra('password');
		
		var data = $(form).serialize();
		data += '&action=wordfence_ls_authenticate';

		$.ajax({
			type: 'POST',
			url: WFLSVars.ajaxurl,
			dataType: 'json',
			data: data,
			success: function(json) {
				if (json.hasOwnProperty('reset') && json.reset) {
					$('#wfls-prompt-overlay').remove();
				}
				
				if (json.hasOwnProperty('error')) {
					showLoginMessage(json.error, 'error');
					$('#wfls-token').val('');

					if (parseInt(WFLSVars.useCAPTCHA)) {
						wfls_init_captcha();
					}
				}
				else if (json.hasOwnProperty('message')) {
					showLoginMessage(json.message, 'message');
					$('#wfls-token').val('');

					if (parseInt(WFLSVars.useCAPTCHA)) {
						wfls_init_captcha();
					}
				}
				else if (json.hasOwnProperty('login')) {
					if (json.hasOwnProperty('captcha')) {
						var captchaField = $('#wfls-captcha-jwt');
						if (!captchaField.length) {
							captchaField = $('<input type="hidden" name="wfls-captcha-jwt" id="wfls-captcha-jwt" value=""/>');
							form.append(captchaField);
						}
						
						$('#wfls-captcha-jwt').val(json.captcha);
					}
					
					blocker.release();
					if (json.hasOwnProperty('two_factor_required') && json.two_factor_required) {
						if ($('#wfls-prompt-overlay').length === 0) {
							var overlay = $('<div id="wfls-prompt-overlay"></div>');
							var wrapper = $('<div id="wfls-prompt-wrapper"></div>');
							var label = $('<label for="wfls-token">');
							label.text(__('Wordfence 2FA Code') + ' ');
							label.append($('<a href="javascript:void(0)" class="wfls-2fa-code-help wfls-tooltip-trigger"><i class="dashicons dashicons-editor-help"></i></a>').attr('title', __('The Wordfence 2FA Code can be found within the authenticator app you used when first activating two-factor authentication. You may also use one of your recovery codes.')));
							label = $('<p>').append(label);
							var field = $('<p><input type="text" name="wfls-token" id="wfls-token" aria-describedby="wfls-token-error" class="input" value="" size="6" autocomplete="one-time-code"/></p>');
							var remember = $('<p class="wfls-remember-device-wrapper"><label for="wfls-remember-device"><input name="wfls-remember-device" type="checkbox" id="wfls-remember-device" value="1" /> </label></p>');
							remember.find('label').append(document.createTextNode(__('Remember for 30 days')));
							var button = $('<p class="submit"><input type="submit" name="wfls-token-submit" id="wfls-token-submit" class="button button-primary button-large"/></p>');
							button.find('input[type=submit]').val(__('Log In'));
							wrapper.append(label);
							wrapper.append(field);
							if (parseInt(WFLSVars.allowremember)) {
								wrapper.append(remember);
							}
							wrapper.append(button);
							overlay.append(wrapper);
							form.css('position', 'relative').append(overlay);
							form.on('submit', function() {
								$('#wfls-token-submit').prop('disabled', true).addClass('disabled');
							});
							$('#wfls-token').focus();

							new $.Zebra_Tooltips($('.wfls-tooltip-trigger'));
						}
					}
					else { //Unexpected response, skip AJAX and process via the regular login flow
						blocker.clickSubmit();
					}
				}
				blocker.unblock();
			},
			error: function(err) {
				if (err.status == 503 || err.status == 403) {
					if ($('.woocommerce').length > 0) {
						if (err.status == 503) {
							showLoginMessage(__('<strong>ERROR</strong>: Login failed with status code 503. Please contact the site administrator.'), 'error');
						}
						else if (err.status == 403) {
							showLoginMessage(__('<strong>ERROR</strong>: Login failed with status code 403. Please contact the site administrator.'), 'error');
						}
						blocker.unblock();
					}
					else {
						window.location.reload(true);
					}
					
					return;
				}
				showLoginMessage(__('<strong>ERROR</strong>: An error was encountered while trying to authenticate. Please try again.'), 'error');
				blocker.unblock();
			}
		});
	};
	
	$(function() {
		//Login
		if (loginLocator.locate()) {
			var loginBlocker = new FormBlocker(loginLocator.getForm(), '#wp-submit,[type=submit][name=login]', true);
			loginBlocker.initialize(function() {
				if (parseInt(WFLSVars.useCAPTCHA)) {
					wfls_init_captcha(function() { wfls_query_ajax(loginBlocker); });
				}
				else {
					wfls_query_ajax(loginBlocker);
				}
			});
		}

		//Registration
		if (registrationLocator.locate() && parseInt(WFLSVars.useCAPTCHA)) {
			var registrationBlocker = new FormBlocker(registrationLocator.getForm(), '[type=submit]');
			registrationBlocker.initialize(function() {
				wfls_init_captcha(
					function() {
						registrationBlocker.release();
						registrationBlocker.clickSubmit();
					},
					registrationLocator.getInput()
				);
			});
		}

		//Passkey
		if (parseInt(WFLSVars.hasPasskeys) && loginLocator.locate()) {
			var loginSubmit = findLoginSubmit(loginLocator.getForm());
			if (loginSubmit.length && !$('#wfls-passkey-container').length) {
				var passkeyContainer = $('<div id="wfls-passkey-container" class="wfls-passkey-container">');
				var passkeyDivider = $('<div class="wfls-passkey-divider" aria-hidden="true">');
				passkeyDivider.append($('<span class="wfls-passkey-divider-label">').text(__('or')));
				var passkeyButton = $('<button type="button" id="wfls-passkey-submit" class="wfls-passkey-submit button button-secondary button-large">');
				var passkeyIcon = $('<span class="wfls-passkey-icon" aria-hidden="true">');
				passkeyIcon.get(0).style.setProperty('--wfls-passkey-icon-url', 'url("' + (WFLSVars.passkeyIconUrl || '') + '")');
				passkeyButton.append(passkeyIcon).append($('<span class="wfls-passkey-label">').text(__('Log In with a Passkey')));
				passkeyContainer.append(passkeyDivider).append(passkeyButton);
				var submitContainer = loginSubmit.closest('p.submit');
				if (submitContainer.length) {
					submitContainer.after(passkeyContainer);
				}
				else {
					loginSubmit.after(passkeyContainer);
				}
			}

			$('#wfls-passkey-submit').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();

				if (!window.PublicKeyCredential || !navigator.credentials || !navigator.credentials.get) {
					if (wfls_passkeys_require_https()) {
						showLoginMessage(__('Passkey login requires HTTPS. Open this site using https:// and try again.'), 'error', true);
						return;
					}
					showLoginMessage(__('This browser does not support passkey login.'), 'error', true);
					return;
				}

				var button = $(this);
				if (button.prop('disabled')) {
					return;
				}

				wfls_start_passkey_login(button);
			});
		}

		var verificationField = $('#wfls-email-verification');
		if (verificationField.length) {
			verificationField.val(WFLSVars.verification || '');
		}
		else {
			var log = getRelevantInputs();
			if (log.length) {
				verificationField = $('<input type="hidden" name="wfls-email-verification" id="wfls-email-verification" />');
				verificationField.val(WFLSVars.verification);
				log.parent().append(verificationField);
			}
		}

		if (parseInt(WFLSVars.useCAPTCHA)) {
			wfls_init_captcha_contact();
		}
	});
})(jQuery);
