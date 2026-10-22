/**
 * Sync Getting started checklist completion state on the Dashboard settings tab.
 *
 * DEPENDS ON:
 * - FCUtils // Optional. Settings merge.
 */

( function ( root, factory ) {
	if ( typeof define === 'function' && define.amd ) {
		define( [], factory( root ) );
	}
	else if ( typeof exports === 'object' ) {
		module.exports = factory( root );
	}
	else {
		root.FCAdminSettingsSetupChecklist = factory( root );
	}
} )( typeof global !== 'undefined' ? global : this.window || this.global, function ( root ) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = {};
	var _lastKnownState = {};
	var _syncRequestId = 0;
	var _settings = {
		checklistItemSelector:     '[data-fc-setup-checklist-step]',
		checklistItemAttribute:    'data-fc-setup-checklist-step',
		completedLabelSelector:    '[data-fc-setup-checklist-completed-label]',
		isCompletedClass:          'is-completed',

		ajaxUrl:                   '',
		syncTabAction:             'fc_setup_checklist_mark_tab',
		nonce:                     '',
		tabToChecklistStep:        {},

		i18n: {
			completed:                 'Completed',
		},
	};



	/**
	 * METHODS
	 */



	/**
	 * Read getting-started checklist completion state from the dashboard DOM.
	 *
	 * @return  {Object}  Map of step id to boolean completion.
	 */
	var readCompletionStateFromDom = function() {
		var items;
		var i;
		var item;
		var stepId;
		var state;

		state = {};
		items = document.querySelectorAll( _settings.checklistItemSelector );

		// Iterate checklist items
		for ( i = 0; i < items.length; i++ ) {
			item = items[ i ];
			stepId = item.getAttribute( _settings.checklistItemAttribute );

			// Skip items without a step id
			if ( ! stepId ) { continue; }

			state[ stepId ] = item.classList.contains( _settings.isCompletedClass );
		}

		return state;
	};

	/**
	 * Apply getting-started checklist completion state to the dashboard checklist.
	 *
	 * @param   {Object}  state  Map of step id to boolean completion.
	 */
	var applyCompletionState = function( state ) {
		var items;
		var i;
		var item;
		var stepId;
		var isComplete;
		var completedLabel;

		// Bail if state is missing
		if ( ! state || 'object' !== typeof state ) { return; }

		_lastKnownState = Object.assign( {}, state );

		items = document.querySelectorAll( _settings.checklistItemSelector );

		// Iterate checklist items
		for ( i = 0; i < items.length; i++ ) {
			item = items[ i ];
			stepId = item.getAttribute( _settings.checklistItemAttribute );

			// Skip items without a step id
			if ( ! stepId ) { continue; }

			isComplete = !! state[ stepId ];
			completedLabel = item.querySelector( _settings.completedLabelSelector );

			// Maybe mark the step as completed
			if ( isComplete ) {
				item.classList.add( _settings.isCompletedClass );

				// Maybe add the completed label for screen readers
				if ( ! completedLabel ) {
					completedLabel = document.createElement( 'span' );
					completedLabel.className = 'screen-reader-text';
					completedLabel.setAttribute( 'data-fc-setup-checklist-completed-label', '' );
					completedLabel.textContent = _settings.i18n.completed || 'Completed';
					item.appendChild( completedLabel );
				}
			}
			// Otherwise mark the step as incomplete
			else {
				item.classList.remove( _settings.isCompletedClass );

				// Maybe remove the completed label for screen readers
				if ( completedLabel ) {
					completedLabel.remove();
				}
			}
		}
	};

	/**
	 * Maybe apply checklist completion state from an AJAX response payload.
	 *
	 * @param   {Object}  payload  AJAX response object.
	 */
	var maybeApplyCompletionStateFromResponse = function( payload ) {
		var state;

		// Bail if response is missing
		if ( ! payload || ! payload.data ) { return; }

		state = payload.data.checklist_state;

		// Bail if checklist state is missing
		if ( ! state ) { return; }

		applyCompletionState( state );
	};

	/**
	 * Maybe apply an optimistic completion update for a mapped settings tab visit.
	 *
	 * @param   {string}  tab  Settings tab slug.
	 */
	var maybeApplyOptimisticStateForTab = function( tab ) {
		var stepId;
		var optimisticState;

		// Bail if tab slug is missing
		if ( ! tab || ! _settings.tabToChecklistStep ) { return; }

		stepId = _settings.tabToChecklistStep[ tab ];

		// Bail if tab does not map to a checklist step
		if ( ! stepId ) { return; }

		optimisticState = Object.assign( {}, _lastKnownState, readCompletionStateFromDom() );
		optimisticState[ stepId ] = true;

		applyCompletionState( optimisticState );
	};

	/**
	 * Sync getting-started checklist state for the active settings tab.
	 *
	 * @param   {string}  tab  Settings tab slug.
	 */
	var syncForTab = function( tab ) {
		var body;
		var requestOptions;
		var requestId;

		// Bail if tab slug is missing
		if ( ! tab ) { return; }

		// Bail if setup checklist settings are unavailable
		if ( ! _settings.ajaxUrl || ! _settings.nonce ) { return; }

		maybeApplyOptimisticStateForTab( tab );

		requestId = ++_syncRequestId;

		body = new FormData();
		body.append( 'action', _settings.syncTabAction || _settings.markTabAction || 'fc_setup_checklist_mark_tab' );
		body.append( 'nonce', _settings.nonce );
		body.append( 'tab', tab );

		requestOptions = {
			method:      'POST',
			credentials: 'same-origin',
			body:        body,
		};

		fetch( _settings.ajaxUrl, requestOptions )
			.then( function( response ) {
				return response.json();
			} )
			.then( function( payload ) {
				// Bail if a newer sync request was started
				if ( requestId !== _syncRequestId ) { return; }

				// Bail if request failed
				if ( ! payload || ! payload.success ) { return; }

				maybeApplyCompletionStateFromResponse( payload );
			} )
			.catch( function() {
				// Ignore stale sync errors
				if ( requestId !== _syncRequestId ) { return; }
			} );
	};



	/**
	 * Initialize getting-started checklist UI sync.
	 *
	 * @param   {Object}  options  Settings passed from PHP.
	 */
	_publicMethods.init = function( options ) {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Merge settings
		if ( options ) {
			if ( root.FCUtils && typeof FCUtils.extendObject === 'function' ) {
				_settings = FCUtils.extendObject( _settings, options );
			}
			else {
				Object.assign( _settings, options );
			}

			// Support legacy localized key
			if ( options.markTabAction && ! _settings.syncTabAction ) {
				_settings.syncTabAction = options.markTabAction;
			}
		}

		_lastKnownState = readCompletionStateFromDom();

		_hasInitialized = true;
	};

	/**
	 * Apply getting-started checklist completion state to the dashboard checklist.
	 *
	 * @param   {Object}  state  Map of step id to boolean completion.
	 */
	_publicMethods.applyCompletionState = applyCompletionState;

	/**
	 * Sync getting-started checklist state for the active settings tab.
	 *
	 * @param   {string}  tab  Settings tab slug.
	 */
	_publicMethods.syncForTab = syncForTab;

	/**
	 * @deprecated Use syncForTab().
	 * @param   {string}  tab  Settings tab slug.
	 */
	_publicMethods.markTabVisited = syncForTab;



	//
	// Public APIs
	//
	return _publicMethods;

} );
