jQuery(document).ready(function($){

	function isEmail(email) {
		var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
		return regex.test(email);
	}

	function parse_notice( message, type = 'error' ){
		return xoo_ml_phone_localize.notices[ type + '_placeholder' ].replace( '%s', message );
	}

	if( xoo_ml_phone_localize.operator === 'firebase' ){
		// Initialize Firebase
		firebase.initializeApp(xoo_ml_phone_localize.firebase.config);
	}
	
	class OTPFormHandler{

		constructor( parentFormHandler, $type = 'phone' ){

			this.parentFormHandler 	= parentFormHandler;
			this.$parentForm 		= this.parentFormHandler.$phoneForm;
			this.displayType 		= this.$parentForm.find('input[name="xoo-ml-otp-form-display"]').length ? this.$parentForm.find('input[name="xoo-ml-otp-form-display"]').val() : 'inline_input';
			this.operator 			= xoo_ml_phone_localize.operator;

			this.firebaseAuth 		= false;


			if( this.displayType === 'external_form' ){
				
				this.$otpForm 		= $( xoo_ml_phone_localize.html.otp_form_external ).insertAfter(this.$parentForm).attr( 'data-type', $type );
				this.$noticeCont 	= this.$otpForm.find('.xoo-ml-notice');
			}
			else{ //Inline Input
				if( !this.parentFormHandler.$phoneInput.siblings('.xoo-ml-inline-otp-cont').length ){
					var $insertInlineInputAfter = this.parentFormHandler.$phoneInput.closest('.xoo-aff-input-group').length ? this.parentFormHandler.$phoneInput.closest('.xoo-aff-input-group') : this.parentFormHandler.$phoneInput;
					$( xoo_ml_phone_localize.html.otp_form_inline ).insertAfter( $insertInlineInputAfter );
				}
				this.$otpForm 		= this.$parentForm.find('.xoo-ml-inline-otp-cont');
				this.$noticeCont 	= this.$otpForm.siblings('.xoo-ml-notice');
			}

			
		
			this.$submitBtn 	= this.$otpForm.find('.xoo-ml-otp-submit-btn');
			this.$inputs 		= this.$otpForm.find('.xoo-ml-otp-input');
			this.$resendLink 	= this.$otpForm.find('.xoo-ml-otp-resend-link');
			this.noticeTimout 	= this.resendTimer = false;
			this.customFormData = {}

			
			this.events();

		}

		events(){

			this.$resendLink.on( 'click', { _thisObj: this }, this.resendOTP );
			

			if( this.displayType === 'external_form' ){
				this.$otpForm.find('.xoo-ml-otp-no-change').on( 'click', { _thisObj: this }, this.changeParentInput );
				this.$otpForm.on( 'submit', { _thisObj: this }, this.onSubmit );
				this.$inputs.on( 'keyup', { _thisObj: this }, this.onOTPInputChange );
			}
			else{
				this.$submitBtn.on( 'click', { _thisObj: this }, this.onSubmit );
			}
		}

		onOTPInputChange(event){

			var _thisObj = event.data._thisObj;

			//Switch Input
			if( $(this).val().length === parseInt( $(this).attr('maxlength') ) && $(this).next('input.xoo-ml-otp-input').length !== 0 ){
				$(this).next('input.xoo-ml-otp-input').focus();
			}

			//Backspace is pressed
			if( $(this).val().length === 0 && event.keyCode == 8 && $(this).prev('input.xoo-ml-otp-input').length !== 0 ){
				$(this).prev('input.xoo-ml-otp-input').focus().val('');
			}
		}

		onSubmit(event){

			event.preventDefault();

			var _thisObj = event.data._thisObj;

			if( !_thisObj.validateInputs() || !_thisObj.getOtpValue().length ) return false;

			_thisObj.$submitBtn.addClass('xoo-ml-processing');

			if( _thisObj.operator === 'firebase' ){

				_thisObj.firebaseAuth.confirm( _thisObj.getOtpValue() ).then(function (result) {

				firebase.auth().currentUser.getIdToken( false ).then(function(idToken) {
					_thisObj.verifyOTP( { firebase_idToken: idToken } );
				})

				}).catch(function (error) {
					// User couldn't sign in (bad verification code?)
					_thisObj.verifyOTP( { firebase_error: JSON.stringify( error ) } );
				});

			}else{
				_thisObj.verifyOTP();
			}
		}


		changeParentInput(event){
			var _thisObj = event.data._thisObj;
			_thisObj.$otpForm.hide();
			_thisObj.$parentForm.show();
			_thisObj.$inputs.val('');
		}


		resendOTP(event){

			event.preventDefault();

			var _thisObj = event.data._thisObj;

			_thisObj.startResendTimer();

			var form_data = {
				action: 'xoo_ml_resend_otp',
				'parentFormData': objectifyForm( _thisObj.$parentForm.serializeArray() ),
			}

			_thisObj.$resendLink.addClass('xoo-ml-processing');

			$.ajax({
				url: xoo_ml_phone_localize.adminurl,
				type: 'POST',
				data: form_data,
				success: function(response){

					_thisObj.$resendLink.trigger( 'xoo_ml_otp_resent', [ response, _thisObj ] );

					_thisObj.$resendLink.removeClass('xoo-ml-processing');
					
				}
			});
		}


		validateInputs(){

			var passedValidation = true;

			if( this.displayType === 'inline_input' ){

			}
			else{
				this.$inputs.each( function( index, input ){
					var $input = $(input);
					if( !parseInt( $input.val() ) && parseInt( $input.val() ) !== 0 ){
						$input.focus();
						passedValidation = false;
						return false;
					}
				} );
			}	
			
			return passedValidation;
		}

		setPhoneData(data){
			this.$otpForm.find('.xoo-ml-otp-no-txt').html( data.otp_txt );
			this.parentFormHandler.verifiedPHone = false;
			this.activeNumber 					= data.phone_no;
			this.activeCode   					= data.phone_code;
		}

		onSuccess(){
			this.$otpForm.hide();
			this.$inputs.val('');
			this.$parentForm.show();
		}

		startResendTimer(){
			var _thisObj 			= this,
				$cont 			= this.$otpForm.find('.xoo-ml-otp-resend'),
				$resendLink 	= $cont.find('.xoo-ml-otp-resend-link'),
				$timer 			= $cont.find('.xoo-ml-otp-resend-timer'),
				resendTime 		= parseInt( xoo_ml_phone_localize.resend_wait );

			if( resendTime === 0 ) return;

			$resendLink.addClass('xoo-ml-disabled');

			clearInterval( this.resendTimer );

			this.resendTimer = setInterval(function(){
				$timer.html('('+resendTime+')');
				if( resendTime <= 0 ){
					clearInterval( _thisObj.resendTimer );
					$resendLink.removeClass('xoo-ml-disabled');
					$timer.html('');
				}
				resendTime--;
			},1000) 
		}

		showNotice(notice){
			var _thisObj = this;
			clearTimeout(this.noticeTimout);
			this.$noticeCont.html( notice ).show();
			this.noticeTimout = setTimeout(function(){
				_thisObj.$noticeCont.hide();
			},4000)
		}

		onOTPSent(response){

			var _thisObj = this;
			_thisObj.$otpForm.show();
			
			setTimeout(function(){
				_thisObj.$inputs.first().trigger('click');
				_thisObj.$inputs.first().focus();
				_thisObj.$inputs.first().attr('autofocus', true);
			}, 500)
			
			_thisObj.startResendTimer();
			_thisObj.setPhoneData( response );

			if( _thisObj.displayType === 'inline_input' ){
				_thisObj.parentFormHandler.$inlineVerifyBtn.hide();
			}
			else{
				_thisObj.$parentForm.hide();
			}

		}

		verifyOTP(data){

			var _thisObj = this;

			var form_data = $.extend( {
				'otp': _thisObj.getOtpValue(),
				'token': _thisObj.$parentForm.find( 'input[name="xoo-ml-form-token"]' ).val(),
				'action': 'xoo_ml_otp_form_submit',
				'parentFormData': objectifyForm( _thisObj.$parentForm.serializeArray() ),
			}, data );


			$.ajax({
				url: xoo_ml_phone_localize.adminurl,
				type: 'POST',
				data: form_data,
				success: function(response){
					_thisObj.$submitBtn.removeClass('xoo-ml-processing');

					if( response.notice ){
						_thisObj.showNotice( response.notice );
					}

					if( response.error === 0 ){
						_thisObj.onSuccess();
						console.log(_thisObj.$otpForm);
						_thisObj.$otpForm.trigger( 'xoo_ml_on_otp_success', [response] );
					}
				}
			});
		}

		validateFormSubmit(){

			if( this.validateInputs() && this.getOtpValue().length ){
				this.$submitBtn.addClass('xoo-ml-processing');
				return true;
			}
		}

		getOtpValue(){
			var otp = '';
			this.$inputs.each( function( index, input ){
				otp += $(input).val();
			});
			return otp;
		}

	}


	class PhoneOTPFormHandler extends OTPFormHandler{


		constructor( parentFormHandler ){

			super( parentFormHandler, 'phone' );
			
			this.activeNumber = this.activeCode = '';

			if( parentFormHandler.$phoneInput ){
				parentFormHandler.$phoneInput.parent().css('position', 'relative');
			}

		}

	}

	class EmailOTPFormHandler extends OTPFormHandler{


		constructor( parentFormHandler ){

			super( parentFormHandler, 'email' );

			this.operator = null;

		}
	}


	var $i = 0;

	class PhoneFormHandler{

		constructor( $phoneForm ){

			this.$phoneForm 				= $phoneForm;
			this.$phoneInput 				= this.$phoneForm.find( '.xoo-ml-phone-input' );
			this.$phoneCode 				= this.$phoneForm.find( '.xoo-ml-phone-cc' );
			this.formType 					= this.$phoneForm.find('input[name="xoo-ml-form-type"]').length ? this.$phoneForm.find('input[name="xoo-ml-form-type"]').val() : ''
			this.$submit_btn 				= this.$phoneForm.find('button[type="submit"]');
			this.$easyLoginCont 			= this.$phoneForm.closest('.xoo-el-form-container');

			this.otpFormHandler 			= new PhoneOTPFormHandler( this );

			if( this.otpFormHandler.displayType === 'inline_input' ){
				$( xoo_ml_phone_localize.inline_otp_verify_btn ).insertAfter( this.$phoneInput );
				this.$inlineVerifyBtn 	= this.$phoneInput.siblings('.xoo-ml-inline-verify');
				this.$noticeCont 		= this.otpFormHandler.$noticeCont;
			}
			else{
				

				if( this.$phoneForm.siblings( '.xoo-el-notice' ).length ){
					this.$noticeCont = this.$phoneForm.siblings('.xoo-el-notice');
				}
				else if( this.$phoneForm.siblings('.xoo-ml-notice' ).length ) {
					this.$noticeCont = this.$phoneForm.siblings( '.xoo-ml-notice' );
				}
				else{
					$( '<div class="xoo-ml-notice"></div>' ).insertBefore(this.$phoneForm); //Notice element
				}
			}

			this.adjustPositions();

		}

		events(){
			if( this.otpFormHandler.displayType === 'inline_input' ){
				this.$inlineVerifyBtn.on( 'click', { _thisObj: this }, this.formSubmit );
				this.$phoneInput.add( this.$phoneCode ).on( 'keyup change', { _thisObj: this }, this.onNumberChange );
			}
			this.$phoneInput.add( this.$phoneCode ).on( 'keyup change', { _thisObj: this }, this.cleanNumber );
			this.$phoneForm.on( 'submit', { _thisObj: this }, this.formSubmit );
			this.otpFormHandler.$resendLink.on( 'xoo_ml_otp_resent', { _thisObj: this}, this.onOTPResent );
		}

		onOTPResent( event, response ){

			var _thisObj = event.data._thisObj;

			if( xoo_ml_phone_localize.operator === 'firebase' && response.otp_sent && response.phone_code && response.phone_no ){
				_thisObj.sendOTPUsingFirebase( response );
			}
			else{
				_thisObj.OTPrequested( response );
			}
		}


		adjustPositions(){

			var _thisObj 	= this,
				inputHeight = this.$phoneInput.innerHeight();

			if( inputHeight <= 10 ) return;

			if( this.$inlineVerifyBtn ){
				this.$inlineVerifyBtn.css(
					'top',
					this.$phoneInput.position().top + ( this.$phoneInput.innerHeight() / 2 ) - ( this.$inlineVerifyBtn.innerHeight() ? this.$inlineVerifyBtn.innerHeight() / 2 : 10 )
				);
			}
			

			setTimeout(function(){
				
				if( _thisObj.$phoneForm.is(':hidden') ){
					_thisObj.$phoneForm.show();
					var hideForm = true;
				}

				if( _thisObj.$phoneCode.length && _thisObj.$phoneInput.innerHeight() ){

					var phoneCodeCSS = {
						'height': _thisObj.$phoneInput.innerHeight()+'px',
						'line-height': _thisObj.$phoneInput.innerHeight()+'px',
					}

					_thisObj.$phoneCode.css( phoneCodeCSS );

					if( _thisObj.$phoneCode.siblings('.select2').length ){
						_thisObj.$phoneCode.siblings('.select2').css( phoneCodeCSS );
					}

				}

				if( hideForm ){
					_thisObj.$phoneForm.hide();
				}

			},200)
			
		}


		cleanNumber(event){

			var _thisObj = event.data._thisObj;

			if( isNaN( _thisObj.getPhoneNumber('number') ) ){
				_thisObj.$phoneInput.val('');
			}

			//Remove 0 from front
			if( xoo_ml_phone_localize.del_0 === "yes" && _thisObj.getPhoneNumber('number') ){
				_thisObj.$phoneInput.val( parseInt( _thisObj.getPhoneNumber('number') ) );
			}

		}


		onNumberChange( event ){

			var _thisObj = event.data._thisObj;

 			// CHANGE: remove default verification status icon update (replaced with custom logic in checkout-mobile-login-woocommerce-premium.js)

			_thisObj.otpFormHandler.$otpForm.hide();
			_thisObj.$noticeCont.hide();
		}


		sendFormData(){

			var _thisObj 		= this,
				form_data		= this.$phoneForm.serialize()+'&action=xoo_ml_request_otp';

			if( _thisObj.$submit_btn.length && _thisObj.$submit_btn.attr('name') ){
				form_data = form_data + '&' + _thisObj.$submit_btn.attr('name') + '=' + _thisObj.$submit_btn.val();
			}

			_thisObj.$submit_btn.addClass('xoo-ml-processing');

			if( _thisObj.$inlineVerifyBtn ){
				_thisObj.$inlineVerifyBtn.addClass('xoo-ml-processing');
			}

			$.ajax({
				url: xoo_ml_phone_localize.adminurl,
				type: 'POST',
				data: form_data,
				success: function(response){
					if( xoo_ml_phone_localize.operator === 'firebase' && response.otp_sent && response.phone_code && response.phone_no ){
						_thisObj.sendOTPUsingFirebase( response );
					}
					else{
						_thisObj.OTPrequested( response );
					}


				},
				complete: function(){
					
				}
			});
		}

		OTPrequested(response){

			if( response.notice ){
				this.$noticeCont.html( response.notice ).show();
			}
			//Display otp form
			if( response.otp_sent ){
				this.otpFormHandler.onOTPSent( response );
			}

			this.$phoneForm.trigger( 'xoo_ml_otp_requested', [ response ] );

			this.$submit_btn.removeClass('xoo-ml-processing');

			if( this.$inlineVerifyBtn ){
				this.$inlineVerifyBtn.removeClass('xoo-ml-processing');
			}
		}


		sendOTPUsingFirebase(response){

			var _thisObj = this;

			if( xoo_ml_phone_localize.operator !== 'firebase'  ) return;

			if( !window.recaptchaVerifier ){
				$( '<div class="xoo-ml-recaptcha"></div>' ).insertBefore( _thisObj.$phoneForm );
				//Firebase
				window.recaptchaVerifier = new firebase.auth.RecaptchaVerifier( _thisObj.$phoneForm.siblings('.xoo-ml-recaptcha').get(0), {
					'size': 'invisible',
					'callback': function(response) {

					}
				});
			}

			var phoneNumber = response.phone_code.toString() + response.phone_no.toString(),
				appVerifier = window.recaptchaVerifier;

			firebase.auth().signInWithPhoneNumber(phoneNumber, appVerifier)
				.then(function ( confirmationResult ) {
				// SMS sent. Prompt user to type the code from the message, then sign the
				// user in with confirmationResult.confirm(code).
				_thisObj.otpFormHandler.firebaseAuth = confirmationResult;
				_thisObj.OTPrequested(response);
			}).catch(function (error) {

				// Error; SMS not sent
				response.otp_sent 	= 0;
				response.notice 	= error.message ? parse_notice( error.message ) : xoo_ml_phone_localize.notices.try_later;
				_thisObj.OTPrequested(response);

				console.log(error);

			});

		}


		getPhoneNumber( $only ){

			var phoneNumber = '',
				code 		= this.$phoneCode.length && this.$phoneCode.val() ? this.$phoneCode.val().trim().toString() : '',
				number 		= this.$phoneInput.val().toString().trim();

			if( $only === 'code' ){
				return code;
			}
			else if( $only === 'number' ){
				return number;
			}
			else{
				return code+number;
			}
		}


		getOTPFormPreviousState(){

			//If requested for changing phone number & same number is put again.
	 		if( ( !this.$phoneCode.length || this.otpFormHandler.activeCode ===  this.getPhoneNumber('code') ) && this.otpFormHandler.activeNumber ===  this.getPhoneNumber('number') ){
	 			this.otpFormHandler.$otpForm.show();
	 			if( this.otpFormHandler.displayType === 'external_form' ){
	 				this.$phoneForm.hide();
	 			}
	 			else{
	 				this.$inlineVerifyBtn.hide();
	 			}
	 			
	 			return true;
	 		}

	 		return false;
		}
	}



	class RegisterPhoneFormHandler extends PhoneFormHandler{

		constructor( $phoneForm ){

			super( $phoneForm );

			this.$phoneForm 	= $phoneForm;
			this.$changePhone 	= this.$phoneForm.find('.xoo-ml-reg-phone-change');
			this.verifiedPHone 	= false;

			this.registerEvents();

			//If this is an update form
			if( this.getPhoneNumber( 'number' ) && this.formType === 'update_user' ){
				this.verifiedPHone = this.initialPhone = this.getPhoneNumber();
				this.$phoneInput.trigger('change');
			}
		}

		registerEvents(){
			this.otpFormHandler.$otpForm.on( 'xoo_ml_on_otp_success', { _thisObj: this }, this.onOtpSuccess );
			this.$changePhone.on( 'click', { _thisObj: this }, this.changePhone );
			this.events();
		}


		fieldsValidation(){

			var	$phoneForm 			= this.$phoneForm,
				error_string 		= ''; 

			if( isNaN( this.getPhoneNumber( 'number' ) ) ){
				error_string 		= xoo_ml_phone_localize.notices.invalid_phone;
			}
				
			//Validate registration form fields [ wocommerce ]
			if( this.otpFormHandler.displayType === 'external_form' && $phoneForm.find('input[name="woocommerce-register-nonce"]').length ){

				var $emailField 	= $phoneForm.find('input[name="email"]'),
					$passwordField 	= $phoneForm.find('input[name="password"]');

				//If email field is empty
				if( $emailField.length && !$emailField.val() ){
					error_string = xoo_ml_phone_localize.notices.empty_email;
				}

				if( $passwordField.length && !$passwordField.val() ){
					error_string = xoo_ml_phone_localize.notices.empty_password;
				}

			}


			if( error_string ){
				this.$noticeCont.html( error_string ).show();
				return false;
			}

			return true;

		}

		formSubmit( event ){

			// CHANGE: Add bail statement to prevent OTP from being sent on checkout form submit
			if ( event.target.matches( 'form.checkout' ) ) { return; }

			var _thisObj = event.data._thisObj;

			_thisObj.$noticeCont.hide();

			$('.xoo-el-notice').hide();

			//If number is optional
			if( _thisObj.getPhoneNumber('number').length === 0 && xoo_ml_phone_localize.show_phone !== 'required' ){
				return;
			}

			//Check if OTP form exists & number is already verified 
			if( !_thisObj.otpFormHandler || _thisObj.verifiedPHone === _thisObj.getPhoneNumber() ) return;

			event.preventDefault();
	 		event.stopImmediatePropagation();

			//CHANGE: Remove scroll to top
			
	 		if( !_thisObj.fieldsValidation() ) return;

	 		//If requested for changing phone number & same number is not put again.
	 		if( !_thisObj.getOTPFormPreviousState() ) {
				_thisObj.sendFormData();
			}
			else{
				if( _thisObj.otpFormHandler.displayType === 'inline_input' ){
					_thisObj.$noticeCont.html( xoo_ml_phone_localize.notices.verify_error ).show();
				}
			}

			
		}


		onOtpSuccess( event, response ){

			var _thisObj 		= event.data._thisObj,
				otpFormHandler 	= _thisObj.otpFormHandler;

			_thisObj.verifiedPHone = _thisObj.initialPhone = _thisObj.getPhoneNumber();

			if( otpFormHandler.displayType === "inline_input" ){
				_thisObj.$inlineVerifyBtn.html( xoo_ml_phone_localize.strings.verified ).show();
				_thisObj.$phoneInput.trigger('change');
			}
			else{
				_thisObj.$phoneInput
					.prop('readonly', true)
					.addClass( 'xoo-ml-disabled' );
				_thisObj.$changePhone.show();
				if( xoo_ml_phone_localize.auto_submit_reg === "yes" ){
					_thisObj.$phoneForm.find('[type="submit"]').trigger('click');
				}
			}


			if( response.notice ){
				_thisObj.$noticeCont.html( response.notice ).show();
			}

		}

		changePhone( event ){
			$(this).hide();
			event.data._thisObj.$phoneInput.prop( 'readonly', false ).focus();
		}
	}

	// CHANGE: Transform phone field initialization into a function
	var initPhoneField = function() {
		$('input[name="xoo-ml-reg-phone"]').each( function( key, form ){

			var $formType = $(this).parents('form').find('input[name="xoo-ml-form-type"]');
			// CHANGE: Add new variable
			var $formInput = $(this).parents('form').find('.xoo-ml-inline-verify');

			// CHANGE: Add additional condition to check if the form already has the verification indicator
			if( ! $formInput.length && $formType.length && $formType.val() !== 'login_with_otp' ){

				new RegisterPhoneFormHandler( $(this).closest('form') );
			}
	
		} );
	}

	// CHANGE: Add phone field initialization on checkout update event
	initPhoneField();
	$( document.body ).on( 'updated_checkout', function() {
		initPhoneField();
	});

	class LoginPhoneFormHandler extends PhoneFormHandler{

		constructor( $loginPhoneForm ){

			super( $loginPhoneForm );

			this.$loginForm 	= $loginPhoneForm;

		}


		OTPrequested( response ){

			if( response.e_code === 'exists' && this.$easyLoginCont.length ){

				var $numberInput = this.$easyLoginCont.find('input[name="xoo-ml-reg-phone"]'),
					$codeInput 	 = this.$easyLoginCont.find('[name="xoo-ml-reg-phone-cc"]');

				if( !$numberInput.length ) return;

				$numberInput.val( this.getPhoneNumber('number') );

				if( $codeInput.length ){
					$codeInput.val( this.getPhoneNumber('code') ).trigger('change');
				}

				this.$easyLoginCont.find('.xoo-el-forcereg-tgr').trigger('click');
				this.$easyLoginCont.find('.xoo-el-notice').html(response.notice).show();
			}

			super.OTPrequested.call( this, response );

		}


		formSubmit( event ){

			var _thisObj = event.data._thisObj;

			event.preventDefault();
			event.stopImmediatePropagation();
			
			_thisObj.processFormSubmit();

		}

		processFormSubmit(){

			this.$noticeCont.hide();

			$('.xoo-el-notice').hide();

			if( !this.getOTPFormPreviousState()  ){
				this.sendFormData();
			}

		}

		onOtpSuccess(){

		}



	}

	class LoginForm extends LoginPhoneFormHandler{

		constructor( $loginPhoneForm, $parentForm ){

			super( $loginPhoneForm );

			this.$parentForm 		= $parentForm;
			this.$parentOTPLoginBtn = this.$parentForm.find('.xoo-ml-open-lwo-btn');
			this.$loginOTPBtn 		= this.$loginForm.find( '.xoo-ml-login-otp-btn' );

			this.loginEvents();
		}

		loginEvents(){
			this.otpFormHandler.$otpForm.on( 'xoo_ml_on_otp_success', { _thisObj: this }, this.onOtpSuccess );
			this.$parentOTPLoginBtn.on( 'click', { _thisObj: this }, this.openLoginForm );
			//Back to parent form
			this.$loginForm.find('.xoo-ml-low-back').on( 'click', { _thisObj: this }, this.openParentLoginForm );
			this.events();
		}

		openParentLoginForm( event ){

			var _thisObj = event.data._thisObj;

			_thisObj.$parentForm.show();
			_thisObj.$loginForm.hide();
			_thisObj.$noticeCont.hide();

		}

		onOtpSuccess( event, response ){

			var _thisObj = event.data._thisObj;

			if( response.notice ){
				_thisObj.$noticeCont.html( response.notice ).show();
			}

			if( response.redirect !== undefined ){
				var redirect = _thisObj.$parentForm.find('input[name="xoo_el_redirect"]').length ? _thisObj.$parentForm.find('input[name="xoo_el_redirect"]').val() : response.redirect;
				window.location = redirect;
			}

		}


		openLoginForm( event, response ){

			var _thisObj = event.data._thisObj;

			_thisObj.$loginForm.show();
			_thisObj.$parentForm.hide();
			$('.xoo-el-notice').hide();

		}

	}


	$('.xoo-ml-open-lwo-btn').each( function( key, el ){

		var $parentForm = $(this).closest('form');

		//attach login with otp form
		$('<form class="xoo-lwo-form"></form>').insertAfter( $parentForm );

		var $loginForm = $parentForm.next('.xoo-lwo-form');
		
		var formHTMLPlaceholder = $parentForm.find('.xoo-ml-lwo-form-placeholder');

		//attach form elements
		$loginForm.append( formHTMLPlaceholder.html() );

		formHTMLPlaceholder.remove();

		//If otp login form is displayed first
		if( xoo_ml_phone_localize.login_first === "yes" ){
			$parentForm.hide();
		}
		else{
			$loginForm.hide();
		}

		new LoginForm( $loginForm, $parentForm );

	} );

	
	class EmailFormHandler{

		constructor( $emailForm ){

			this.$form 				= this.$phoneForm = $emailForm;
			this.$input 			= this.$form.find('input[name="xoo-ml-email-input"]');
			this.$easyLoginSection 	= this.$form.closest('.xoo-el-section');
			this.$easyLoginCont 	= this.$easyLoginSection.closest('.xoo-el-form-container');
			this.$parentForm 		= this.$easyLoginSection.find('.xoo-el-action-form');


			this.$submit_btn 		= this.$form.find('button[type="submit"]');

			this.otpFormHandler 	= new EmailOTPFormHandler( this );

			if( this.$form.siblings( '.xoo-el-notice' ).length ){
				this.$noticeCont = this.$form.siblings('.xoo-el-notice');
			}
			else if( this.$form.siblings('.xoo-ml-notice' ).length ) {
				this.$noticeCont = this.$form.siblings( '.xoo-ml-notice' );
			}

			this.emailFormEvents();

		}

		emailFormEvents(){

			this.$form.on( 'submit', { _thisObj: this }, this.formSubmit );

			this.otpFormHandler.$otpForm.on( 'xoo_ml_on_otp_success', { _thisObj: this }, this.onOtpSuccess );

			//Back to parent form
			this.$form.find('.xoo-ml-email-goback').on('click', { _thisObj: this }, this.openParentLoginForm);

			this.otpFormHandler.$resendLink.on( 'xoo_ml_otp_resent', { _thisObj: this}, this.onOTPResent );
		}

		onOTPResent( event, response ){
			var _thisObj = event.data._thisObj;
			_thisObj.OTPrequested(response);
		}

		openParentLoginForm(event){

			var _thisObj = event.data._thisObj;

			_thisObj.$parentForm.show();
			_thisObj.$form.hide();
			_thisObj.$noticeCont.hide();
		}

		onOtpSuccess( event, response ){

			var _thisObj = event.data._thisObj;

			if( response.notice ){
				_thisObj.$noticeCont.html( response.notice ).show();
			}

			if( response.redirect !== undefined ){
				var redirect = _thisObj.$parentForm.length && _thisObj.$parentForm.find('input[name="xoo_el_redirect"]').length ? _thisObj.$parentForm.find('input[name="xoo_el_redirect"]').val() : response.redirect;
				window.location = redirect;
			}

		}

		getEmailValue(){
			return this.$input.val();
		}


		formSubmit( event ){
			event.preventDefault();
			var _thisObj = event.data._thisObj;
			_thisObj.sendOTP();
		}

		sendOTP(){

			var _thisObj 	= this,
				form_data 	= this.$form.serialize()+'&action=xoo_ml_email_request_otp';

			_thisObj.$submit_btn.addClass('xoo-ml-processing');

			$.ajax({
				url: xoo_ml_phone_localize.adminurl,
				type: 'POST',
				data: form_data,
				success: function(response){
					_thisObj.OTPrequested( response );
				},
				complete: function(){
					
				}
			});
		}


		OTPrequested( response ){

			if( false && response.e_code === 'no-user' && this.$easyLoginCont.length ){
				var $registerEmailInput = this.$easyLoginCont.find('input[name="xoo_el_reg_email"]');
				if( $registerEmailInput.length ){
					$registerEmailInput.val(this.getEmailValue());
					this.$easyLoginCont.find('.xoo-el-forcereg-tgr').trigger('click');
					this.$easyLoginCont.find('.xoo-el-notice').html(response.notice).show();
				}
			}

			if( response.notice ){
				this.$noticeCont.html( response.notice ).show();
			}
		
			this.$form.trigger( 'xoo_ml_otp_requested', [ response ] );
			
			//Display otp form
			if( response.otp_sent ){
				this.otpFormHandler.onOTPSent( response );
			}

			this.$submit_btn.removeClass('xoo-ml-processing');
		}

	}

	$('input[name="xoo-ml-email-input"]').each(function( key, el ){
		if( !$(this).closest('form.xoo-el-form-single').length ){
			new EmailFormHandler( $(this).closest('form') );
		}
	});

	$( 'body' ).on( 'click', '.xoo-ml-open-email-otp-form', function(){

		var $parentForm = $(this).closest('form'),
			$emailForm;

		if( $parentForm.length ){
			$parentForm.hide();
		}
		else{
			$emailForm = $(this).siblings('form.xoo-ml-email-form');
		}

		var $emailForm = $(this).siblings('form.xoo-ml-email-form');

		if( !$emailForm.length && $(this).parents('.xoo-el-form-container').length ){
			$emailForm = $(this).parents('.xoo-el-form-container').find('form.xoo-ml-email-form');
		}

		$emailForm.show();

	} )



	class SinglePatternFormHandler{

		static loginWithEmailOTP 	= xoo_ml_phone_localize.login_with_email_otp === 'yes';
		static loginWithPhoneOTP 	= xoo_ml_phone_localize.login_with_phone_otp === 'yes';
		static loginWithPassword 	= xoo_ml_phone_localize.login_with_password === 'yes';
		static autoSendOTPEmail 	= this.loginWithEmailOTP && ( !this.loginWithPassword || xoo_ml_phone_localize.single_otpauto === 'email' || xoo_ml_phone_localize.single_otpauto === 'em_phone' );
		static autoSendOTPhone 		= this.loginWithPhoneOTP && ( !this.loginWithPassword || xoo_ml_phone_localize.single_otpauto === 'phone' || xoo_ml_phone_localize.single_otpauto === 'em_phone' );

		constructor( $form ){

			this.loginPhoneFormHandler 	= new LoginPhoneFormHandler( $form );

			this.$emailInput 			= $form.find('.xoo-ml-email-input');

			if( this.$emailInput.length ){

				this.emailFormHandler = new EmailFormHandler( $form );

				this.$emailCont  	= $form.find('.xoo-ml-eminput-cont');
				this.$emailInput 	= $form.find('.xoo-ml-email-input');
			}


			this.$singleForm 			= $form;
			this.$userInput 			= $form.find('input[name="xoo-el-sing-user"]');
			this.$easyLoginCont 		= $form.closest('.xoo-el-form-container');
			this.$phoneCont 			= $form.find('.xoo-ml-phinput-cont');
			this.$phoneInput 			= $form.find('input.xoo-ml-phone-input');
			this.$cc  					= $form.find('select.xoo-ml-phone-cc');
			this.$noticeCont 			= $form.siblings('.xoo-el-notice');

			this.$fieldsCont 			= $form.find('.xoo-el-sing-fields');
			this.activeField 			= 'username';

			this.$fieldsCont.attr( 'data-active', this.activeField );

			this.$lwoBtn 				= $form.find('button.xoo-el-single-otp-btn'); 

			this.singleFormEvents();

		}

		singleFormEvents(){

			if( this.$userInput.length ){
				this.$userInput.on( 'keyup', { _thisObj: this }, this.userInputChange );
			}
			
			this.$phoneInput.on( 'keyup', { _thisObj: this }, this.phoneInputChange );

			this.$emailInput.on( 'keyup', { _thisObj: this }, this.emailInputChange );

			this.loginPhoneFormHandler.otpFormHandler.$otpForm.on( 'xoo_ml_on_otp_success', { _thisObj: this }, this.onOtpSuccess );

			this.loginPhoneFormHandler.events();

			this.loginPhoneFormHandler.$phoneForm.off( 'submit' );

			this.$singleForm.on( 'submit', { _thisObj: this }, this.formSubmit );

			this.$singleForm.on( 'xoo_ml_otp_requested', { _thisObj: this }, this.onOTPRequest );

			this.$cc.on( 'change', { _thisObj: this}, this.changeRegCC );


			this.$singleForm.on( 'reset', { _thisObj: this }, this.resetToDefault );

		}

		resetToDefault( event ){
			var _thisObj = event.data._thisObj;
			_thisObj.$userInput.closest('.xoo-aff-group').show();
			_thisObj.$phoneCont.add( _thisObj.$emailCont ).detach();
		}

		changeRegCC(event){

			var _thisObj = event.data._thisObj,
				$regForm = _thisObj.$easyLoginCont.find('form.xoo-el-form-register');
			
			if( $regForm.length ){
				$regForm.find('.xoo-ml-phone-cc').val( $(this).val() ).trigger('change');
			}
		}


		onOTPRequest( event ){

			var _thisObj = event.data._thisObj;
			_thisObj.$lwoBtn.removeClass('xoo-ml-processing');
			
		}


		onOtpSuccess( event, response ){

			var _thisObj = event.data._thisObj;

			if( response.notice ){
				_thisObj.$noticeCont.html( response.notice ).show();
			}

			if( response.redirect !== undefined ){
				window.location = response.redirect;
			}
			
		}


		formSubmit(event){

			event.preventDefault();

			var _thisObj 		= event.data._thisObj,
				isLwoBtnClick 	= $(event.originalEvent.submitter).hasClass('xoo-el-single-otp-btn');

			if( isLwoBtnClick ){
				_thisObj.$lwoBtn.addClass('xoo-ml-processing');
			}

			_thisObj.$noticeCont.hide();

			if( _thisObj.activeField === 'phone' && ( _thisObj.autoSendOTPhone || isLwoBtnClick ) ){

				//Set redirect values
				_thisObj.$singleForm.find( 'input[name="redirect"]').val( _thisObj.$easyLoginCont.find('.xoo-el-form-login input[name="xoo_el_redirect"]').val() );

				LoginPhoneFormHandler.prototype.processFormSubmit.call( _thisObj.loginPhoneFormHandler );

				event.stopImmediatePropagation();
				
			}
			else if( _thisObj.activeField === 'email' && ( _thisObj.autoSendOTPEmail || isLwoBtnClick ) ){
				_thisObj.emailFormHandler.sendOTP();
				event.stopImmediatePropagation();
			}
			else{

				//Not sending OTP, process single form normally by main easylogin plugin.

				var userInputVal = _thisObj.$userInput.val();

				if( _thisObj.activeField === 'email' ){
					userInputVal = _thisObj.$emailInput.val();
				}
				else if( _thisObj.activeField === 'phone' ){
					userInputVal = _thisObj.loginPhoneFormHandler.getPhoneNumber();
					_thisObj.$singleForm.find('input[name="_xoo_ml_phone_number"]').val( _thisObj.loginPhoneFormHandler.getPhoneNumber('number') );
				}

				_thisObj.$userInput.val(userInputVal);
				_thisObj.$singleForm.find('input[name="_xoo_ml_active_input"]').val( _thisObj.activeField );

				_thisObj.$emailCont.detach();
				_thisObj.$phoneCont.detach();

				setTimeout(function(){
					_thisObj.reattach('email');
					_thisObj.reattach('phone');
				},10);
			}


		}

		reattach( $type = 'email' ){

			var $cont = $type === 'email' ? this.$emailCont : this.$phoneCont;

			if( !$cont.parent().length ){ // check if phone field exist
				this.$fieldsCont.prepend( $cont );
			}

		}

		userInputChange(event){

			var _thisObj 	= event.data._thisObj,
				inputVal 	= $(this).val();

			_thisObj.$lwoBtn.hide();

			//Show phone input field
			if( _thisObj.$phoneCont.length && inputVal.length > 3 && !isNaN( inputVal ) ){

				_thisObj.reattach( 'phone' );

				_thisObj.$phoneCont.show();
				_thisObj.$phoneInput.val(inputVal).focus();

				_thisObj.$userInput.closest('.xoo-aff-group').hide();
				_thisObj.$emailCont.detach();

				_thisObj.activeField = 'phone';


				if( SinglePatternFormHandler.loginWithPhoneOTP && !SinglePatternFormHandler.autoSendOTPhone ){
					_thisObj.$lwoBtn.show();
				}

			}
			else if( isEmail( inputVal ) ){

				_thisObj.reattach( 'email' );

				_thisObj.$emailCont.show();
				_thisObj.emailFormHandler.$input.val(inputVal).focus();

				_thisObj.$userInput.closest('.xoo-aff-group').hide();
				_thisObj.$phoneCont.detach();

				_thisObj.activeField = 'email';

				if( SinglePatternFormHandler.loginWithEmailOTP && !SinglePatternFormHandler.autoSendOTPEmail ){
					_thisObj.$lwoBtn.show();
				}

			}
			else{
				_thisObj.activeField = 'username';
			}

			_thisObj.$fieldsCont.attr( 'data-active', _thisObj.activeField );

		}


		phoneInputChange = function(event){

			var _thisObj 	= event.data._thisObj,
				inputVal 	= $(this).val();

			if( !inputVal.length || isNaN( inputVal ) ){
				_thisObj.$phoneInput.val('');
				_thisObj.$phoneCont.hide();
				_thisObj.$userInput.closest('.xoo-aff-group').show();
				_thisObj.$userInput.val(inputVal).focus();
			}

			if( isNaN( inputVal ) ){
				event.stopImmediatePropagation();
			}

		}


		emailInputChange = function(event){

			var _thisObj 	= event.data._thisObj,
				inputVal 	= $(this).val();


			if( !isEmail(inputVal) ){
				_thisObj.$emailCont.hide();
				_thisObj.$emailInput.val('');
				_thisObj.$userInput.closest('.xoo-aff-group').show();
				_thisObj.$userInput.val(inputVal).focus();
				event.stopImmediatePropagation();
			}
		}
	}



	$( 'form.xoo-el-form-single' ).each( function( key, el ){
		new SinglePatternFormHandler( $(el) );
	} )



	//converts serializeArray to json object
	function objectifyForm(formArray) {//serialize data function

	  var returnArray = {};
	  for (var i = 0; i < formArray.length; i++){
	    returnArray[formArray[i]['name']] = formArray[i]['value'];
	  }
	  return returnArray;
	}


	$( document.body ).on('xoo_el_form_toggled', function( e, formType, containerObj ){

		var $container = containerObj.$container;

		$container.find('.xoo-ml-notice').hide();

		var lwoForm 		= $container.find('.xoo-lwo-form'),
			lwoEmail 		= $container.find('.xoo-ml-email-form'),
			parentLoginForm = $container.find('.xoo-el-form-login');

		if( lwoForm.length ){
			lwoEmail.hide();
		}		

		//If login with OTP form is to be displayed first.
		if( parentLoginForm.length ){
			if( xoo_ml_phone_localize.login_first === "yes" ){

				if( lwoForm.length ){
					lwoForm.show();
				}
				else if( lwoEmail.length ){
					lwoEmail.show();
				}
				
				parentLoginForm.hide();
			}
			else{
				lwoForm.hide();
				parentLoginForm.show();
			}
		}

		$otpForm = $container.find( '.xoo-ml-otp-form' ); 
		if( $otpForm.length ){
			$otpForm.hide();
		}
	})

	if( $.fn.select2 ){

		function formatState (state) {

			if (!state.id) {
				return state.text;
			}

			var cc = state.element.getAttribute('data-cc');
				cc = cc ? cc.toLowerCase() : cc;

			var $state = $( '<div class="xoo-ml-flag-cont"><span class="flag ' + cc +'"></span>' + '<span>' + state.text + '</span></div>' );

			return $state;

		};

		// CHANGE: Transform 'Select2' field initialization into a function
		var initSelect2Fields = function() {
			$('select.xoo-ml-phone-cc, select.xoo-aff-phone_code').each(function( key, el ){
				// CHANGE: Add bail statement to prevent reinitialization of 'Select2' fields
				if ( $(el).hasClass( 'select2-hidden-accessible' ) ) { return; }

				$(el).select2({ templateResult: formatState, templateSelection: formatState });
			});
		}

		// CHANGE: Add 'Select2' field initialization on checkout update event
		initSelect2Fields();
		$( document.body ).on( 'updated_checkout', function() {
			initSelect2Fields();
		});

	}

	$('.xoo-ml-inline-otp-cont').on('xoo_ml_on_otp_success', function( event, response ){
		var $form = $(this).parents('form');
		if( !$form.length || !$form.find('input[name="gform_submit"]').length ) return;
		window[ 'gf_submitting_'+$form.find('input[name="gform_submit"]').val() ] = 0;
	})


	if( xoo_ml_phone_localize.login_with_password !== 'yes' ){
		$('button.xoo-ml-low-back, button.xoo-ml-email-goback').hide();
	}

})