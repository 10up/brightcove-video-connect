var MediaCollectionView = BrightcoveView.extend(
	{
		tagName :   'ul',
		className : 'brightcove-media attachments',

		attributes : {
			tabIndex : - 1
		},

		events : {
			/* scroll fired on playlist edits, but for media grids it's handled by firing 'scroll:mediaGrid' in brightcove-media-manager */
			'scroll' : 'scrollHandler'
		},

		loadMoreMediaItems : function () {
			this.fetchingResults = true;
			this.collection.fetch();
		},

		scrollHandler : function () {
			// We don't fetch for videos in an existing playlist
			if ( 'existingPlaylists' === this.collection.mediaCollectionViewType ) {
				return;
			}

			var scrollThreshold = 200; // How many px from bottom until we fetch the next page.
			if ( ! this.fetchingResults && this.el.scrollTop + this.el.clientHeight + scrollThreshold > this.el.scrollHeight ) {
				this.collection.pageNumber += 1;
				this.loadMoreMediaItems();
			}
		},

		initialize : function ( options ) {
			this.fetchingResults = false;
			this.listenTo( wpbc.broadcast, 'fetch:finished', function () {
				this.fetchingResults = false;
			} );

			this.listenTo( wpbc.broadcast, 'fetch:apiError', this.handleAPIError );

			var scrollRefreshSensitivity = wp.media.isTouchDevice ? 300 : 200;
			this.scrollHandler           = _.chain( this.scrollHandler ).bind( this ).throttle( scrollRefreshSensitivity ).value();
			this.listenTo( wpbc.broadcast, 'scroll:mediaGrid', this.scrollHandler );
			options    = options || {};
			this.el.id = _.uniqueId( '__attachments-view-' );

			// Occurs on playlist edit, existing videos.
			if ( ! this.collection && options.videoIds ) {
				this.collection = new MediaCollection(
					null,
					{
						videoIds : options.videoIds,
						activeAccount : options.activeAccount,
						mediaCollectionViewType : options.mediaCollectionViewType
					}
				);

				this.listenTo( wpbc.broadcast, 'playlist:moveUp', this.videoMoveUp );
				this.listenTo( wpbc.broadcast, 'playlist:moveDown', this.videoMoveDown );
				this.listenTo( wpbc.broadcast, 'playlist:remove', this.videoRemove );
				this.listenTo( wpbc.broadcast, 'playlist:add', this.videoAdd );
			} else if ( ! this.collection && 'libraryPlaylists' === options.mediaCollectionViewType ) {
				this.collection = new MediaCollection(
					null,
					{
						excludeVideoIds : options.excludeVideoIds,
						activeAccount : options.activeAccount,
						mediaCollectionViewType : options.mediaCollectionViewType
					}
				);

				this.listenTo( wpbc.broadcast, 'playlist:remove', this.videoRemove );
				this.listenTo( wpbc.broadcast, 'playlist:add', this.videoAdd );
			}

			_.defaults( this.options, {
				refreshSensitivity : wp.media.isTouchDevice ? 300 : 200,
				refreshThreshold :   3,
				VideoView :          wp.media.view.Video,
				sortable :           false,
				resize :             true,
				idealColumnWidth :   202
			} );

			this._viewsByCid = {};
			this.resizeEvent = 'resize.media-modal-columns';

			this.listenTo( this.collection, 'add', function ( media ) {
				this.views.add( this.createMediaView( media ), {
					at : this.collection.indexOf( media )
				} );
			}, this );

			this.listenTo( this.collection, 'remove', function ( media ) {
				if ( media ) {
					if ( media.view ) {
						media.view.remove();
					} else if ( media.cid && this._viewsByCid[media.cid] ) {
						this._viewsByCid[media.cid].remove();
					}
				}
			}, this );

			this.listenTo( this.collection, 'reset', this.render );

			// Throttle the scroll handler and bind this.
			this.scroll = _.chain( this.scroll ).bind( this ).throttle( this.options.refreshSensitivity ).value();

			this.options.scrollElement = this.options.scrollElement || this.el;
			$( this.options.scrollElement ).on( 'scroll', this.scroll );

			_.bindAll( this, 'setColumns' );

			if ( this.options.resize ) {
				this.on( 'ready', this.bindEvents );
				// this.controller.on('open', this.setColumns);

				// Call this.setColumns() after this view has been rendered in the DOM so
				// attachments get proper width applied.
				_.defer( this.setColumns, this );
			}
		},

		handleAPIError: function() {
			this.el.innerText = wpbc.str_apifailure;
		},

		render : function () {
			// hide the spinner when content has finished loading
			this.listenTo( wpbc.broadcast, 'spinner:off', function() {
				$( '#js-media-loading' ).css( 'display', 'none' );
			} );

			this.$el.empty();
			this.collection.each( function ( mediaModel ) {
				mediaModel.view = new MediaView( {model : mediaModel} );
				this.registerSubview( mediaModel.view );
				mediaModel.view.render();
				mediaModel.view.delegateEvents();
				mediaModel.view.$el.appendTo( this.$el );

				wpbc.broadcast.trigger( 'spinner:off' );
			}, this );
		},

		setViewType : function ( type ) {
			this.collection.each( function ( mediaModel ) {
				mediaModel.set( 'view', type );
			}, this );
		},

		bindEvents : function () {
			this.$window.off( this.resizeEvent ).on( this.resizeEvent, _.debounce( this.setColumns, 50 ) );
		},

		setColumns : function () {
			var prev  = this.columns,
			    width = this.$el.width();

			if ( width ) {
				this.columns = Math.min( Math.round( width / this.options.idealColumnWidth ), 12 ) || 1;

				if ( ! prev || prev !== this.columns ) {
					this.$el.closest( '.media-frame-content' ).attr( 'data-columns', this.columns );
				}
			}
		},

		/**
		 * @param {wp.media.model.Video} attachment
		 * @returns {wp.media.View}
		 */
		createMediaView : function ( attachment ) {
			attachment.set( 'viewType', this.collection.mediaCollectionViewType );
			var view = new MediaView( {
				controller : this.controller,
				model :      attachment,
				collection : this.collection,
				selection :  this.options.selection
			} );
			this.registerSubview( view );
			this._viewsByCid[attachment.cid] = view;
			return view;
		},

		prepare : function () {
			// Create all of the Video views, and replace
			// the list in a single DOM operation.
			if ( this.collection.length ) {
				this.views.set( this.collection.map( this.createMediaView, this ) );

				// If there are no elements, clear the views and load some.
			} else {
				this.views.unset();
				this.collection.more().done( this.scroll );
			}
		},

		ready : function () {
			// Trigger the scroll event to check if we're within the
			// threshold to query for additional attachments.
			this.scroll();
		},

		scroll : function () {
			var view      = this,
			    el        = this.options.scrollElement,
			    scrollTop = el.scrollTop,
			    toolbar;

			// The scroll event occurs on the document, but the element
			// that should be checked is the document body.
			if ( el === document ) {
				el        = document.body;
				scrollTop = $( document ).scrollTop();
			}

			if ( 'function' !== this.collection.hasMore || ! $( el ).is( ':visible' ) || ! this.collection.hasMore() ) {
				return;
			}

			toolbar = this.views.parent.toolbar;

			// Show the spinner only if we are close to the bottom.
			if ( el.scrollHeight - ( scrollTop + el.clientHeight ) < el.clientHeight / 3 ) {
				toolbar.get( 'spinner' ).show();
			}

			if ( el.scrollHeight < scrollTop + ( el.clientHeight * this.options.refreshThreshold ) ) {
				this.collection.more().done( function () {
					view.scroll();
					toolbar.get( 'spinner' ).hide();
				} );
			}
		},

		videoMoveUp : function ( videoView ) {
			var model = videoView.model;
			var index = this.collection.indexOf( model );
			if ( index > 0 ) {
				this.collection.remove( model, {silent : true} ); // silence this to stop excess event triggers
				this.collection.add( model, {at : index - 1} );
			}
			this.render();
			this.playlistChanged();
		},

		videoMoveDown : function ( videoView ) {
			var model = videoView.model;
			var index = this.collection.indexOf( model );
			if ( index < this.collection.models.length ) {
				this.collection.remove( model, {silent : true} ); // silence this to stop excess event triggers
				this.collection.add( model, {at : index + 1} );
			}
			this.render();
			this.playlistChanged();
		},

		videoRemove : function ( videoView ) {
			var model = videoView.model;
			if ( - 1 === this.collection.indexOf( model ) ) {
				// this is the library model
				this.collection.add( model );
			} else {
				// this is the playlist collection
				this.collection.remove( model, {silent : true} ); // silence this to stop excess event triggers
				this.playlistChanged();
			}
			this.render();
		},

		videoAdd : function ( videoView ) {
			/**
			 * Video add is heard by two collections, the one containing the videos for the playlists
			 * and the one containing the videos that we can add to them.
			 * We handle the add by adding from the collection where it doesn't exist (the playlist) and removing
			 * where it does (the library).
			 */
			var model = videoView.model;
			if ( - 1 === this.collection.indexOf( model ) ) {
				// this is the playlist collection
				this.collection.add( model );
				this.playlistChanged();
			} else {
				// this is the library model
				this.collection.remove( model, {silent : true} );
				this.render();
			}
		},

		playlistChanged : function () {
			var videoIds = [];
			this.collection.each( function ( video ) {
				videoIds.push( video.id );
			} );
			this.videoIds = videoIds;
			// var syncPlaylist = _.throttle(_.bind(this.syncPlaylist, this), 2000);
			this.syncPlaylist();
		},

		syncPlaylist : function () {
			wpbc.broadcast.trigger( 'playlist:changed', this.videoIds );
		}

	}
);

