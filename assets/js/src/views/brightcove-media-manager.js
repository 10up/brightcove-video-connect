var BrightcoveRouter = Backbone.Router.extend({
	routes: {
		'add-new-brightcove-video' : "addNew"
	},
	addNew: function() {
		wpbc.broadcast.trigger('upload:video');
	}
});

var BrightcoveMediaManagerView = BrightcoveView.extend(
	{
		tagName :   'div',
		className : 'brightcove-media',

		events : {
			/*
			 'click .brightcove.media-button': 'insertIntoPost'
			 */
		},

		scrollHandler : function () {
			wpbc.broadcast.trigger( 'scroll:mediaGrid' );
		},

		initialize : function ( options ) {

			var scrollRefreshSensitivity = wp.media.isTouchDevice ? 300 : 200;
			this.scrollHandler           = _.chain( this.scrollHandler ).bind( this ).throttle( scrollRefreshSensitivity ).value();
			this.options                 = options;
			this.mode                    = options.mode || 'manager';

			options.preload = this.options.preload ? wpbc.preload[this.options.mediaType] : false;

			this.model = new BrightcoveMediaManagerModel( options );

			/* Search and dropdowns */
			this.toolbar = new ToolbarView( {model : this.model} );

			/* Uploader View */
			this.uploader = new UploadVideoManagerView();

			this.model.set( 'accounts', wpbc.preload.accounts ); // All accounts.
			this.model.set( 'activeAccount', options.account ); // Active account ID / All

			this.listenTo( this.toolbar, 'viewType', function ( viewType ) {
				this.model.set( 'view', viewType ); // Set the model view type
			} );

			this.listenTo( wpbc.broadcast, 'videoEdit:message', this.message );
			this.listenTo( wpbc.broadcast, 'permanent:message', this.permanentMessage );

			this.listenTo( wpbc.broadcast, 'remove:permanentMessage', function () {

				if ( wpbc.permanentMessage ) {
					wpbc.permanentMessage.remove();
				}

				this.$el.find( '.brightcove-message' ).addClass( 'hidden' );

			} );

			// We only care when a change occurs
			this.listenTo( this.model, 'change:view', function ( model, type ) {
				this.model.get( 'media-collection-view' ).setViewType( type );
			} );

			this.listenTo( this.model, 'change:mode', function ( model, mode ) {

				if ( 'uploader' !== mode ) {
					wpbc.broadcast.trigger( 'uploader:clear' );
				}

			} );

			this.listenTo( wpbc.broadcast, 'cancelPreview:media', function ( settings ) {
				this.clearPreview();
				this.detailsView = undefined;
				this.model.set( 'mode', 'manager' );
				this.render();

				// Disable "Insert Into Post" button since no video would be selected.
				wpbc.broadcast.trigger( 'toggle:insertButton' );
			} );

			this.listenTo( wpbc.broadcast, 'change:emptyPlaylists', function ( hideEmptyPlaylists ) {

				var mediaCollectionView = this.model.get( 'media-collection-view' );
				this.model.set( 'mode', 'manager' );

				_.each( mediaCollectionView.collection.models, function ( playlistModel ) {

					// Don't hide smart playlists. Only Manual playlists will have playlistType as 'EXPLICIT'.
					if ( 'EXPLICIT' !== playlistModel.get ( 'type' ) ) {
						return;
					}

					// Manual play list will have videos populated in video_ids. Empty playlists will have zero video_ids.
					if ( playlistModel.get( 'video_ids' ).length === 0 ) {
						if ( hideEmptyPlaylists ) {
							playlistModel.view.$el.hide();
						} else {
							playlistModel.view.$el.show();
						}
					}
				} );
			} );

			this.listenTo( wpbc.broadcast, 'delete:successful', function ( message ) {

				this.startGridView();
				this.message( message, 'success' );

			} );

			this.listenTo( wpbc.broadcast, 'change:activeAccount', function ( accountId ) {

				this.clearPreview();
				this.model.set( 'activeAccount', accountId );
				this.model.set( 'mode', 'manager' );
				this.render();

			} );

			this.listenTo( wpbc.broadcast, 'change:tag', function ( tag ) {

				this.clearPreview();
				this.model.set( 'tag', tag );

			} );

			this.listenTo( wpbc.broadcast, 'change:date', function ( date ) {

				this.clearPreview();
				this.model.set( 'date', date );

			} );

			this.listenTo( wpbc.broadcast, 'upload:video', function () {
				this.showUploader();
			} );

			this.listenTo( this.model, 'change:search', function ( model, searchTerm ) {
				this.model.get( 'search' );
			} );

			this.listenTo( wpbc.broadcast, 'start:gridview', function () {

				_.invoke( this.subviews, 'remove' );

				this.detailsView = null; // Prevent selected view from not being toggleable when we hit the back button on edit

				this.startGridView();

			} );

			this.listenTo( wpbc.broadcast, 'tabChange', function ( settings ) {

				this.model.set( settings );

				if ( this.detailsView instanceof MediaDetailsView ) {

					this.detailsView.remove();

					this.detailsView = undefined;

				}

				this.render();

			} );

			this.listenTo( wpbc.broadcast, 'edit:media', function ( model ) {

				var mediaType = this.model.get( 'mediaType' );

				if ( mediaType === 'videos' ) {

					// We just hit the edit button with the edit window already open.
					if ( 'editVideo' === this.model.get( 'mode' ) ) {
						return true;
					}

					// hide the previous notification
					var messages = this.$el.find( '.brightcove-message' );
					messages.addClass( 'hidden' );

					this.editView = new VideoEditView( {model : model} );

					this.registerSubview( this.editView );
					this.model.set( 'mode', 'editVideo' );
					this.render();

				} else {

					// We just hit the edit button with the edit window already open.
					if ( 'editPlaylist' === this.model.get( 'mode' ) ) {
						return true;
					}

					this.editView = new PlaylistEditView( {model : model} );

					this.registerSubview( this.editView );
					this.model.set( 'mode', 'editPlaylist' );
					this.render();

				}
			} );

			this.listenTo( wpbc.broadcast, 'preview:media', function ( model, shortcode ) {

				var mediaType = this.model.get( 'mediaType' );

				if ( mediaType === 'videos' ) {

					// We just hit the preview button with the preview window already open.
					if ( 'previewVideo' === this.model.get( 'mode' ) ) {
						return true;
					}

					this.previewView = new VideoPreviewView( {model : model, shortcode: shortcode} );

					this.registerSubview( this.previewView );
					this.model.set( 'mode', 'previewVideo' );
					this.render();

				} else {

					/**
					 * @todo: playlist preview view
					 */
					this.model.set( 'mode', 'editPlaylist' );

				}
			} );

			this.listenTo( wpbc.broadcast, 'change:searchTerm', function ( mediaView ) {
				this.clearPreview();
			} );

			this.listenTo( wpbc.broadcast, 'select:media', function ( mediaView ) {

				/* If user selects same thumbnail they want to hide the details view */
				if ( this.detailsView && this.detailsView.model === mediaView.model ) {

					this.detailsView.$el.toggle();
					mediaView.$el.toggleClass( 'highlighted' );
					this.model.get( 'media-collection-view' ).$el.toggleClass( 'menu-visible' );
					wpbc.broadcast.trigger( 'toggle:insertButton' );

				} else {

					this.clearPreview();
					this.detailsView = new MediaDetailsView( {model : mediaView.model, el : $( '.brightcove.media-frame-menu' ), mediaType : this.model.get( 'mediaType' )} );
					this.registerSubview( this.detailsView );

					this.detailsView.render();
					this.detailsView.$el.toggle( true ); // Always show new view
					this.model.get( 'media-collection-view' ).$el.addClass( 'menu-visible' );
					mediaView.$el.addClass( 'highlighted' );
					wpbc.broadcast.trigger( 'toggle:insertButton', 'enabled' );

				}
			} );

		},

		/**
		 * Clear the preview view and remove highlighted class from previous selected video.
		 */
		clearPreview : function () {

			if ( this.detailsView instanceof MediaDetailsView ) {
				this.detailsView.remove();
			}

			this.model.get( 'media-collection-view' ).$el.find( '.highlighted' ).removeClass( 'highlighted' );

		},

		startGridView : function () {

			this.model.set( 'mode', 'manager' );
			this.render();

		},

		message : function ( message, type, permanent ) {

			var messages = this.$el.find( '.brightcove-message' );

			if ( 'success' === type ) {

				messages.addClass( 'updated' );
				messages.removeClass( 'error' );

			} else if ( 'error' === type ) {

				messages.addClass( 'error' );
				messages.removeClass( 'updated' );

			}

			var newMessage = $( '<p></p>' );
			newMessage.text( message );

			messages.append( newMessage );
			messages.removeClass( 'hidden' );

			if ( permanent ) {

				if ( wpbc.permanentMessage ) {
					wpbc.permanentMessage.remove();
				}

				wpbc.permanentMessage = newMessage;

			} else {
				// Make the notice dismissable.
				messages.addClass( 'notice is-dismissible' );
				this.makeNoticesDismissible();
			}
		},

		// Make notices dismissible, mimics core function, fades them empties.
		makeNoticesDismissible : function() {
			$( '.notice.is-dismissible' ).each( function() {
				var $el = $( this ),
					$button = $( '<button type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button>' ),
					btnText = commonL10n.dismiss || '';

				// Ensure plain text
				$button.find( '.screen-reader-text' ).text( btnText );
				$button.on( 'click.wp-dismiss-notice', function( event ) {
					event.preventDefault();
					$el.fadeTo( 100, 0, function() {
						$el.slideUp( 100, function() {
							$el.addClass( 'hidden' )
								.css( {
									'opacity': 1,
									'margin-bottom': 0,
									'display': ''
								} )
								.empty();
						});
					});
				});

				$el.append( $button );
			});
		},

		showUploader : function () {

			this.model.set( 'mode', 'uploader' );
			this.render();

		},

		permanentMessage : function ( message ) {
			this.message( message, 'error', true );
		},

		render : function () {

			var options = this.model.get( 'options' );
			var contentContainer;

			var mode = this.model.get( 'mode' );

			// Nuke all registered subviews
			_.invoke( this.subviews, 'remove' );

			if ( 'uploader' === mode ) {

				this.template = wp.template( 'brightcove-uploader-container' );

				this.$el.empty();
				this.$el.html( this.template( options ) );
				this.uploader.render();
				this.uploader.delegateEvents();
				this.uploader.$el.appendTo( $( '.brightcove-uploader' ) );

			} else if ( 'manager' === mode ) {

				this.template = wp.template( 'brightcove-media' );

				this.$el.html( this.template( options ) );
				this.toolbar.render();
				this.toolbar.delegateEvents();
				this.toolbar.$el.show();
				this.toolbar.$el.appendTo( this.$el.find( '.media-frame-router' ) );

				// Add the Media views to the media manager
				var mediaCollectionView = this.model.get( 'media-collection-view' );

				mediaCollectionView.render();
				mediaCollectionView.delegateEvents();

				var mediaCollectionContainer = this.$el.find( '.media-frame-content' );

				mediaCollectionContainer.on( 'scroll', this.scrollHandler );
				mediaCollectionView.$el.appendTo( mediaCollectionContainer );

				if ( ! ! wpbc.initialSync ) {

					wpbc.broadcast.trigger( 'remove:permanentMessage' );
					wpbc.broadcast.trigger( 'permanent:message', wpbc.preload.messages.ongoingSync );

				}
			} else if ( 'editVideo' === mode ) {

				this.toolbar.$el.hide();

				contentContainer = this.$el.find( '.media-frame-content' );

				contentContainer.empty();
				this.editView.render();
				this.editView.delegateEvents();
				this.editView.$el.appendTo( contentContainer );
				this.$el.find( '.brightcove.media-frame-content' ).addClass( 'edit-view' );

			} else if ( 'editPlaylist' === mode ) {

				this.toolbar.$el.hide();

				contentContainer = this.$el;

				contentContainer.empty();
				contentContainer.html( '<div class="playlist-edit-container"></div>' );

				contentContainer = contentContainer.find( '.playlist-edit-container' );

				this.editView.render();
				this.editView.delegateEvents();
				this.editView.$el.appendTo( contentContainer );
				contentContainer.addClass( 'playlist' );

			} else if ( 'previewVideo' === mode ) {

				this.toolbar.$el.hide();

				contentContainer = this.$el.find( '.media-frame-content' );

				contentContainer.empty();
				this.previewView.render();
				this.detailsView.render( {detailsMode : 'preview'} );
				this.previewView.delegateEvents();
				this.previewView.$el.appendTo( contentContainer );
				this.$el.find( '.brightcove.media-frame-toolbar' ).hide();
				brightcove.createExperiences();

			}

			if ( 'editPlaylist' !== mode ) {
				this.$el.find( '.media-frame-content' ).removeClass( 'playlist' );
			}

			return this;

		}

	}
);
