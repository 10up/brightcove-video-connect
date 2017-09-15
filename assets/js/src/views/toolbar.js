/**
 * This is the toolbar to handle sorting, filtering, searching and grid/list view toggles.
 * State is captured in the brightcove-media-manager model.
 */
var ToolbarView = BrightcoveView.extend(
	{
		tagName :   'div',
		className : 'media-toolbar wp-filter',
		template :  wp.template( 'brightcove-media-toolbar' ),

		events : {
			'click .view-list' :                   'toggleList',
			'click .view-grid' :                   'toggleGrid',
			'click .brightcove-toolbar':           'toggleToolbar',
			'change .brightcove-media-source' :    'sourceChanged',
			'change .brightcove-media-dates' :     'datesChanged',
			'change .brightcove-media-tags' :      'tagsChanged',
			'change .brightcove-empty-playlists' : 'emptyPlaylistsChanged',
			'click #media-search' : 'searchHandler'
		},

		render : function () {
			var mediaType = this.model.get( 'mediaType' );
			var options   = {
				accounts :  wpbc.preload.accounts,
				dates :     {},
				mediaType : mediaType,
				tags :      wpbc.preload.tags,
				account :   this.model.get( 'account' )
			};

			var dates    = wpbc.preload.dates;
			var date_var = this.model.get( 'date' );
			/* @todo: find out if this is working */
			if ( dates !== undefined && dates[mediaType] !== undefined && dates[mediaType][date_var] !== undefined ) {
				options.dates = dates[mediaType][date_var];
			}

			this.$el.html( this.template( options ) );
			var spinner = this.$el.find( '.spinner' );
			this.listenTo( wpbc.broadcast, 'spinner:on', function () {
				spinner.addClass( 'is-active' ).removeClass( 'hidden' );
			} );
			this.listenTo( wpbc.broadcast, 'spinner:off', function () {
				spinner.removeClass( 'is-active' ).addClass( 'hidden' );
			} );
		},

		// List view Selected
		toggleList : function () {
			this.trigger( 'viewType', 'list' );
			this.$el.find( '.view-list' ).addClass( 'current' );
			this.$el.find( '.view-grid' ).removeClass( 'current' );
		},

		// Grid view Selected
		toggleGrid : function () {
			this.trigger( 'viewType', 'grid' );
			this.$el.find( '.view-grid' ).addClass( 'current' );
			this.$el.find( '.view-list' ).removeClass( 'current' );
		},

		// Toggle toolbar help
		toggleToolbar : function () {
			var template = wp.template( 'brightcove-tooltip-notice' );

			// Throw a notice to the user that the file is not the correct format
			$( '.brightcove-media-videos' ).before( template );
			// Allow the user to dismiss the notice
			$( '#js-tooltip-dismiss' ).on( 'click', function() {
				$( '#js-tooltip-notice' ).first().fadeOut( 500, function() {
					$( this ).remove();
				} );
			} );
		},

		// Brightcove source changed
		sourceChanged : function ( event ) {

			// Store the currently selected account on the model.
			this.model.set( 'account', event.target.value );
			wpbc.broadcast.trigger( 'change:activeAccount', event.target.value );
		},

		datesChanged : function ( event ) {
			wpbc.broadcast.trigger( 'change:date', event.target.value );
		},

		tagsChanged : function ( event ) {
			wpbc.broadcast.trigger( 'change:tag', event.target.value );
		},

		emptyPlaylistsChanged : function ( event ) {
			var emptyPlaylists = $( event.target ).prop( 'checked' );
			wpbc.broadcast.trigger( 'change:emptyPlaylists', emptyPlaylists );
		},

		searchHandler : function ( event ) {
			var searchTerm = $( '#media-search-input' ).val();

			if ( searchTerm.length > 2 && searchTerm !== this.model.get( 'search' ) ) {
				this.model.set( 'search', searchTerm );
				wpbc.broadcast.trigger( 'change:searchTerm', searchTerm );
			}
		}
	}
);

