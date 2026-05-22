/**
 * Animate Helper
 *
 * Provide a functions to handle animations and transitions gracefully.
 *
 * Author: Diego Versiani
 * Contact: https://diegoversiani.me
 */
( function() {

	// Initialize public methods object
	var animateHelper = {};
	window.AnimateHelper = animateHelper;



	/**
	 * Provide a crossbrowser way to determine which
	 * animationend event is supported by the current browser.
	 * 
	 * Based on the work of:
	 * Jonathan Suh - https://jonsuh.com/blog/detect-the-end-of-css-animations-and-transitions-with-javascript/
	 * David Walsh - https://davidwalsh.name/css-animation-callback
	 *
	 * @return  {String}  The animationend event name
	 */
    var getAnimationEvent = function() {
        var t,
        el = document.createElement("fakeelement");
		var animations = {
			'animation'      : 'animationend',
			'OAnimation'     : 'oAnimationEnd',
			'MozAnimation'   : 'animationend',
			'WebkitAnimation': 'webkitAnimationEnd'
		}

		for (t in animations){
			if (el.style[t] !== undefined){
				return animations[t];
			}
		}

		return 'animationend';
	};



	/**
	 * Play animation class on the element then call callback function
	 *
	 * @param   {Element}  	element         Element to add animation class to
	 * @param   {String}  	animationClass  Animation class name
	 * @param   {Function}  callback        Callback function to run after animation
	 */
	animateHelper.animateThenDo = function( element, animationClass, callback ) {
        // Set event handler
        element.addEventListener( getAnimationEvent(), function finish( e ) {
            // Remove animation class and event listener
            e.target.classList.remove( animationClass );
			e.target.removeEventListener( getAnimationEvent(), finish );
			
			// Do
            if ( typeof callback === 'function' ) callback( e.target, animationClass );
        } );

        // Play animation
        element.classList.add( animationClass );
    };



	/**
	 * Call callback function then play animation class on the element
	 *
	 * @param   {Element}  	element         Element to add animation class to
	 * @param   {String}  	animationClass  Animation class name
	 * @param   {Function}  callback        Callback function to run before animation
	 */
    animateHelper.doThenAnimate = function( element, animationClass, callback ) {
		// Do
		if ( typeof callback === 'function' ) callback( element, animationClass );
        
        // Set event handler
        element.addEventListener( getAnimationEvent(), function finish( e ) {
            // Remove animation class and event listener
            e.target.classList.remove( animationClass );
            e.target.removeEventListener( getAnimationEvent(), finish );
        } );

        // Play animation
        element.classList.add( animationClass );
	};

})();
