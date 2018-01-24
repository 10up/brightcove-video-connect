var PlaylistEditView = BrightcoveView.extend(
	{
		tagName :   'div',
		className : 'playlist-edit brightcove attachment-details',
		template :  wp.template( 'brightcove-playlist-edit' ),

		events : {
			'click .brightcove.button.save-sync' : 'saveSync',
			'click .brightcove.playlist-back' :    'back',
			'change .brightcove-name' :            'updatedName'
		},

		deleteVideo : function ( event ) {
			event.preventDefault();
			this.model.set( 'mediaType', 'videos' );
			this.model.destroy();
		},

		updatedName : function ( event ) {
			var name = this.model.get( 'name' );
			if ( name !== event.target.value ) {
				this.model.set( 'name', event.target.value );
				this.model.save();
			}
		},

		back : function ( event ) {
			event.preventDefault();
			wpbc.broadcast.trigger( 'start:gridview' );

		},

		saveSync : function ( event ) {
			event.preventDefault();
			this.model.set( 'name', this.$el.find( '.brightcove-name' ).val() );
			this.model.set( 'description', this.$el.find( '.brightcove-description' ).val() );
			this.model.set( 'long_description', this.$el.find( '.brightcove-long-description' ).val() );
			this.model.set( 'tags', this.$el.find( '.brightcove-tags' ).val() );
			this.model.set( 'mediaType', 'videos' );
			this.model.save();
		},

		initialize : function () {
			this.listenTo( wpbc.broadcast, 'tabChange', function () {
				_.invoke( this.subviews, 'remove' );
			} );
			wpbc.broadcast.trigger( 'spinner:off' );
		},

		render : function ( options ) {
			options = this.model.toJSON();
			this.$el.html( this.template( options ) );
			this.spinner = this.$el.find( '.spinner' );

			if ( options.video_ids ) {
				this.killPendingRequests();

				this.playlistVideosView = new MediaCollectionView( {
					el : this.$el.find( '.existing-videos' ),
					videoIds : options.video_ids,
					activeAccount : this.model.get( 'account_id' ),
					mediaCollectionViewType : 'existingPlaylists',
					mediaType : 'playlists'
				} );

				this.libraryVideosView  = new MediaCollectionView( {
					el : this.$el.find( '.library-videos' ),
					excludeVideoIds : options.video_ids,
					activeAccount : this.model.get( 'account_id' ),
					mediaCollectionViewType : 'libraryPlaylists',
					mediaType : 'playlists'
				} );

				this.registerSubview( this.playlistVideosView );
				this.registerSubview( this.libraryVideosView );

				this.listenTo( wpbc.broadcast, 'playlist:changed', _.throttle( this.playlistChanged, 300 ) );
				this.listenTo( wpbc.broadcast, 'insert:shortcode', this.insertShortcode );
			}

			this.listenTo( wpbc.broadcast, 'spinner:on', function () {
				this.spinner.addClass( 'is-active' ).removeClass( 'hidden' );
			} );

			this.listenTo( wpbc.broadcast, 'spinner:off', function () {
				this.spinner.removeClass( 'is-active' ).addClass( 'hidden' );
			} );
		},

		playlistChanged : function ( videoIds ) {
			this.killPendingRequests();
			this.model.set( 'video_ids', videoIds );
			this.model.save();
		},

		killPendingRequests : function () {
			// Kill all pending requests
			_.each( wpbc.requests, function ( request ) {
				request.abort();
			} );

			wpbc.requests = [];
		}
	}
);
