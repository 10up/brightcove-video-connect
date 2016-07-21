var MediaView = BrightcoveView.extend(
	{
		tagName :   'li',
		className : 'attachment brightcove',

		attributes : function () {
			return {
				'tabIndex' :     0,
				'role' :         'checkbox',
				'aria-label' :   this.model.get( 'title' ),
				'aria-checked' : false,
				'data-id' :      this.model.get( 'id' )
			};
		},

		events : {
			'click .attachment-preview' : 'toggleDetailView',
			'click .video-move-up' :      'videoMoveUp',
			'click .video-move-down' :    'videoMoveDown',
			'click .trash' :              'removeVideoFromPlaylist',
			'click .add-to-playlist' :    'videoAdd',
			'click .edit' :               'triggerEditMedia',
			'click .preview' :            'triggerPreviewMedia'
		},

		triggerEditMedia : function ( event ) {
			event.preventDefault();
			wpbc.broadcast.trigger( 'edit:media', this.model );
		},

		triggerPreviewMedia : function ( event ) {
			event.preventDefault();
			wpbc.broadcast.trigger( 'preview:media', this.model );
		},

		buttons : {},

		initialize : function ( options ) {
			options   = options || {};
			this.type = options.type ? options.type : 'grid';

			// We only care when a change occurs
			this.listenTo( this.model, 'change:view', function ( model, type ) {
				if ( this.type !== type ) {
					this.type = type;
					this.render();
				}
			} );

			this.render();
		},

		render : function () {
			var options                 = this.model.toJSON();
			options.duration            = this.model.getReadableDuration();
			options.updated_at_readable = this.model.getReadableDate( 'updated_at' );
			options.account_name        = this.model.getAccountName();

			if ( 'existingPlaylists' === options.viewType ) {
				this.template = wp.template( 'brightcove-playlist-edit-video-in-playlist' );
			} else if ( 'libraryPlaylists' === options.viewType ) {
				this.template = wp.template( 'brightcove-playlist-edit-video-in-library' );
			} else {
				this.template = wp.template( 'brightcove-media-item-' + this.type );
			}

			options.buttons = this.buttons;

			this.$el.html( this.template( options ) );

			this.$el.toggleClass( 'uploading', options.uploading );

			return this;
		},

		toggleDetailView : function () {
			wpbc.broadcast.trigger( 'select:media', this );
		},

		videoMoveUp : function () {
			wpbc.broadcast.trigger( 'playlist:moveUp', this );
		},

		videoMoveDown : function () {
			wpbc.broadcast.trigger( 'playlist:moveDown', this );
		},

		videoAdd : function () {
			wpbc.broadcast.trigger( 'playlist:add', this );
		},

		removeVideoFromPlaylist : function () {
			wpbc.broadcast.trigger( 'playlist:remove', this );
		}
	}
);
